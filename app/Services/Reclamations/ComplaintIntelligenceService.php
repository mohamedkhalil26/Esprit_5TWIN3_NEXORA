<?php

namespace App\Services\Reclamations;

use App\Models\Reclamation;
use App\Models\ReclamationHistory;
use Illuminate\Support\Carbon;

final class ComplaintIntelligenceService
{
    public function dashboard(): array
    {
        $statuses = Reclamation::query()->selectRaw('statut, COUNT(*) AS total')->groupBy('statut')->pluck('total', 'statut');
        $categories = Reclamation::query()->selectRaw('categorie, COUNT(*) AS total')->groupBy('categorie')->pluck('total', 'categorie');
        $priorities = Reclamation::query()->selectRaw('priorite, COUNT(*) AS total')->groupBy('priorite')->pluck('total', 'priorite');

        $from = now()->startOfMonth()->subMonths(11);
        $createdByMonth = Reclamation::query()->where('created_at', '>=', $from)->pluck('created_at')
            ->filter()->groupBy(fn ($createdAt) => Carbon::parse($createdAt)->format('Y-m'))
            ->map->count();
        $monthly = [];
        for ($offset = 0; $offset < 12; $offset++) {
            $month = $from->copy()->addMonths($offset);
            $key = $month->format('Y-m');
            $monthly[] = ['key' => $key, 'label' => $month->translatedFormat('M Y'), 'total' => (int) ($createdByMonth[$key] ?? 0)];
        }

        return [
            'total' => (int) Reclamation::count(),
            'pending' => (int) ($statuses['ouverte'] ?? 0),
            'in_progress' => (int) ($statuses['en_cours'] ?? 0),
            'resolved' => (int) ($statuses['resolue'] ?? 0),
            'closed' => (int) ($statuses['fermee'] ?? 0),
            'by_status' => collect(Reclamation::STATUTS)->keys()->mapWithKeys(fn ($key) => [$key => (int) ($statuses[$key] ?? 0)]),
            'by_category' => collect(Reclamation::CATEGORIES)->keys()->mapWithKeys(fn ($key) => [$key => (int) ($categories[$key] ?? 0)]),
            'by_priority' => collect(Reclamation::PRIORITES)->keys()->mapWithKeys(fn ($key) => [$key => (int) ($priorities[$key] ?? 0)]),
            'monthly' => $monthly,
            'average_resolution_hours' => $this->averageResolutionHours(),
        ];
    }

    /** @return list<string> Categories with at least two real records. */
    public function recurringCategories(): array
    {
        return Reclamation::query()->selectRaw('categorie, COUNT(*) AS total')
            ->groupBy('categorie')->havingRaw('COUNT(*) >= 2')->orderByDesc('total')
            ->pluck('categorie')->all();
    }

    private function averageResolutionHours(): ?float
    {
        $resolutionChanges = ReclamationHistory::query()->with('reclamation:id,created_at')
            ->get(['id', 'reclamation_id', 'changes', 'created_at'])
            ->filter(fn (ReclamationHistory $history) => ($history->changes['statut']['apres'] ?? null) === 'resolue')
            ->groupBy('reclamation_id')
            ->map(fn ($histories) => $histories->sortByDesc('id')->first());

        $hours = $resolutionChanges->map(function (ReclamationHistory $history) {
            $createdAt = $history->reclamation?->created_at;
            if (! $createdAt || ! $history->created_at || $history->created_at->lt($createdAt)) {
                return null;
            }

            return $createdAt->diffInMinutes($history->created_at) / 60;
        })->filter(fn ($value) => $value !== null);

        return $hours->isEmpty() ? null : round((float) $hours->avg(), 2);
    }
}
