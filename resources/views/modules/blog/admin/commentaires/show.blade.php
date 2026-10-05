@extends('layouts.admin')

@section('title', 'Commentaire n°'.$commentaire->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.commentaires.index') }}">Commentaires</a></li>
    <li class="breadcrumb-item active">N°{{ $commentaire->id }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-7">
            <x-admin.card title="Commentaire" icon="chat-left-text">
                <x-slot:tools>
                    <span class="badge {{ $commentaire->approuve ? 'text-bg-success' : 'text-bg-warning' }}">{{ $commentaire->approuve ? 'Approuvé' : 'En attente' }}</span>
                </x-slot:tools>
                <div class="d-flex align-items-center mb-3">
                    <span class="avatar me-2">{{ $commentaire->auteur->initiales }}</span>
                    <div>
                        <strong>{{ $commentaire->auteur->name }}</strong>
                        <div class="small text-muted">{{ $commentaire->created_at->format('d/m/Y à H:i') }}</div>
                    </div>
                </div>
                <blockquote class="blockquote fs-6 mb-0">{!! nl2br(e($commentaire->contenu)) !!}</blockquote>

                <x-slot:footer>
                    <form method="POST" action="{{ route('admin.commentaires.approbation', $commentaire) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm {{ $commentaire->approuve ? 'btn-outline-secondary' : 'btn-success' }}">
                            <i class="bi bi-{{ $commentaire->approuve ? 'eye-slash' : 'check-lg' }} me-1"></i>{{ $commentaire->approuve ? 'Masquer' : 'Approuver' }}
                        </button>
                    </form>
                    <a href="{{ route('admin.commentaires.edit', $commentaire) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                    <x-delete-button :action="route('admin.commentaires.destroy', $commentaire)" label="ce commentaire" text="Supprimer" class="float-end" />
                </x-slot:footer>
            </x-admin.card>
        </div>
        <div class="col-lg-5">
            {{-- Jointure : article commenté --}}
            <x-admin.card title="Article commenté" icon="journal-text">
                <h6><a href="{{ route('admin.articles.show', $commentaire->article) }}">{{ $commentaire->article->titre }}</a></h6>
                <p class="small text-muted mb-2">{{ $commentaire->article->resume }}</p>
                <span class="badge text-bg-{{ $commentaire->article->statut_couleur }}">{{ $commentaire->article->statut_label }}</span>
                <span class="badge text-bg-light border"><i class="bi bi-{{ $commentaire->article->categorie_icone }} me-1"></i>{{ $commentaire->article->categorie_label }}</span>
            </x-admin.card>
        </div>
    </div>
@endsection
