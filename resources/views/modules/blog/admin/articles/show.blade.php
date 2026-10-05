@extends('layouts.admin')

@section('title', \Illuminate\Support\Str::limit($article->titre, 50))

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.articles.index') }}">Articles</a></li>
    <li class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($article->titre, 30) }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <x-admin.card :title="$article->titre" icon="journal-text">
                <x-slot:tools>
                    <span class="badge text-bg-{{ $article->statut_couleur }}">{{ $article->statut_label }}</span>
                </x-slot:tools>

                @if ($article->image)
                    <img src="{{ $article->image_url }}" alt="" class="img-fluid rounded mb-3">
                @endif
                <p class="lead">{{ $article->resume }}</p>
                <hr>
                <div>{!! nl2br(e($article->contenu)) !!}</div>

                <x-slot:footer>
                    <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                    @if ($article->statut === 'publie')
                        <a href="{{ route('front.blog.show', $article) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-globe me-1"></i>Voir sur le blog</a>
                    @endif
                    <x-delete-button :action="route('admin.articles.destroy', $article)" :label="$article->titre" text="Supprimer" class="float-end" />
                </x-slot:footer>
            </x-admin.card>
        </div>

        <div class="col-lg-4">
            <x-admin.card title="Informations" icon="info-circle">
                <dl class="row detail-list mb-0">
                    <dt class="col-5">Auteur</dt>
                    <dd class="col-7">{{ $article->auteur->name }}</dd>
                    <dt class="col-5">Catégorie</dt>
                    <dd class="col-7"><i class="bi bi-{{ $article->categorie_icone }} me-1"></i>{{ $article->categorie_label }}</dd>
                    <dt class="col-5">Publié le</dt>
                    <dd class="col-7">{{ $article->publie_le?->format('d/m/Y H:i') ?? 'Non publié' }}</dd>
                    <dt class="col-5">Lecture</dt>
                    <dd class="col-7">{{ $article->temps_lecture }} min</dd>
                    <dt class="col-5">Vues</dt>
                    <dd class="col-7">{{ $article->vues }}</dd>
                    <dt class="col-5">Slug</dt>
                    <dd class="col-7"><code class="small">{{ $article->slug }}</code></dd>
                </dl>
            </x-admin.card>

            {{-- Jointure 1-N : commentaires de l'article --}}
            <x-admin.card title="Commentaires ({{ $article->commentaires->count() }})" icon="chat-left-text" body-class="p-0">
                <x-slot:tools>
                    <a href="{{ route('admin.commentaires.create', ['article' => $article->id]) }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i></a>
                </x-slot:tools>
                <ul class="list-group list-group-flush">
                    @forelse ($article->commentaires as $commentaire)
                        <li class="list-group-item {{ $commentaire->approuve ? '' : 'bg-warning-subtle' }}">
                            <div class="d-flex justify-content-between">
                                <strong class="small">{{ $commentaire->auteur->name }}</strong>
                                <small class="text-muted">{{ $commentaire->created_at->diffForHumans() }}</small>
                            </div>
                            <div class="small">{{ $commentaire->contenu }}</div>
                            <div class="mt-1">
                                <form method="POST" action="{{ route('admin.commentaires.approbation', $commentaire) }}" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-link p-0 me-2">{{ $commentaire->approuve ? 'Masquer' : 'Approuver' }}</button>
                                </form>
                                <a href="{{ route('admin.commentaires.edit', $commentaire) }}" class="btn btn-sm btn-link p-0">Modifier</a>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted small">Aucun commentaire.</li>
                    @endforelse
                </ul>
            </x-admin.card>
        </div>
    </div>
@endsection
