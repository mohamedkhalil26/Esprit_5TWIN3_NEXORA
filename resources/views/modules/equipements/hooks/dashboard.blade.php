<div class="col-12 col-sm-6 col-lg-3">
    <a href="{{ route('admin.equipements.index') }}" class="text-decoration-none text-reset">
        <div class="info-box">
            <span class="info-box-icon text-bg-success shadow-sm"><i class="bi bi-sun-fill"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Équipements</span>
                <span class="info-box-number">
                    {{ \App\Models\Equipement::count() }}
                    <small class="text-muted fw-normal">dont {{ \App\Models\Equipement::disponibles()->count() }} disponibles</small>
                </span>
            </div>
        </div>
    </a>
</div>
<div class="col-12 col-sm-6 col-lg-3">
    <a href="{{ route('admin.categories.index') }}" class="text-decoration-none text-reset">
        <div class="info-box">
            <span class="info-box-icon text-bg-info shadow-sm"><i class="bi bi-tags-fill"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Catégories</span>
                <span class="info-box-number">{{ \App\Models\Categorie::count() }}</span>
            </div>
        </div>
    </a>
</div>
