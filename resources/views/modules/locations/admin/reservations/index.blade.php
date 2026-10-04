@extends('layouts.admin')

@section('title', 'Réservations')

@section('breadcrumb')
    <li class="breadcrumb-item active">Réservations</li>
@endsection

@section('content')
    <x-admin.card title="Liste des réservations" icon="calendar-check" body-class="p-0">
        <x-slot:tools>
            <a href="{{ route('admin.reservations.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nouvelle réservation
            </a>
        </x-slot:tools>

        <form method="GET" class="row g-2 p-3 border-bottom">
            <div class="col-md-4">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Équipement ou locataire...">
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    @foreach (\App\Models\Reservation::STATUTS as $valeur => [$libelle, $couleur])
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="du" value="{{ request('du') }}" class="form-control" title="Début à partir du">
            </div>
            <div class="col-md-2">
                <input type="date" name="au" value="{{ request('au') }}" class="form-control" title="Fin au plus tard le">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrer</button>
            </div>
        </form>

        @if ($reservations->isEmpty())
            <x-empty-state icon="calendar-x" message="Aucune réservation trouvée." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>N°</th>
                        <th>Équipement</th>
                        <th>Locataire</th>
                        <th>Période</th>
                        <th>Montant</th>
                        <th>Payé</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($reservations as $reservation)
                        <tr>
                            <td>{{ $reservation->id }}</td>
                            <td><a href="{{ route('admin.equipements.show', $reservation->equipement) }}">{{ $reservation->equipement->nom }}</a></td>
                            <td>{{ $reservation->locataire->name }}</td>
                            <td class="small">{{ $reservation->periode }}<br><span class="text-muted">{{ $reservation->nb_jours }} jour(s)</span></td>
                            <td class="fw-semibold">{{ number_format($reservation->montant_total, 2, ',', ' ') }} DT</td>
                            <td>
                                @php($taux = $reservation->montant_total > 0 ? min(100, round(($reservation->total_paye ?? 0) / $reservation->montant_total * 100)) : 0)
                                <div class="progress" style="height: 6px; width: 80px" title="{{ $taux }} %">
                                    <div class="progress-bar bg-success" style="width: {{ $taux }}%"></div>
                                </div>
                                <small class="text-muted">{{ $taux }} %</small>
                            </td>
                            <td><span class="badge text-bg-{{ $reservation->statut_couleur }}">{{ $reservation->statut_label }}</span></td>
                            <td class="actions text-end">
                                <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-info" title="Voir"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.reservations.edit', $reservation) }}" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <x-delete-button :action="route('admin.reservations.destroy', $reservation)" :label="'la réservation n°'.$reservation->id" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            {{ $reservations->links() }}
        </x-slot:footer>
    </x-admin.card>
@endsection
