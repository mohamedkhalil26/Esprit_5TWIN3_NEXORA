@extends('layouts.admin')

@section('title', 'Événements')

@section('breadcrumb')
    <li class="breadcrumb-item active">Événements</li>
@endsection

@section('content')
    <x-admin.card title="Liste des événements" icon="calendar-event" body-class="p-0">
        <x-slot:tools>
            <a href="{{ route('admin.evenements.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nouvel événement
            </a>
        </x-slot:tools>

        <form method="GET" class="row g-2 p-3 border-bottom">
            <div class="col-md-4">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Titre ou lieu...">
            </div>
            <div class="col-md-2">
                <select name="type" class="form-select">
                    <option value="">Tous types</option>
                    @foreach (\App\Models\Evenement::TYPES as $valeur => [$libelle, $icone])
                        <option value="{{ $valeur }}" @selected(request('type') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    @foreach (\App\Models\Evenement::STATUTS as $valeur => [$libelle, $couleur])
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="periode" class="form-select">
                    <option value="">Toutes dates</option>
                    <option value="a_venir" @selected(request('periode') === 'a_venir')>À venir</option>
                    <option value="passes" @selected(request('periode') === 'passes')>Passés</option>
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrer</button>
            </div>
        </form>

        @if ($evenements->isEmpty())
            <x-empty-state icon="calendar-x" message="Aucun événement trouvé." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Événement</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Lieu</th>
                        <th>Remplissage</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($evenements as $evenement)
                        @php($taux = round($evenement->nb_inscrits / max(1, $evenement->capacite) * 100))
                        <tr>
                            <td><a href="{{ route('admin.evenements.show', $evenement) }}" class="fw-semibold">{{ \Illuminate\Support\Str::limit($evenement->titre, 45) }}</a></td>
                            <td><i class="bi bi-{{ $evenement->type_icone }} me-1"></i>{{ $evenement->type_label }}</td>
                            <td class="small">{{ $evenement->date_debut->format('d/m/Y H:i') }}</td>
                            <td class="small">{{ $evenement->lieu }}<br><span class="text-muted">{{ $evenement->ville }}</span></td>
                            <td style="min-width: 120px">
                                <div class="progress" style="height: 6px">
                                    <div class="progress-bar {{ $taux >= 100 ? 'bg-danger' : 'bg-success' }}" style="width: {{ min(100, $taux) }}%"></div>
                                </div>
                                <small class="text-muted">{{ $evenement->nb_inscrits }} / {{ $evenement->capacite }}</small>
                            </td>
                            <td>{{ $evenement->prix_label }}</td>
                            <td><span class="badge text-bg-{{ $evenement->statut_couleur }}">{{ $evenement->statut_label }}</span></td>
                            <td class="actions text-end">
                                <a href="{{ route('admin.evenements.show', $evenement) }}" class="btn btn-sm btn-outline-info" title="Voir"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.evenements.edit', $evenement) }}" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <x-delete-button :action="route('admin.evenements.destroy', $evenement)" :label="$evenement->titre" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            {{ $evenements->links() }}
        </x-slot:footer>
    </x-admin.card>
@endsection
