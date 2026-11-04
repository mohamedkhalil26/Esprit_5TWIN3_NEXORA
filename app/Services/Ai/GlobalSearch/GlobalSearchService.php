<?php

namespace App\Services\Ai\GlobalSearch;

use App\Models\Article;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Collection;

final class GlobalSearchService
{
    public function search(?User $user, string $query, ?string $type = null): Collection
    {
        $term = trim($query);
        if ($term === '') {
            return collect();
        }
        $like = '%'.$term.'%';
        $results = collect();

        if ($type === null || $type === 'equipements') {
            $results = $results->merge(Equipement::disponibles()->where(function ($builder) use ($like) {
                $builder->where('nom', 'like', $like)->orWhere('marque', 'like', $like)->orWhere('description', 'like', $like);
            })->limit(20)->get()->map(fn (Equipement $item) => $this->result('Équipement', $item->nom, $item->description, route('front.equipements.show', $item), $term)));
        }
        if ($type === null || $type === 'evenements') {
            $results = $results->merge(Evenement::aVenir()->where(function ($builder) use ($like) {
                $builder->where('titre', 'like', $like)->orWhere('description', 'like', $like)->orWhere('ville', 'like', $like);
            })->limit(20)->get()->map(fn (Evenement $item) => $this->result('Événement', $item->titre, $item->description, route('front.evenements.show', $item), $term)));
        }
        if ($type === null || $type === 'articles') {
            $results = $results->merge(Article::publies()->where(function ($builder) use ($like) {
                $builder->where('titre', 'like', $like)->orWhere('resume', 'like', $like)->orWhere('contenu', 'like', $like);
            })->limit(20)->get()->map(fn (Article $item) => $this->result('Article', $item->titre, $item->resume, route('front.blog.show', $item->slug), $term)));
        }
        if ($user && ($type === null || $type === 'reservations')) {
            $results = $results->merge($user->reservations()->with('equipement')->whereHas('equipement', fn ($builder) => $builder->where('nom', 'like', $like))->limit(20)->get()->map(fn (Reservation $item) => $this->result('Ma réservation', 'Réservation n°'.$item->id, $item->equipement?->nom.' · '.$item->statut, route('front.reservations.show', $item), $term)));
        }
        if ($user && ($type === null || $type === 'reclamations')) {
            $results = $results->merge($user->reclamations()->where(function ($builder) use ($like) {
                $builder->where('sujet', 'like', $like)->orWhere('description', 'like', $like);
            })->limit(20)->get()->map(fn (Reclamation $item) => $this->result('Ma réclamation', $item->sujet, $item->statut, route('front.reclamations.show', $item), $term)));
        }

        return $results->sortByDesc('relevance')->take(50)->values();
    }

    private function result(string $type, string $title, ?string $description, string $url, string $term): array
    {
        $normalizedTerm = mb_strtolower($term);
        $normalizedTitle = mb_strtolower($title);
        $normalizedDescription = mb_strtolower(strip_tags((string) $description));
        $relevance = $normalizedTitle === $normalizedTerm ? 100 : (str_starts_with($normalizedTitle, $normalizedTerm) ? 80 : (str_contains($normalizedTitle, $normalizedTerm) ? 60 : (str_contains($normalizedDescription, $normalizedTerm) ? 30 : 10)));

        return ['type' => $type, 'title' => $title, 'description' => strip_tags((string) $description), 'url' => $url, 'relevance' => $relevance];
    }
}
