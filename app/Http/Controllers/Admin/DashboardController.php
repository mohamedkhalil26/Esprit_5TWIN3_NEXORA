<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Equipement;
use App\Models\Evenement;
use App\Models\Reclamation;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Ai\Dashboard\AIDashboardService;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(AIDashboardService $dashboard): View
    {
        // Activité calculée à partir des dernières créations persistées, sans compteurs fictifs.
        $activitesRecentes = collect([
            [User::class, 'name', 'Nouvelle inscription : ', 'person-plus'],
            [Equipement::class, 'nom', 'Équipement ajouté : ', 'sun'],
            [Reservation::class, null, 'Réservation créée n°', 'calendar-check'],
            [Reclamation::class, 'sujet', 'Réclamation créée : ', 'chat-square-text'],
            [Evenement::class, 'titre', 'Événement créé : ', 'calendar-event'],
            [Article::class, 'titre', 'Article créé : ', 'journal-text'],
        ])->flatMap(function (array $source) {
            [$model, $field, $prefix, $icon] = $source;
            $columns = $field ? ['id', 'created_at', $field] : ['id', 'created_at'];

            return $model::query()->latest()->limit(5)->get($columns)->map(fn ($record) => [
                'label' => $prefix.($field ? Str::limit($record->{$field}, 70) : $record->getKey()),
                'date' => $record->created_at,
                'icon' => $icon,
            ]);
        })->sortByDesc('date')->take(8)->values();

        return view('admin.dashboard', [
            'nbUtilisateurs' => User::where('role', User::ROLE_USER)->count(),
            'nbAdmins' => User::where('role', User::ROLE_ADMIN)->count(),
            'derniersInscrits' => User::latest()->take(5)->get(),
            'activitesRecentes' => $activitesRecentes,
            'aiKpis' => $dashboard->admin(),
            'tendances' => $dashboard->insights(6),
        ]);
    }
}
