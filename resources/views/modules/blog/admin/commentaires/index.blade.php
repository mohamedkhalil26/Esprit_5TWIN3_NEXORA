@extends('layouts.admin')

@section('title', 'Commentaires')

@section('breadcrumb')
    <li class="breadcrumb-item active">Commentaires</li>
@endsection

@section('content')
    @if ($enAttente)
        <div class="callout callout-warning">
            <i class="bi bi-hourglass-split me-1"></i>
            <strong>{{ $enAttente }}</strong> commentaire(s) en attente de modération.
            <a href="{{ route('admin.commentaires.index', ['approuve' => 0]) }}">Les afficher</a>
        </div>
    @endif

    <x-admin.card title="Modération des commentaires" icon="chat-left-text" body-class="p-0">
        <x-slot:tools>
            <a href="{{ route('admin.commentaires.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nouveau commentaire
            </a>
        </x-slot:tools>

        <form method="GET" class="row g-2 p-3 border-bottom">
            <div class="col-md-4">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher dans les commentaires...">
            </div>
            <div class="col-md-4">
                <select name="article" class="form-select">
                    <option value="">Tous les articles</option>
                    @foreach ($articles as $id => $titre)
                        <option value="{{ $id }}" @selected(request('article') == $id)>{{ \Illuminate\Support\Str::limit($titre, 50) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="approuve" class="form-select">
                    <option value="">Tous</option>
                    <option value="1" @selected(request('approuve') === '1')>Approuvés</option>
                    <option value="0" @selected(request('approuve') === '0')>En attente</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrer</button>
            </div>
        </form>

        @if ($commentaires->isEmpty())
            <x-empty-state icon="chat" message="Aucun commentaire trouvé." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Auteur</th>
                        <th>Commentaire</th>
                        <th>Article</th>
                        <th>Date</th>
                        <th>État</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($commentaires as $commentaire)
                        <tr>
                            <td><span class="avatar me-2">{{ $commentaire->auteur->initiales }}</span>{{ $commentaire->auteur->name }}</td>
                            <td class="small">{{ \Illuminate\Support\Str::limit($commentaire->contenu, 70) }}</td>
                            <td class="small"><a href="{{ route('admin.articles.show', $commentaire->article) }}">{{ \Illuminate\Support\Str::limit($commentaire->article->titre, 35) }}</a></td>
                            <td class="small">{{ $commentaire->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.commentaires.approbation', $commentaire) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="badge border-0 {{ $commentaire->approuve ? 'text-bg-success' : 'text-bg-warning' }}" title="Cliquer pour changer">
                                        {{ $commentaire->approuve ? 'Approuvé' : 'En attente' }}
                                    </button>
                                </form>
                            </td>
                            <td class="actions text-end">
                                <a href="{{ route('admin.commentaires.show', $commentaire) }}" class="btn btn-sm btn-outline-info" title="Voir"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.commentaires.edit', $commentaire) }}" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <x-delete-button :action="route('admin.commentaires.destroy', $commentaire)" :label="'le commentaire de '.$commentaire->auteur->name" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            {{ $commentaires->links() }}
        </x-slot:footer>
    </x-admin.card>
@endsection
