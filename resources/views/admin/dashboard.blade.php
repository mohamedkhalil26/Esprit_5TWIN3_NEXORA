@extends('layouts.admin')

@section('title', __('Tableau de bord'))

@section('content')
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
                <span class="info-box-icon text-bg-primary shadow-sm"><i class="bi bi-people-fill"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('Membres inscrits') }}</span>
                    <span class="info-box-number">{{ $nbUtilisateurs }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
                <span class="info-box-icon text-bg-warning shadow-sm"><i class="bi bi-shield-lock-fill"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ __('Administrateurs') }}</span>
                    <span class="info-box-number">{{ $nbAdmins }}</span>
                </div>
            </div>
        </div>

        {{-- Indicateurs ajoutés par les modules (hooks/dashboard.blade.php) --}}
        @foreach (\App\Support\Modules::hooks('dashboard') as $hook)
            @include($hook)
        @endforeach
    </div>

    <x-admin.card :title="__('Actions rapides')" icon="lightning-charge" class="mb-3">
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="{{ route('admin.equipements.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('Ajouter un équipement') }}</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.reservations.index') }}"><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>{{ __('Gérer les réservations') }}</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.reclamations.intelligence') }}"><i class="bi bi-chat-square-text me-1" aria-hidden="true"></i>{{ __('Centre des réclamations') }}</a>
            <a class="btn btn-outline-primary" href="{{ route('admin.articles.create') }}"><i class="bi bi-journal-plus me-1" aria-hidden="true"></i>{{ __('Créer un article') }}</a>
            <a class="btn btn-outline-secondary" href="{{ route('ai.index') }}"><i class="bi bi-stars me-1" aria-hidden="true"></i>{{ __('Centre IA') }}</a>
        </div>
    </x-admin.card>

    <div class="row g-3 mt-1">
        @foreach ([
            [__('Équipements disponibles'), $aiKpis['equipments_available'], 'sun'],
            [__('Réservations de la période'), $aiKpis['reservations_total'], 'calendar-check'],
            [__('Réclamations ouvertes'), $aiKpis['claims_open'], 'chat-square-text'],
            [__('Taux d’annulation'), $aiKpis['cancellation_rate'] === null ? 'N/D' : $aiKpis['cancellation_rate'].' %', 'graph-down-arrow'],
        ] as [$label, $value, $icon])
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="info-box"><span class="info-box-icon text-bg-success shadow-sm"><i class="bi bi-{{ $icon }}"></i></span><div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ $value }}</span></div></div>
            </div>
        @endforeach
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <x-admin.card :title="__('Activité réelle — 6 derniers mois')" icon="bar-chart-line">
                @php
                    $maxReservations = max(1, max($tendances['reservations']));
                    $maxClaims = max(1, max($tendances['claims']));
                @endphp
                <div class="dashboard-chart-legend small text-muted mb-3" aria-hidden="true">
                    <span><i class="dashboard-legend-swatch dashboard-legend-reservations"></i>{{ __('Réservations') }}</span>
                    <span><i class="dashboard-legend-swatch dashboard-legend-claims"></i>{{ __('Réclamations') }}</span>
                </div>
                <div class="dashboard-chart-wrap">
                    <div class="dashboard-chart" role="img" aria-label="{{ __('Comparaison mensuelle du nombre de réservations et de réclamations issues des données enregistrées') }}">
                        @foreach ($tendances['periods'] as $index => $period)
                            @php($reservationCount = $tendances['reservations'][$index])
                            @php($claimCount = $tendances['claims'][$index])
                            <div class="dashboard-chart-month">
                                <div class="dashboard-chart-bars">
                                    <span class="dashboard-chart-bar dashboard-chart-bar-reservations" style="height: {{ $reservationCount > 0 ? max(4, 112 * $reservationCount / $maxReservations) : 2 }}px" title="{{ $reservationCount }} réservation(s)"></span>
                                    <span class="dashboard-chart-bar dashboard-chart-bar-claims" style="height: {{ $claimCount > 0 ? max(4, 112 * $claimCount / $maxClaims) : 2 }}px" title="{{ $claimCount }} réclamation(s)"></span>
                                </div>
                                <span class="small text-muted">{{ $period }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <p class="small text-muted mt-2 mb-0">{{ __('Les valeurs sont calculées depuis les enregistrements; une barre vide signifie qu’aucun élément n’a été créé durant le mois.') }}</p>
            </x-admin.card>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <x-admin.card :title="__('Derniers inscrits')" icon="person-plus" body-class="p-0">
                <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th scope="col">{{ __('Nom') }}</th>
                        <th scope="col">{{ __('Adresse e-mail') }}</th>
                        <th scope="col">{{ __('Rôle') }}</th>
                        <th scope="col">{{ __('Inscrit') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($derniersInscrits as $user)
                        <tr>
                            <td><span class="avatar me-2">{{ $user->initiales }}</span>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->isAdmin() ? 'text-bg-warning' : 'text-bg-secondary' }}">{{ $user->role }}</span>
                            </td>
                            <td>{{ $user->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ __('Aucun membre inscrit.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>
            </x-admin.card>
        </div>
        <div class="col-lg-6">
            <x-admin.card :title="__('Bienvenue dans le back office')" icon="sun">
                <p>{{ __('Depuis cet espace, les administrateurs gèrent l\'ensemble de la plateforme SolarShare : catalogue d\'équipements, locations, réclamations, événements et contenus du blog.') }}</p>
                <p class="mb-0 text-muted small">{{ __('Utilisez le menu de gauche pour accéder à chaque module.') }}</p>
            </x-admin.card>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <x-admin.card :title="__('Activité récente')" icon="activity" body-class="p-0">
                @forelse ($activitesRecentes as $activite)
                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center border-bottom px-3 py-2 gap-1 gap-sm-3">
                        <span class="text-break"><i class="bi bi-{{ $activite['icon'] }} me-2 text-success" aria-hidden="true"></i>{{ $activite['label'] }}</span>
                        <time class="small text-muted text-nowrap" datetime="{{ $activite['date']->toAtomString() }}">{{ $activite['date']->diffForHumans() }}</time>
                    </div>
                @empty
                    <p class="text-muted p-3 mb-0">{{ __('Aucune activité enregistrée pour le moment.') }}</p>
                @endforelse
            </x-admin.card>
        </div>
    </div>
@endsection
