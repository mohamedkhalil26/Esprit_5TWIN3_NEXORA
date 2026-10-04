@extends('layouts.admin')

@section('title', 'Paiements')

@section('breadcrumb')
    <li class="breadcrumb-item active">Paiements</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="info-box">
                <span class="info-box-icon text-bg-success shadow-sm"><i class="bi bi-cash-stack"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total encaissé (validé)</span>
                    <span class="info-box-number">{{ number_format($totalValide, 2, ',', ' ') }} DT</span>
                </div>
            </div>
        </div>
    </div>

    <x-admin.card title="Liste des paiements" icon="credit-card" body-class="p-0">
        <x-slot:tools>
            <a href="{{ route('admin.paiements.create') }}" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nouveau paiement
            </a>
        </x-slot:tools>

        <form method="GET" class="row g-2 p-3 border-bottom">
            <div class="col-md-5">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Référence ou locataire...">
            </div>
            <div class="col-md-3">
                <select name="methode" class="form-select">
                    <option value="">Toutes méthodes</option>
                    @foreach (\App\Models\Paiement::METHODES as $valeur => [$libelle, $icone])
                        <option value="{{ $valeur }}" @selected(request('methode') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous statuts</option>
                    @foreach (\App\Models\Paiement::STATUTS as $valeur => [$libelle, $couleur])
                        <option value="{{ $valeur }}" @selected(request('statut') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel me-1"></i>Filtrer</button>
            </div>
        </form>

        @if ($paiements->isEmpty())
            <x-empty-state icon="wallet2" message="Aucun paiement trouvé." />
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Réservation</th>
                        <th>Locataire</th>
                        <th>Date</th>
                        <th>Méthode</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($paiements as $paiement)
                        <tr>
                            <td><code>{{ $paiement->reference }}</code></td>
                            <td>
                                <a href="{{ route('admin.reservations.show', $paiement->reservation) }}">N°{{ $paiement->reservation->id }}</a>
                                <div class="small text-muted">{{ $paiement->reservation->equipement->nom }}</div>
                            </td>
                            <td>{{ $paiement->reservation->locataire->name }}</td>
                            <td>{{ $paiement->date_paiement->format('d/m/Y H:i') }}</td>
                            <td><i class="bi bi-{{ $paiement->methode_icone }} me-1"></i>{{ $paiement->methode_label }}</td>
                            <td class="fw-semibold">{{ number_format($paiement->montant, 2, ',', ' ') }} DT</td>
                            <td><span class="badge text-bg-{{ $paiement->statut_couleur }}">{{ $paiement->statut_label }}</span></td>
                            <td class="actions text-end">
                                <a href="{{ route('admin.paiements.show', $paiement) }}" class="btn btn-sm btn-outline-info" title="Voir"><i class="bi bi-eye"></i></a>
                                <a href="{{ route('admin.paiements.edit', $paiement) }}" class="btn btn-sm btn-outline-warning" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <x-delete-button :action="route('admin.paiements.destroy', $paiement)" :label="'le paiement '.$paiement->reference" />
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            {{ $paiements->links() }}
        </x-slot:footer>
    </x-admin.card>
@endsection
