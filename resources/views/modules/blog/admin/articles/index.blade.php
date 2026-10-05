@extends('layouts.admin')

@section('title', 'Articles du blog')

@section('breadcrumb')
    <li class="breadcrumb-item active">Articles</li>
@endsection

@section('content')
    <x-admin.card title="Liste des articles" icon="journal-text" body-class="p-0">
        <x-slot:tools>
            <a href="{{ route('admin.articles.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nouvel article
            </a>
        </x-slot:tools>

        <form method="GET" class="row g-2 p-3 border-bottom">
            <div class="col-md-5">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Titre ou résumé...">
            </div>
            <div class="col-md-3">
                <select name="categorie" class="form-select">
                    <option value="">Toutes catégories</option>
                    @foreach (\App\Models\Article::CATEGORIES as $valeur => [$libelle, $icone])
                        <option value="{{ $valeur }}" @selected(request('categorie') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    @foreach (\App\Models\Article::STATUTS as $valeur => [$libelle, $couleur])
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrer</button>
            </div>
        </form>

        @if ($articles->isEmpty())
            <x-empty-state icon="journal-x" message="Aucun article trouvé." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Article</th>
                        <th>Catégorie</th>
                        <th>Auteur</th>
                        <th>Statut</th>
                        <th>Publié le</th>
                        <th class="text-center"><i class="bi bi-eye" title="Vues"></i></th>
                        <th class="text-center"><i class="bi bi-chat" title="Commentaires"></i></th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($articles as $article)
                        <tr>
                            <td><a href="{{ route('admin.articles.show', $article) }}" class="fw-semibold">{{ \Illuminate\Support\Str::limit($article->titre, 50) }}</a></td>
                            <td><i class="bi bi-{{ $article->categorie_icone }} me-1"></i>{{ $article->categorie_label }}</td>
                            <td>{{ $article->auteur->name }}</td>
                            <td><span class="badge text-bg-{{ $article->statut_couleur }}">{{ $article->statut_label }}</span></td>
                            <td class="small">{{ $article->publie_le?->format('d/m/Y') ?? '—' }}</td>
                            <td class="text-center">{{ $article->vues }}</td>
                            <td class="text-center"><span class="badge rounded-pill text-bg-light border">{{ $article->commentaires_count }}</span></td>
                            <td class="actions text-end">
                                <a href="{{ route('admin.articles.show', $article) }}" class="btn btn-sm btn-outline-info" title="Voir"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <x-delete-button :action="route('admin.articles.destroy', $article)" :label="$article->titre" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            {{ $articles->links() }}
        </x-slot:footer>
    </x-admin.card>
@endsection
