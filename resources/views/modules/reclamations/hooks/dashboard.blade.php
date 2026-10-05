<div class="col-12 col-sm-6 col-lg-3">
    <a href="{{ route('admin.reclamations.index', ['statut' => 'ouverte']) }}" class="text-decoration-none text-reset">
        <div class="info-box">
            <span class="info-box-icon text-bg-danger shadow-sm"><i class="bi bi-exclamation-diamond-fill"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Réclamations ouvertes</span>
                <span class="info-box-number">
                    {{ \App\Models\Reclamation::where('statut', 'ouverte')->count() }}
                    <small class="text-muted fw-normal">sur {{ \App\Models\Reclamation::count() }}</small>
                </span>
            </div>
        </div>
    </a>
</div>
