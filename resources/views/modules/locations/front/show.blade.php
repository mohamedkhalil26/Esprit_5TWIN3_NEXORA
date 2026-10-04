@extends('layouts.front')

@section('title', 'Réservation n°'.$reservation->id)
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Réservation n°'.$reservation->id,
        'subtitle' => $reservation->equipement->nom,
        'breadcrumb' => ['Mes réservations' => route('front.reservations.index'), 'N°'.$reservation->id => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card form-card">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="mb-0">Récapitulatif</h4>
                                <span class="badge fs-6 text-bg-{{ $reservation->statut_couleur }}">{{ $reservation->statut_label }}</span>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between"><span>Équipement</span><strong>{{ $reservation->equipement->nom }}</strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Propriétaire</span><span>{{ $reservation->equipement->proprietaire->name }}</span></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Période</span><span>{{ $reservation->periode }}</span></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Durée</span><span>{{ $reservation->nb_jours }} jour(s)</span></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Montant total</span><strong class="text-primary">{{ number_format($reservation->montant_total, 2, ',', ' ') }} DT</strong></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Déjà payé</span><span>{{ number_format($reservation->montant_paye, 2, ',', ' ') }} DT</span></li>
                                <li class="list-group-item d-flex justify-content-between"><span>Reste à payer</span><strong>{{ number_format($reservation->reste_a_payer, 2, ',', ' ') }} DT</strong></li>
                            </ul>
                            @if ($reservation->message)
                                <p class="fst-italic text-muted mt-3 mb-0">« {{ $reservation->message }} »</p>
                            @endif
                        </div>
                        @if (in_array($reservation->statut, ['en_attente', 'confirmee'], true))
                            <div class="card-footer bg-white p-4">
                                <form method="POST" action="{{ route('front.reservations.annuler', $reservation) }}"
                                      onsubmit="return confirm('Voulez-vous vraiment annuler cette réservation ?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Annuler ma réservation</button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card form-card">
                        <div class="card-body p-4">
                            <h5 class="mb-3"><i class="bi bi-credit-card me-1"></i>Mes paiements</h5>
                            @forelse ($reservation->paiements as $paiement)
                                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                    <div>
                                        <div class="fw-semibold">{{ number_format($paiement->montant, 2, ',', ' ') }} DT</div>
                                        <small class="text-muted">{{ $paiement->methode_label }} · {{ $paiement->date_paiement->format('d/m/Y') }}</small>
                                    </div>
                                    <span class="badge text-bg-{{ $paiement->statut_couleur }}">{{ $paiement->statut_label }}</span>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">Aucun paiement enregistré. Le propriétaire vous contactera après confirmation.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
