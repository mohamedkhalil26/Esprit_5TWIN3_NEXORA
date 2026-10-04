@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
                <span class="info-box-icon text-bg-primary shadow-sm"><i class="bi bi-people-fill"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Membres inscrits</span>
                    <span class="info-box-number">{{ $nbUtilisateurs }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="info-box">
                <span class="info-box-icon text-bg-warning shadow-sm"><i class="bi bi-shield-lock-fill"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Administrateurs</span>
                    <span class="info-box-number">{{ $nbAdmins }}</span>
                </div>
            </div>
        </div>

        {{-- Indicateurs ajoutés par les modules (hooks/dashboard.blade.php) --}}
        @foreach (\App\Support\Modules::hooks('dashboard') as $hook)
            @include($hook)
        @endforeach
    </div>

    <div class="row">
        <div class="col-lg-6">
            <x-admin.card title="Derniers inscrits" icon="person-plus" body-class="p-0">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>Nom</th>
                        <th>E-mail</th>
                        <th>Rôle</th>
                        <th>Inscrit</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($derniersInscrits as $user)
                        <tr>
                            <td><span class="avatar me-2">{{ $user->initiales }}</span>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->isAdmin() ? 'text-bg-warning' : 'text-bg-secondary' }}">{{ $user->role }}</span>
                            </td>
                            <td>{{ $user->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </x-admin.card>
        </div>
        <div class="col-lg-6">
            <x-admin.card title="Bienvenue dans le back office" icon="sun">
                <p>
                    Depuis cet espace, les administrateurs gèrent l'ensemble de la plateforme SolarShare :
                    catalogue d'équipements, locations, réclamations, événements et contenus du blog.
                </p>
                <p class="mb-0 text-muted small">Utilisez le menu de gauche pour accéder à chaque module.</p>
            </x-admin.card>
        </div>
    </div>
@endsection
