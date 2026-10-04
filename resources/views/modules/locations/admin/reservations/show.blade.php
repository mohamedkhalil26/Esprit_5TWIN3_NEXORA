@extends('layouts.admin')

@section('title', 'Réservation n°'.$reservation->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
    <li class="breadcrumb-item active">N°{{ $reservation->id }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5">
            <x-admin.card title="Détail de la réservation" icon="calendar-check">
                <x-slot:tools>
                    <span class="badge fs-6 text-bg-{{ $reservation->statut_couleur }}">{{ $reservation->statut_label }}</span>
                </x-slot:tools>

                <dl class="row detail-list mb-0">
                    <dt class="col-sm-5">Équipement</dt>
                    <dd class="col-sm-7">
                        <a href="{{ route('admin.equipements.show', $reservation->equipement) }}">{{ $reservation->equipement->nom }}</a>
                        <div class="small text-muted">{{ $reservation->equipement->categorie->nom }} · {{ $reservation->equipement->ville }}</div>
                    </dd>
                    <dt class="col-sm-5">Locataire</dt>
                    <dd class="col-sm-7">{{ $reservation->locataire->name }}<div class="small text-muted">{{ $reservation->locataire->email }}</div></dd>
                    <dt class="col-sm-5">Période</dt>
                    <dd class="col-sm-7">{{ $reservation->periode }}</dd>
                    <dt class="col-sm-5">Durée</dt>
                    <dd class="col-sm-7">{{ $reservation->nb_jours }} jour(s) × {{ number_format($reservation->equipement->prix_jour, 2, ',', ' ') }} DT</dd>
                    <dt class="col-sm-5">Montant total</dt>
                    <dd class="col-sm-7 fw-bold fs-5">{{ number_format($reservation->montant_total, 2, ',', ' ') }} DT</dd>
                    <dt class="col-sm-5">Déjà payé</dt>
                    <dd class="col-sm-7 text-success">{{ number_format($reservation->montant_paye, 2, ',', ' ') }} DT</dd>
                    <dt class="col-sm-5">Reste à payer</dt>
                    <dd class="col-sm-7 {{ $reservation->reste_a_payer > 0 ? 'text-danger' : 'text-success' }} fw-semibold">
                        {{ number_format($reservation->reste_a_payer, 2, ',', ' ') }} DT
                    </dd>
                    <dt class="col-sm-5">Créée le</dt>
                    <dd class="col-sm-7">{{ $reservation->created_at->format('d/m/Y H:i') }}</dd>
                    @if ($reservation->message)
                        <dt class="col-12">Message</dt>
                        <dd class="col-12 fst-italic">« {{ $reservation->message }} »</dd>
                    @endif
                </dl>

                <x-slot:footer>
                    <a href="{{ route('admin.reservations.edit', $reservation) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                    <x-delete-button :action="route('admin.reservations.destroy', $reservation)" :label="'la réservation n°'.$reservation->id" text="Supprimer" class="float-end" />
                </x-slot:footer>
            </x-admin.card>
        </div>

        <div class="col-lg-7">
            {{-- Jointure 1-N : paiements de la réservation --}}
            <x-admin.card title="Paiements" icon="credit-card" body-class="p-0">
                <x-slot:tools>
                    @if ($reservation->reste_a_payer > 0 && $reservation->statut !== 'annulee')
                        <a href="{{ route('admin.paiements.create', ['reservation' => $reservation->id]) }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg me-1"></i>Enregistrer un paiement
                        </a>
                    @endif
                </x-slot:tools>

                @if ($reservation->paiements->isEmpty())
                    <x-empty-state icon="wallet2" message="Aucun paiement pour cette réservation." />
                @else
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Date</th>
                            <th>Méthode</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($reservation->paiements as $paiement)
                            <tr>
                                <td><code>{{ $paiement->reference }}</code></td>
                                <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                <td><i class="bi bi-{{ $paiement->methode_icone }} me-1"></i>{{ $paiement->methode_label }}</td>
                                <td class="fw-semibold">{{ number_format($paiement->montant, 2, ',', ' ') }} DT</td>
                                <td><span class="badge text-bg-{{ $paiement->statut_couleur }}">{{ $paiement->statut_label }}</span></td>
                                <td class="actions text-end">
                                    <a href="{{ route('admin.paiements.edit', $paiement) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </x-admin.card>
        </div>
    </div>
@endsection
