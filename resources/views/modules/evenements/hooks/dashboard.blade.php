<div class="col-12 col-sm-6 col-lg-3">
    <a href="{{ route('admin.evenements.index', ['periode' => 'a_venir']) }}" class="text-decoration-none text-reset">
        <div class="info-box">
            <span class="info-box-icon text-bg-warning shadow-sm"><i class="bi bi-calendar-event-fill"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Événements à venir</span>
                <span class="info-box-number">
                    {{ \App\Models\Evenement::aVenir()->count() }}
                    <small class="text-muted fw-normal">· {{ \App\Models\Participation::where('statut', 'inscrit')->count() }} inscrits</small>
                </span>
            </div>
        </div>
    </a>
</div>
