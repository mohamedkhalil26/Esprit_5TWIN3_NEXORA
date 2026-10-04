{{-- Carte d'un équipement (catalogue, accueil, suggestions) --}}
<div class="card card-ss h-100">
    <a href="{{ route('front.equipements.show', $equipement) }}">
        @if ($equipement->image)
            <img src="{{ $equipement->image_url }}" class="card-img-top" alt="{{ $equipement->nom }}">
        @else
            <div class="placeholder-visual"><i class="bi {{ $equipement->categorie->icone }}"></i></div>
        @endif
    </a>
    <div class="card-body d-flex flex-column">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-1 mb-2">
            <span class="badge bg-sun text-dark">{{ $equipement->categorie->nom }}</span>
            <small class="text-muted"><i class="bi bi-geo-alt"></i> {{ $equipement->ville }}</small>
        </div>
        <h5 class="card-title mb-1">
            <a href="{{ route('front.equipements.show', $equipement) }}" class="text-dark text-decoration-none">{{ $equipement->nom }}</a>
        </h5>
        <p class="small text-muted mb-3">{{ $equipement->marque }}
            @if ($equipement->puissance_watts) · {{ $equipement->puissance_watts }} W @endif
            @if ($equipement->capacite_wh) · {{ $equipement->capacite_wh }} Wh @endif
        </p>
        <div class="mt-auto d-flex justify-content-between align-items-center">
            <span class="fs-5 fw-bold text-primary">{{ number_format($equipement->prix_jour, 0, ',', ' ') }} DT<small class="fs-6 text-muted fw-normal"> / jour</small></span>
            <a href="{{ route('front.equipements.show', $equipement) }}" class="btn btn-sm btn-outline-primary">Voir</a>
        </div>
    </div>
</div>
