@extends('layouts.admin')

@section('title', 'Réponse n°'.$reponse->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reponses.index') }}">Réponses</a></li>
    <li class="breadcrumb-item active">N°{{ $reponse->id }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-7">
            <x-admin.card title="Réponse" icon="reply">
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar me-2">{{ $reponse->auteur->initiales }}</span>
                    <div>
                        <strong>{{ $reponse->auteur->name }}</strong>
                        <div class="small text-muted">{{ $reponse->created_at->format('d/m/Y à H:i') }}</div>
                    </div>
                </div>
                <p class="mb-0">{!! nl2br(e($reponse->message)) !!}</p>

                <x-slot:footer>
                    <a href="{{ route('admin.reponses.edit', $reponse) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                    <x-delete-button :action="route('admin.reponses.destroy', $reponse)" label="cette réponse" text="Supprimer" class="float-end" />
                </x-slot:footer>
            </x-admin.card>
        </div>
        <div class="col-lg-5">
            {{-- Jointure : réclamation liée --}}
            <x-admin.card title="Réclamation concernée" icon="exclamation-diamond">
                <h6><a href="{{ route('admin.reclamations.show', $reponse->reclamation) }}">N°{{ $reponse->reclamation->id }} — {{ $reponse->reclamation->sujet }}</a></h6>
                <p class="small text-muted">{{ \Illuminate\Support\Str::limit($reponse->reclamation->description, 180) }}</p>
                <span class="badge text-bg-{{ $reponse->reclamation->statut_couleur }}">{{ $reponse->reclamation->statut_label }}</span>
                <span class="badge text-bg-{{ $reponse->reclamation->priorite_couleur }}">{{ $reponse->reclamation->priorite_label }}</span>
                <div class="small mt-2">Déposée par {{ $reponse->reclamation->auteur->name }}</div>
            </x-admin.card>
        </div>
    </div>
@endsection
