{{-- Inclus automatiquement dans la fiche équipement du module 1 --}}
@if (! $equipement->disponible)
    <button class="btn btn-secondary btn-lg w-100" disabled><i class="bi bi-x-circle me-1"></i>Indisponible pour le moment</button>
@elseif (auth()->check() && auth()->id() === (int) $equipement->user_id)
    <div class="alert alert-info mb-0 small"><i class="bi bi-info-circle me-1"></i>C'est votre équipement.</div>
@else
    <a href="{{ route('front.reservations.create', ['equipement' => $equipement->id]) }}" class="btn btn-primary btn-lg w-100">
        <i class="bi bi-calendar-plus me-1"></i>Réserver cet équipement
    </a>
    @guest
        <p class="small text-muted text-center mt-2 mb-0">Vous devrez vous connecter pour finaliser la réservation.</p>
    @endguest
@endif
