{{-- Carte d'un article (liste du blog, accueil, suggestions) --}}
<div class="card card-ss h-100">
    <a href="{{ route('front.blog.show', $article) }}">
        @if ($article->image)
            <img src="{{ $article->image_url }}" class="card-img-top" alt="">
        @else
            <div class="placeholder-visual" style="background: linear-gradient(135deg, #f6b819, #2e9e5b)"><i class="bi bi-{{ $article->categorie_icone }}"></i></div>
        @endif
    </a>
    <div class="card-body d-flex flex-column">
        <div class="small text-muted mb-2">
            <span class="text-primary fw-semibold">{{ $article->categorie_label }}</span>
            · {{ $article->publie_le?->translatedFormat('d M Y') }} · {{ $article->temps_lecture }} min
        </div>
        <h5 class="card-title">
            <a href="{{ route('front.blog.show', $article) }}" class="text-dark text-decoration-none">{{ $article->titre }}</a>
        </h5>
        <p class="small text-muted">{{ $article->resume }}</p>
        <div class="mt-auto d-flex justify-content-between align-items-center small text-muted">
            <span><i class="bi bi-person me-1"></i>{{ $article->auteur->name }}</span>
            <span>
                <i class="bi bi-eye me-1"></i>{{ $article->vues }}
                @isset($article->commentaires_approuves_count)
                    <i class="bi bi-chat ms-2 me-1"></i>{{ $article->commentaires_approuves_count }}
                @endisset
            </span>
        </div>
    </div>
</div>
