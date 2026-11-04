<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reservation;
use App\Services\Ai\Scoring\RecommendationScorer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiRecommendationController extends Controller
{
    public function energy(Request $request, RecommendationScorer $scorer): View
    {
        $filters = $request->validate([
            'need' => ['nullable', 'string', 'max:180'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'power' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'capacity' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'city' => ['nullable', 'string', 'max:120'],
        ]);
        $searched = $request->query->count() > 0;
        $items = collect();

        if ($searched) {
            $items = Equipement::with('categorie')->disponibles()
                ->when($request->user(), fn ($query, $user) => $query->where('user_id', '!=', $user->id))
                ->when(filled($filters['need'] ?? null), fn ($query) => $query->where(function ($q) use ($filters) {
                    $term = '%'.trim($filters['need']).'%';
                    $q->where('nom', 'like', $term)->orWhere('description', 'like', $term)->orWhere('marque', 'like', $term);
                }))
                ->when(filled($filters['category'] ?? null), fn ($query) => $query->where('categorie_id', $filters['category']))
                ->when(isset($filters['power']) && $filters['power'] > 0, fn ($query) => $query->where('puissance_watts', '>=', $filters['power']))
                ->when(isset($filters['capacity']) && $filters['capacity'] > 0, fn ($query) => $query->where('capacite_wh', '>=', $filters['capacity']))
                ->when(isset($filters['budget']), fn ($query) => $query->where('prix_jour', '<=', $filters['budget']))
                ->when(filled($filters['city'] ?? null), fn ($query) => $query->where('ville', 'like', '%'.trim($filters['city']).'%'))
                ->limit(60)->get()
                ->map(function (Equipement $item) use ($filters, $scorer) {
                    $score = $scorer->scoreEquipment($item, [
                        'puissance_min' => $filters['power'] ?? null,
                        'capacite_min' => $filters['capacity'] ?? null,
                        'ville' => $filters['city'] ?? null,
                        'category_id' => $filters['category'] ?? null,
                    ]);
                    $item->recommendation = $score;
                    $item->recommendation_explanation = $this->equipmentExplanation($score['components']);

                    return $item;
                })->sortByDesc(fn (Equipement $item) => $item->recommendation['score'])->values();
        }

        return view('ai.recommendations.index', [
            'mode' => 'energy', 'items' => $items, 'filters' => $filters, 'searched' => $searched, 'days' => null,
            'categories' => Categorie::whereHas('equipements', fn ($query) => $query->disponibles())->orderBy('nom')->get(),
        ]);
    }

    public function booking(Request $request, RecommendationScorer $scorer): View
    {
        $searched = $request->query->count() > 0;
        $filters = $request->validate([
            'need' => ['nullable', 'string', 'max:180'],
            'date_start' => [$searched ? 'required' : 'nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'date_end' => [$searched ? 'required' : 'nullable', 'date_format:Y-m-d', 'after_or_equal:date_start'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'city' => ['nullable', 'string', 'max:120'],
        ]);
        $items = collect();
        $days = null;

        if ($searched) {
            $days = Reservation::calculerJours($filters['date_start'], $filters['date_end']);
            if ($days > 30) {
                $validator = validator([], []);
                $validator->errors()->add('date_end', __('nexora.booking.max_duration'));
                throw new ValidationException($validator);
            }

            $items = Equipement::with('categorie')->disponibles()
                ->where('user_id', '!=', $request->user()->id)
                ->when(filled($filters['need'] ?? null), fn ($query) => $query->where(function ($q) use ($filters) {
                    $term = '%'.trim($filters['need']).'%';
                    $q->where('nom', 'like', $term)->orWhere('description', 'like', $term)->orWhere('marque', 'like', $term);
                }))
                ->when(filled($filters['city'] ?? null), fn ($query) => $query->where('ville', 'like', '%'.trim($filters['city']).'%'))
                ->whereNotIn('id', Reservation::query()->select('equipement_id')->whereIn('statut', Reservation::STATUTS_ACTIFS)
                    ->whereDate('date_debut', '<=', $filters['date_end'])->whereDate('date_fin', '>=', $filters['date_start']))
                ->when(isset($filters['budget']), fn ($query) => $query->where('prix_jour', '<=', $filters['budget'] / $days))
                ->orderBy('prix_jour')->limit(60)->get()
                ->map(function (Equipement $item) use ($filters, $days, $scorer) {
                    $item->booking_total = round((float) $item->prix_jour * $days, 2);
                    $score = $scorer->scoreBooking($item, ['ville' => $filters['city'] ?? null]);
                    $item->recommendation = $score;
                    $item->recommendation_explanation = $this->equipmentExplanation($score['components']);

                    return $item;
                })->sortByDesc(fn (Equipement $item) => $item->recommendation['score'])->values();
        }

        return view('ai.recommendations.index', [
            'mode' => 'booking', 'items' => $items, 'filters' => $filters, 'searched' => $searched, 'days' => $days,
        ]);
    }

    public function events(Request $request, RecommendationScorer $scorer): View
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(Evenement::TYPES))],
            'city' => ['nullable', 'string', 'max:120'],
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
        ]);
        $searched = $request->query->count() > 0;
        $items = collect();

        if ($searched) {
            $items = Evenement::aVenir()->avecNbInscrits()
                ->when($request->user(), fn ($query, $user) => $query->with(['participations' => fn ($q) => $q->where('user_id', $user->id)]))
                ->when(filled($filters['type'] ?? null), fn ($query) => $query->where('type', $filters['type']))
                ->when(filled($filters['city'] ?? null), fn ($query) => $query->where('ville', 'like', '%'.trim($filters['city']).'%'))
                ->when(filled($filters['date'] ?? null), fn ($query) => $query->whereDate('date_debut', '>=', $filters['date']))
                ->when(isset($filters['budget']), fn ($query) => $query->where('prix', '<=', $filters['budget']))
                ->orderBy('date_debut')->limit(60)->get()
                ->map(function (Evenement $event) use ($filters, $scorer) {
                    $score = $scorer->scoreEvent($event, [
                        'type' => $filters['type'] ?? null,
                        'ville' => $filters['city'] ?? null,
                        'date_debut' => $filters['date'] ?? null,
                    ]);
                    $event->recommendation = $score;
                    $event->recommendation_explanation = $this->eventExplanation($score['components']);

                    return $event;
                })->sortByDesc(fn (Evenement $event) => $event->recommendation['score'])->values();
        }

        return view('ai.recommendations.index', [
            'mode' => 'events', 'items' => $items, 'filters' => $filters, 'searched' => $searched, 'days' => null,
        ]);
    }

    private function equipmentExplanation(array $components): string
    {
        if ($components === []) {
            return __('nexora.recommendation.no_scored_criteria');
        }

        $matched = collect($components)->filter(fn ($score) => $score >= 70)->keys()
            ->map(fn ($criterion) => __('nexora.criteria.'.$criterion))->filter()->values();

        return $matched->isEmpty()
            ? __('nexora.recommendation.explanation_generic')
            : __('nexora.recommendation.explanation_matches', ['criteria' => $matched->join(', ')]);
    }

    private function eventExplanation(array $components): string
    {
        if ($components === []) {
            return __('nexora.recommendation.no_scored_criteria');
        }

        $matched = collect($components)->filter(fn ($score) => $score >= 70)->keys()
            ->map(fn ($criterion) => __('nexora.criteria.'.$criterion))->filter()->values();

        return $matched->isEmpty()
            ? __('nexora.recommendation.explanation_generic')
            : __('nexora.recommendation.explanation_matches', ['criteria' => $matched->join(', ')]);
    }
}
