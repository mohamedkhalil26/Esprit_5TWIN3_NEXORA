@extends('layouts.admin')

@section('title', 'Participations')

@section('breadcrumb')
    <li class="breadcrumb-item active">Participations</li>
@endsection

@section('content')
    <x-admin.card title="Inscriptions aux événements" icon="people" body-class="p-0">
        <x-slot:tools>
            <a href="{{ route('admin.participations.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle inscription
            </a>
        </x-slot:tools>

        <form method="GET" class="row g-2 p-3 border-bottom">
            <div class="col-md-3">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Nom du membre...">
            </div>
            <div class="col-md-5">
                <select name="evenement" class="form-select">
                    <option value="">Tous les événements</option>
                    @foreach ($evenements as $evenement)
                        <option value="{{ $evenement->id }}" @selected(request('evenement') == $evenement->id)>
                            {{ $evenement->date_debut->format('d/m/Y') }} — {{ $evenement->titre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    @foreach (\App\Models\Participation::STATUTS as $valeur => [$libelle, $couleur])
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrer</button>
            </div>
        </form>

        @if ($participations->isEmpty())
            <x-empty-state icon="people" message="Aucune inscription trouvée." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>N°</th>
                        <th>Membre</th>
                        <th>Événement</th>
                        <th>Date de l'événement</th>
                        <th>Statut</th>
                        <th>Inscrit le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($participations as $participation)
                        <tr>
                            <td>{{ $participation->id }}</td>
                            <td>{{ $participation->participant->name }}</td>
                            <td><a href="{{ route('admin.evenements.show', $participation->evenement) }}">{{ \Illuminate\Support\Str::limit($participation->evenement->titre, 40) }}</a></td>
                            <td class="small">{{ $participation->evenement->date_debut->format('d/m/Y H:i') }}</td>
                            <td><span class="badge text-bg-{{ $participation->statut_couleur }}">{{ $participation->statut_label }}</span></td>
                            <td class="small">{{ $participation->created_at->format('d/m/Y') }}</td>
                            <td class="actions text-end">
                                <a href="{{ route('admin.participations.show', $participation) }}" class="btn btn-sm btn-outline-info" title="Voir"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.participations.edit', $participation) }}" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <x-delete-button :action="route('admin.participations.destroy', $participation)" :label="'l\'inscription n°'.$participation->id" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            {{ $participations->links() }}
        </x-slot:footer>
    </x-admin.card>
@endsection
