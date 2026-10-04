@extends('layouts.admin')

@section('title', 'Catégories')

@section('breadcrumb')
    <li class="breadcrumb-item active">Catégories</li>
@endsection

@section('content')
    <x-admin.card title="Liste des catégories" icon="tags" body-class="p-0">
        <x-slot:tools>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle catégorie
            </a>
        </x-slot:tools>

        <form method="GET" class="p-3 border-bottom">
            <div class="input-group" style="max-width: 420px">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Rechercher une catégorie...">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>

        @if ($categories->isEmpty())
            <x-empty-state icon="tags" message="Aucune catégorie trouvée." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Catégorie</th>
                        <th>Description</th>
                        <th class="text-center">Équipements</th>
                        <th>Créée le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($categories as $categorie)
                        <tr>
                            <td>{{ $categorie->id }}</td>
                            <td>
                                <i class="bi {{ $categorie->icone }} text-warning me-2"></i>
                                <a href="{{ route('admin.categories.show', $categorie) }}" class="fw-semibold">{{ $categorie->nom }}</a>
                            </td>
                            <td class="text-muted small">{{ \Illuminate\Support\Str::limit($categorie->description, 70) }}</td>
                            <td class="text-center"><span class="badge text-bg-primary">{{ $categorie->equipements_count }}</span></td>
                            <td>{{ $categorie->created_at->format('d/m/Y') }}</td>
                            <td class="actions text-end">
                                <a href="{{ route('admin.categories.show', $categorie) }}" class="btn btn-sm btn-outline-info" title="Voir"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.categories.edit', $categorie) }}" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <x-delete-button :action="route('admin.categories.destroy', $categorie)" :label="$categorie->nom" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            {{ $categories->links() }}
        </x-slot:footer>
    </x-admin.card>
@endsection
