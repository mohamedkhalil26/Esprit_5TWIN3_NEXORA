<?php

namespace App\Services\Ai\Dashboard;

use App\Models\Article;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Carbon;

final class AIDashboardService
{
    /**
     * @return array<string, int|float|null>
     */
    public function admin(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(30)->startOfDay();
        $to ??= now()->endOfDay();
        $reservations = Reservation::whereBetween('created_at', [$from, $to]);
        $totalReservations = (clone $reservations)->count();
        $cancelledReservations = (clone $reservations)->where('statut', 'annulee')->count();
        $events = Evenement::whereBetween('created_at', [$from, $to]);
        $claims = Reclamation::whereBetween('created_at', [$from, $to]);

        return [
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'users' => User::where('role', User::ROLE_USER)->whereBetween('created_at', [$from, $to])->count(),
            'equipments_total' => Equipement::count(),
            'equipments_available' => Equipement::disponibles()->count(),
            'reservations_total' => $totalReservations,
            'reservations_confirmed' => (clone $reservations)->where('statut', 'confirmee')->count(),
            'cancellation_rate' => $totalReservations > 0 ? round($cancelledReservations / $totalReservations * 100, 2) : null,
            'events_upcoming' => (clone $events)->where('statut', 'planifie')->where('date_debut', '>=', now())->count(),
            'participations_active' => $events->withCount(['participations as active_participations_count' => fn ($query) => $query->where('statut', '!=', 'annule')])->get()->sum('active_participations_count'),
            'claims_open' => (clone $claims)->whereIn('statut', ['ouverte', 'en_cours'])->count(),
            'claims_urgent' => (clone $claims)->where('priorite', 'urgente')->whereIn('statut', ['ouverte', 'en_cours'])->count(),
            'articles_published' => Article::publies()->whereBetween('publie_le', [$from, $to])->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function front(User $user): array
    {
        return [
            'reservations' => $user->reservations()->count(),
            'active_reservations' => $user->reservations()->whereIn('statut', Reservation::STATUTS_ACTIFS)->count(),
            'open_claims' => $user->reclamations()->whereIn('statut', ['ouverte', 'en_cours'])->count(),
            'upcoming_events' => $user->participations()->whereHas('evenement', fn ($query) => $query->where('date_debut', '>=', now())->where('statut', 'planifie'))->where('statut', '!=', 'annule')->count(),
        ];
    }

    /**
     * @return array{periods: list<string>, reservations: list<int>, cancellations: list<int>, claims: list<int>, events: list<int>, equipment_demand: array<string, int>}
     */
    public function insights(int $months = 6): array
    {
        $months = max(1, min(12, $months));
        $periods = [];
        $reservations = [];
        $cancellations = [];
        $claims = [];
        $events = [];
        $from = now()->startOfMonth()->subMonths($months - 1);
        for ($offset = 0; $offset < $months; $offset++) {
            $start = $from->copy()->addMonths($offset)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $periods[] = $start->format('m/Y');
            $reservations[] = Reservation::whereBetween('created_at', [$start, $end])->count();
            $cancellations[] = Reservation::where('statut', 'annulee')->whereBetween('created_at', [$start, $end])->count();
            $claims[] = Reclamation::whereBetween('created_at', [$start, $end])->count();
            $events[] = Evenement::whereBetween('created_at', [$start, $end])->count();
        }
        $equipmentDemand = Reservation::with('equipement.categorie')
            ->whereBetween('created_at', [$from, now()])
            ->get()
            ->groupBy(fn (Reservation $reservation) => $reservation->equipement?->categorie?->nom ?? 'Catégorie inconnue')
            ->map->count()
            ->sortDesc()
            ->all();

        return ['periods' => $periods, 'reservations' => $reservations, 'cancellations' => $cancellations, 'claims' => $claims, 'events' => $events, 'equipment_demand' => $equipmentDemand];
    }
}
