@extends('layouts.admin')

@section('title', 'Équipements')

@section('breadcrumb')
    <li class="breadcrumb-item active">Équipements</li>
@endsection

@section('content')
    <x-admin.card title="Liste des équipements" icon="lightning-charge" body-class="p-0">
        <x-slot:tools>
            <a href="{{ route('admin.equipements.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nouvel équipement
            </a>
        </x-slot:tools>

        {{-- Filtres --}}
        <form method="GET" class="row g-2 p-3 border-bottom">
            <div class="col-md-5">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Nom, marque, description...">
            </div>
            <div class="col-md-3">
                <select name="categorie" class="form-select">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $id => $nom)
                        <option value="{{ $id }}" @selected(request('categorie') == $id)>{{ $nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="disponible" class="form-select">
                    <option value="">Tous statuts</option>
                    <option value="1" @selected(request('disponible') === '1')>Disponibles</option>
                    <option value="0" @selected(request('disponible') === '0')>Indisponibles</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrer</button>
            </div>
        </form>

        @if ($equipements->isEmpty())
            <x-empty-state icon="lightning" message="Aucun équipement ne correspond à votre recherche." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th></th>
                        <th>Équipement</th>
                        <th>Catégorie</th>
                        <th>Propriétaire</th>
                        <th>Ville</th>
                        <th>Prix / jour</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($equipements as $equipement)
                        <tr>
                            <td>
                                @if ($equipement->image)
                                    <img src="{{ $equipement->image_url }}" alt="" class="thumb">
                                @else
                                    <span class="thumb d-inline-flex align-items-center justify-content-center bg-light text-warning">
                                        <i class="bi {{ $equipement->categorie->icone }} fs-4"></i>
                                    </span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.equipements.show', $equipement) }}" class="fw-semibold">{{ $equipement->nom }}</a>
                                <div class="small text-muted">{{ $equipement->marque }}</div>
                            </td>
                            {{-- Données issues des jointures --}}
                            <td><a href="{{ route('admin.categories.show', $equipement->categorie) }}">{{ $equipement->categorie->nom }}</a></td>
                            <td>{{ $equipement->proprietaire->name }}</td>
                            <td>{{ $equipement->ville }}</td>
                            <td class="fw-semibold">{{ number_format($equipement->prix_jour, 2, ',', ' ') }} DT</td>
                            <td>
                                <span class="badge {{ $equipement->disponible ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $equipement->disponible ? 'Disponible' : 'Indisponible' }}
                                </span>
                            </td>
                            <td class="actions text-end">
                                <a href="{{ route('admin.equipements.show', $equipement) }}" class="btn btn-sm btn-outline-info" title="Voir"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.equipements.edit', $equipement) }}" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <x-delete-button :action="route('admin.equipements.destroy', $equipement)" :label="$equipement->nom" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            {{ $equipements->links() }}
        </x-slot:footer>
    </x-admin.card>
@endsection
