<div class="col-12 col-sm-6 col-lg-3">
    <a href="{{ route('admin.reservations.index') }}" class="text-decoration-none text-reset">
        <div class="info-box">
            <span class="info-box-icon text-bg-primary shadow-sm"><i class="bi bi-calendar-check-fill"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Réservations</span>
                <span class="info-box-number">
                    {{ \App\Models\Reservation::count() }}
                    <small class="text-muted fw-normal">dont {{ \App\Models\Reservation::where('statut', 'en_attente')->count() }} en attente</small>
                </span>
            </div>
        </div>
    </a>
</div>
<div class="col-12 col-sm-6 col-lg-3">
    <a href="{{ route('admin.paiements.index') }}" class="text-decoration-none text-reset">
        <div class="info-box">
            <span class="info-box-icon text-bg-success shadow-sm"><i class="bi bi-cash-stack"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Revenus encaissés</span>
                <span class="info-box-number">{{ number_format(\App\Models\Paiement::where('statut', 'valide')->sum('montant'), 2, ',', ' ') }} DT</span>
            </div>
        </div>
    </a>
</div>
