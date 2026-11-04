@extends('layouts.front')

@section('title', $article->titre)
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => $article->titre,
        'subtitle' => $article->categorie_label.' · '.$article->publie_le?->translatedFormat('d F Y').' · '.$article->temps_lecture.' min de lecture',
        'breadcrumb' => ['Blog' => route('front.blog.index'), \Illuminate\Support\Str::limit($article->titre, 30) => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @auth
                    @php($isFavorite = auth()->user()->favoris()->where('articles.id', $article->id)->exists())
                    <form class="mb-3" method="POST" action="{{ route($isFavorite ? 'favorites.destroy' : 'favorites.store', $article) }}">@csrf
                    @if($isFavorite) @method('DELETE') @endif
                    <button class="btn btn-outline-success"><i class="bi bi-bookmark"></i> {{ $isFavorite ? 'Retirer des favoris' : 'Enregistrer dans mes favoris' }}</button></form>
                    <a class="btn btn-outline-primary mb-3" href="{{ route('ai.front.show', ['module' => 'blog', 'article_id' => $article->id]) }}"><i class="bi bi-stars me-1" aria-hidden="true"></i>Résumer ou expliquer cet article</a>
                    <a class="btn btn-outline-primary mb-3" href="{{ route('ai.quiz.show', $article) }}"><i class="bi bi-question-circle me-1" aria-hidden="true"></i>Faire le quiz</a>
                    @endauth
                    <article class="card form-card mb-5">
                        @if ($article->image)
                            <img src="{{ $article->image_url }}" class="card-img-top" style="max-height: 400px; object-fit: cover" alt="">
                        @endif
                        <div class="card-body p-4 p-md-5">
                            <div class="d-flex align-items-center mb-4">
                                <span class="step-icon me-3" style="width:3rem;height:3rem;font-size:1.2rem">{{ $article->auteur->initiales }}</span>
                                <div>
                                    <div class="fw-semibold">{{ $article->auteur->name }}</div>
                                    <small class="text-muted"><i class="bi bi-eye me-1"></i>{{ $article->vues }} lectures</small>
                                </div>
                            </div>
                            <p class="lead fw-semibold">{{ $article->resume }}</p>
                            @foreach (preg_split("/\R{2,}/", trim($article->contenu)) as $paragraphe)
                                <p style="line-height: 1.8">{{ $paragraphe }}</p>
                            @endforeach
                        </div>
                    </article>

                    {{-- Commentaires approuvés (jointure 1-N) --}}
                    <h4 class="mb-4" id="commentaires">
                        <i class="bi bi-chat-left-text me-2"></i>{{ $article->commentairesApprouves->count() }} commentaire(s)
                    </h4>

                    @auth
                        <form method="POST" action="{{ route('front.blog.commenter', $article) }}" class="card form-card mb-4" novalidate>
                            @csrf
                            <div class="card-body p-4">
                                <label for="contenu" class="form-label fw-semibold">Votre commentaire</label>
                                <textarea id="contenu" name="contenu" rows="3" maxlength="1000"
                                          class="form-control @error('contenu') is-invalid @enderror"
                                          placeholder="Partagez votre expérience ou posez une question...">{{ old('contenu') }}</textarea>
                                @error('contenu')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-send me-1"></i>Publier</button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-light border mb-4">
                            <a href="{{ route('login') }}">Connectez-vous</a> pour participer à la discussion.
                        </div>
                    @endauth

                    @foreach ($article->commentairesApprouves as $commentaire)
                        <div class="d-flex mb-3">
                            <span class="step-icon flex-shrink-0 me-3" style="width:2.5rem;height:2.5rem;font-size:.9rem">{{ $commentaire->auteur->initiales }}</span>
                            <div class="bg-white rounded-3 shadow-sm p-3 flex-grow-1">
                                <div class="small mb-1">
                                    <strong>{{ $commentaire->auteur->name }}</strong>
                                    <span class="text-muted">· {{ $commentaire->created_at->diffForHumans() }}</span>
                                </div>
                                <div>{!! nl2br(e($commentaire->contenu)) !!}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($suggestions->isNotEmpty())
                <h3 class="mt-5 mb-4 text-center">À lire aussi</h3>
                <div class="row g-4">
                    @foreach ($suggestions as $suggestion)
                        <div class="col-md-4">
                            @include('modules.blog.front._card', ['article' => $suggestion])
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
