@extends('layouts.admin')

@section('title', $equipement->nom)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.equipements.index') }}">Équipements</a></li>
    <li class="breadcrumb-item active">{{ $equipement->nom }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <x-admin.card body-class="text-center">
                @if ($equipement->image)
                    <img src="{{ $equipement->image_url }}" alt="{{ $equipement->nom }}" class="img-fluid rounded mb-3">
                @else
                    <div class="py-5 bg-light rounded mb-3"><i class="bi {{ $equipement->categorie->icone }} display-1 text-warning"></i></div>
                @endif
                <h4 class="mb-1">{{ $equipement->nom }}</h4>
                <p class="text-muted mb-2">{{ $equipement->marque }}</p>
                <span class="badge fs-6 {{ $equipement->disponible ? 'text-bg-success' : 'text-bg-secondary' }}">
                    {{ $equipement->disponible ? 'Disponible' : 'Indisponible' }}
                </span>
            </x-admin.card>
        </div>

        <div class="col-lg-8">
            <x-admin.card title="Fiche détaillée" icon="card-list">
                <dl class="row detail-list">
                    <dt class="col-sm-4">Catégorie</dt>
                    <dd class="col-sm-8">
                        <i class="bi {{ $equipement->categorie->icone }} me-1"></i>
                        <a href="{{ route('admin.categories.show', $equipement->categorie) }}">{{ $equipement->categorie->nom }}</a>
                    </dd>

                    <dt class="col-sm-4">Propriétaire</dt>
                    <dd class="col-sm-8">{{ $equipement->proprietaire->name }} — {{ $equipement->proprietaire->email }}</dd>

                    <dt class="col-sm-4">Prix par jour</dt>
                    <dd class="col-sm-8 fw-bold">{{ number_format($equipement->prix_jour, 2, ',', ' ') }} DT</dd>

                    <dt class="col-sm-4">Caution</dt>
                    <dd class="col-sm-8">{{ number_format($equipement->caution, 2, ',', ' ') }} DT</dd>

                    <dt class="col-sm-4">Puissance</dt>
                    <dd class="col-sm-8">{{ $equipement->puissance_watts ? $equipement->puissance_watts.' W' : '—' }}</dd>

                    <dt class="col-sm-4">Capacité</dt>
                    <dd class="col-sm-8">{{ $equipement->capacite_wh ? $equipement->capacite_wh.' Wh' : '—' }}</dd>

                    <dt class="col-sm-4">État</dt>
                    <dd class="col-sm-8">{{ $equipement->etat_label }}</dd>

                    <dt class="col-sm-4">Ville</dt>
                    <dd class="col-sm-8">{{ $equipement->ville }}</dd>

                    <dt class="col-sm-4">Ajouté le</dt>
                    <dd class="col-sm-8">{{ $equipement->created_at->format('d/m/Y à H:i') }}</dd>

                    <dt class="col-sm-12">Description</dt>
                    <dd class="col-sm-12">{!! nl2br(e($equipement->description)) !!}</dd>
                </dl>

                <x-slot:footer>
                    <a href="{{ route('admin.equipements.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Retour</a>
                    <a href="{{ route('front.equipements.show', $equipement) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-globe me-1"></i>Voir sur le site</a>
                    <span class="float-end">
                        <a href="{{ route('admin.equipements.edit', $equipement) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                        <x-delete-button :action="route('admin.equipements.destroy', $equipement)" :label="$equipement->nom" text="Supprimer" />
                    </span>
                </x-slot:footer>
            </x-admin.card>
        </div>
    </div>
@endsection
