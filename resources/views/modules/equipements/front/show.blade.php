@extends('layouts.front')

@section('title', $equipement->nom)
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => $equipement->nom,
        'subtitle' => $equipement->categorie->nom.' · '.$equipement->ville,
        'breadcrumb' => ['Équipements' => route('front.equipements.index'), $equipement->nom => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card form-card overflow-hidden">
                        @if ($equipement->image)
                            <img src="{{ $equipement->image_url }}" class="w-100" style="max-height: 420px; object-fit: cover" alt="{{ $equipement->nom }}">
                        @else
                            <div class="placeholder-visual" style="height: 320px; font-size: 7rem; display:flex; align-items:center; justify-content:center; color:#fff; background: linear-gradient(135deg, #2e9e5b, #1b5e3a)">
                                <i class="bi {{ $equipement->categorie->icone }}"></i>
                            </div>
                        @endif
                        <div class="card-body p-4">
                            <h4 class="mb-3">Description</h4>
                            <p class="text-muted">{!! nl2br(e($equipement->description)) !!}</p>

                            <h5 class="mt-4 mb-3">Caractéristiques</h5>
                            <div class="row text-center g-3">
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-light rounded"><i class="bi bi-lightning-charge fs-3 text-sun"></i>
                                        <div class="small text-muted">Puissance</div>
                                        <strong>{{ $equipement->puissance_watts ? $equipement->puissance_watts.' W' : '—' }}</strong></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-light rounded"><i class="bi bi-battery-full fs-3 text-sun"></i>
                                        <div class="small text-muted">Capacité</div>
                                        <strong>{{ $equipement->capacite_wh ? $equipement->capacite_wh.' Wh' : '—' }}</strong></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-light rounded"><i class="bi bi-stars fs-3 text-sun"></i>
                                        <div class="small text-muted">État</div>
                                        <strong>{{ $equipement->etat_label }}</strong></div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="p-3 bg-light rounded"><i class="bi bi-tag fs-3 text-sun"></i>
                                        <div class="small text-muted">Marque</div>
                                        <strong>{{ $equipement->marque ?: '—' }}</strong></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card form-card sticky-lg-top" style="top: 6rem">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-baseline mb-1">
                                <span class="display-6 fw-bold text-primary">{{ number_format($equipement->prix_jour, 2, ',', ' ') }} DT</span>
                                <span class="text-muted ms-2">/ jour</span>
                            </div>
                            <p class="text-muted small">Caution : {{ number_format($equipement->caution, 2, ',', ' ') }} DT (restituée au retour)</p>

                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="bi bi-geo-alt text-primary me-2"></i>{{ $equipement->ville }}</li>
                                <li class="mb-2"><i class="bi bi-person-circle text-primary me-2"></i>Proposé par <strong>{{ $equipement->proprietaire->name }}</strong></li>
                                <li class="mb-2"><i class="bi bi-calendar3 text-primary me-2"></i>En ligne depuis {{ $equipement->created_at->translatedFormat('F Y') }}</li>
                            </ul>

                            {{-- Le module "Locations" (membre 2) ajoute ici son bouton de réservation s'il est intégré --}}
                            @if (view()->exists('modules.locations.partials.bouton-reserver'))
                                @include('modules.locations.partials.bouton-reserver', ['equipement' => $equipement])
                            @else
                                <a href="mailto:{{ $equipement->proprietaire->email }}" class="btn btn-primary btn-lg w-100">
                                    <i class="bi bi-envelope me-1"></i>Contacter le propriétaire
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($similaires->isNotEmpty())
                <h3 class="mt-5 mb-4">Dans la même catégorie</h3>
                <div class="row g-4">
                    @foreach ($similaires as $similaire)
                        <div class="col-md-4">
                            @include('modules.equipements.front._card', ['equipement' => $similaire])
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
