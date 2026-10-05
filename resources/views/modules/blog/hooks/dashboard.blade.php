<div class="col-12 col-sm-6 col-lg-3">
    <a href="{{ route('admin.articles.index') }}" class="text-decoration-none text-reset">
        <div class="info-box">
            <span class="info-box-icon text-bg-info shadow-sm"><i class="bi bi-journal-richtext"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Articles publiés</span>
                <span class="info-box-number">
                    {{ \App\Models\Article::where('statut', 'publie')->count() }}
                    <small class="text-muted fw-normal">· {{ \App\Models\Commentaire::where('approuve', false)->count() }} commentaire(s) à modérer</small>
                </span>
            </div>
        </div>
    </a>
</div>
