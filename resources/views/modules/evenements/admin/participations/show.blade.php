@extends('layouts.admin')

@section('title', 'Inscription n°'.$participation->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.participations.index') }}">Participations</a></li>
    <li class="breadcrumb-item active">N°{{ $participation->id }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <x-admin.card title="Inscription" icon="person-check">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar me-3" style="width:3rem;height:3rem;font-size:1.1rem">{{ $participation->participant->initiales }}</span>
                    <div>
                        <h5 class="mb-0">{{ $participation->participant->name }}</h5>
                        <small class="text-muted">{{ $participation->participant->email }}</small>
                    </div>
                </div>
                <dl class="row detail-list mb-0">
                    <dt class="col-sm-4">Statut</dt>
                    <dd class="col-sm-8"><span class="badge text-bg-{{ $participation->statut_couleur }}">{{ $participation->statut_label }}</span></dd>
                    <dt class="col-sm-4">Inscrit le</dt>
                    <dd class="col-sm-8">{{ $participation->created_at->format('d/m/Y à H:i') }}</dd>
                    <dt class="col-sm-4">Commentaire</dt>
                    <dd class="col-sm-8">{{ $participation->commentaire ?: '—' }}</dd>
                </dl>

                <x-slot:footer>
                    <a href="{{ route('admin.participations.edit', $participation) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                    <x-delete-button :action="route('admin.participations.destroy', $participation)" label="cette inscription" text="Supprimer" class="float-end" />
                </x-slot:footer>
            </x-admin.card>
        </div>
        <div class="col-lg-6">
            {{-- Jointure : événement lié --}}
            <x-admin.card title="Événement" icon="calendar-event">
                <h5><a href="{{ route('admin.evenements.show', $participation->evenement) }}">{{ $participation->evenement->titre }}</a></h5>
                <p class="mb-1"><i class="bi bi-{{ $participation->evenement->type_icone }} me-1"></i>{{ $participation->evenement->type_label }}</p>
                <p class="mb-1"><i class="bi bi-calendar3 me-1"></i>{{ $participation->evenement->date_debut->format('d/m/Y H:i') }}</p>
                <p class="mb-1"><i class="bi bi-geo-alt me-1"></i>{{ $participation->evenement->lieu }}, {{ $participation->evenement->ville }}</p>
                <p class="mb-0"><i class="bi bi-people me-1"></i>{{ $participation->evenement->nb_inscrits }} / {{ $participation->evenement->capacite }} inscrits</p>
            </x-admin.card>
        </div>
    </div>
@endsection
