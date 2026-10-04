@extends('layouts.admin')

@section('title', 'Paiement '.$paiement->reference)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.paiements.index') }}">Paiements</a></li>
    <li class="breadcrumb-item active">{{ $paiement->reference }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-6">
            <x-admin.card title="Détail du paiement" icon="receipt">
                <div class="text-center mb-4">
                    <div class="display-6 fw-bold">{{ number_format($paiement->montant, 2, ',', ' ') }} DT</div>
                    <span class="badge fs-6 text-bg-{{ $paiement->statut_couleur }}">{{ $paiement->statut_label }}</span>
                </div>
                <dl class="row detail-list mb-0">
                    <dt class="col-sm-5">Référence</dt>
                    <dd class="col-sm-7"><code>{{ $paiement->reference }}</code></dd>
                    <dt class="col-sm-5">Méthode</dt>
                    <dd class="col-sm-7"><i class="bi bi-{{ $paiement->methode_icone }} me-1"></i>{{ $paiement->methode_label }}</dd>
                    <dt class="col-sm-5">Date</dt>
                    <dd class="col-sm-7">{{ $paiement->date_paiement->format('d/m/Y à H:i') }}</dd>
                    <dt class="col-sm-5">Enregistré le</dt>
                    <dd class="col-sm-7">{{ $paiement->created_at->format('d/m/Y H:i') }}</dd>
                </dl>

                <x-slot:footer>
                    <a href="{{ route('admin.paiements.edit', $paiement) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                    <x-delete-button :action="route('admin.paiements.destroy', $paiement)" :label="'le paiement '.$paiement->reference" text="Supprimer" class="float-end" />
                </x-slot:footer>
            </x-admin.card>
        </div>

        <div class="col-lg-6">
            {{-- Jointure : réservation et locataire liés --}}
            @php($reservation = $paiement->reservation)
            <x-admin.card title="Réservation associée" icon="calendar-check">
                <dl class="row detail-list mb-0">
                    <dt class="col-sm-5">Réservation</dt>
                    <dd class="col-sm-7"><a href="{{ route('admin.reservations.show', $reservation) }}">N°{{ $reservation->id }}</a>
                        <span class="badge text-bg-{{ $reservation->statut_couleur }} ms-1">{{ $reservation->statut_label }}</span></dd>
                    <dt class="col-sm-5">Équipement</dt>
                    <dd class="col-sm-7">{{ $reservation->equipement->nom }}</dd>
                    <dt class="col-sm-5">Locataire</dt>
                    <dd class="col-sm-7">{{ $reservation->locataire->name }}</dd>
                    <dt class="col-sm-5">Période</dt>
                    <dd class="col-sm-7">{{ $reservation->periode }}</dd>
                    <dt class="col-sm-5">Montant total</dt>
                    <dd class="col-sm-7">{{ number_format($reservation->montant_total, 2, ',', ' ') }} DT</dd>
                    <dt class="col-sm-5">Reste à payer</dt>
                    <dd class="col-sm-7 fw-semibold">{{ number_format($reservation->reste_a_payer, 2, ',', ' ') }} DT</dd>
                </dl>
            </x-admin.card>
        </div>
    </div>
@endsection
