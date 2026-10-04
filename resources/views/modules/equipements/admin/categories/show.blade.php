@extends('layouts.admin')

@section('title', $categorie->nom)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Catégories</a></li>
    <li class="breadcrumb-item active">{{ $categorie->nom }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <x-admin.card title="Détail de la catégorie" icon="info-circle">
                <div class="text-center mb-3">
                    <i class="bi {{ $categorie->icone }} display-3 text-warning"></i>
                </div>
                <dl class="row detail-list mb-0">
                    <dt class="col-5">Nom</dt>
                    <dd class="col-7">{{ $categorie->nom }}</dd>
                    <dt class="col-5">Équipements</dt>
                    <dd class="col-7">{{ $equipements->total() }}</dd>
                    <dt class="col-5">Créée le</dt>
                    <dd class="col-7">{{ $categorie->created_at->format('d/m/Y H:i') }}</dd>
                    <dt class="col-12">Description</dt>
                    <dd class="col-12">{{ $categorie->description ?: '—' }}</dd>
                </dl>

                <x-slot:footer>
                    <a href="{{ route('admin.categories.edit', $categorie) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                    <x-delete-button :action="route('admin.categories.destroy', $categorie)" :label="$categorie->nom" text="Supprimer" class="float-end" />
                </x-slot:footer>
            </x-admin.card>
        </div>

        <div class="col-lg-8">
            {{-- Jointure : équipements appartenant à cette catégorie --}}
            <x-admin.card title="Équipements de cette catégorie" icon="lightning-charge" body-class="p-0">
                <x-slot:tools>
                    <a href="{{ route('admin.equipements.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Ajouter</a>
                </x-slot:tools>

                @if ($equipements->isEmpty())
                    <x-empty-state icon="lightning" message="Aucun équipement dans cette catégorie." />
                @else
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Équipement</th>
                            <th>Propriétaire</th>
                            <th>Prix / jour</th>
                            <th>Statut</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($equipements as $equipement)
                            <tr>
                                <td><a href="{{ route('admin.equipements.show', $equipement) }}">{{ $equipement->nom }}</a></td>
                                <td>{{ $equipement->proprietaire->name }}</td>
                                <td>{{ number_format($equipement->prix_jour, 2, ',', ' ') }} DT</td>
                                <td>
                                    <span class="badge {{ $equipement->disponible ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $equipement->disponible ? 'Disponible' : 'Indisponible' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif

                <x-slot:footer>{{ $equipements->links() }}</x-slot:footer>
            </x-admin.card>
        </div>
    </div>
@endsection
