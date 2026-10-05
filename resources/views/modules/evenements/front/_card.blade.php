{{-- Carte d'un événement (liste et page d'accueil) --}}
<div class="card card-ss h-100">
    <div class="d-flex">
        <div class="bg-primary text-white text-center p-3 d-flex flex-column justify-content-center" style="min-width: 90px">
            <div class="fs-2 fw-bold lh-1">{{ $evenement->date_debut->format('d') }}</div>
            <div class="text-uppercase small">{{ $evenement->date_debut->translatedFormat('M') }}</div>
            <div class="small opacity-75">{{ $evenement->date_debut->format('H:i') }}</div>
        </div>
        <div class="card-body">
            <span class="badge bg-sun text-dark mb-2"><i class="bi bi-{{ $evenement->type_icone }} me-1"></i>{{ $evenement->type_label }}</span>
            <h5 class="card-title mb-1">
                <a href="{{ route('front.evenements.show', $evenement) }}" class="text-dark text-decoration-none">{{ $evenement->titre }}</a>
            </h5>
            <p class="small text-muted mb-2"><i class="bi bi-geo-alt"></i> {{ $evenement->lieu }}, {{ $evenement->ville }}</p>
            <div class="d-flex justify-content-between align-items-center small">
                <span class="fw-bold text-primary">{{ $evenement->prix_label }}</span>
                @if ($evenement->est_complet)
                    <span class="badge text-bg-danger">Complet</span>
                @else
                    <span class="text-muted">{{ $evenement->places_restantes }} place(s) restante(s)</span>
                @endif
            </div>
        </div>
    </div>
</div>
