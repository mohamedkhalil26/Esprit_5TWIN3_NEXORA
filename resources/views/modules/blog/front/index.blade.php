@extends('layouts.front')

@section('title', 'Blog & conseils')
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Blog & conseils énergie',
        'subtitle' => 'Guides pratiques, retours d\'expérience et actualités du renouvelable',
        'breadcrumb' => ['Blog' => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                <div class="col-lg-8">
                    {{-- Filtres par catégorie --}}
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <a href="{{ route('front.blog.index', request()->except(['categorie', 'page'])) }}"
                           class="btn btn-sm {{ request('categorie') ? 'btn-outline-primary' : 'btn-primary' }}">Tous</a>
                        @foreach (\App\Models\Article::CATEGORIES as $valeur => [$libelle, $icone])
                            <a href="{{ route('front.blog.index', [...request()->except(['categorie', 'page']), 'categorie' => $valeur]) }}"
                               class="btn btn-sm {{ request('categorie') === $valeur ? 'btn-primary' : 'btn-outline-primary' }}">
                                <i class="bi bi-{{ $icone }} me-1"></i>{{ $libelle }}
                            </a>
                        @endforeach
                    </div>

                    @if ($articles->isEmpty())
                        <x-empty-state icon="journal-x" message="Aucun article ne correspond à votre recherche." />
                    @else
                        <div class="row g-4">
                            @foreach ($articles as $article)
                                <div class="col-md-6">
                                    @include('modules.blog.front._card')
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 d-flex justify-content-center">{{ $articles->links() }}</div>
                    @endif
                </div>

                <aside class="col-lg-4">
                    <form method="GET" class="card form-card mb-4">
                        <div class="card-body">
                            <h6 class="text-uppercase text-muted small">Rechercher</h6>
                            <div class="input-group">
                                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Mot-clé...">
                                @if (request('categorie'))<input type="hidden" name="categorie" value="{{ request('categorie') }}">@endif
                                <button class="btn btn-primary"><i class="bi bi-search"></i></button>
                            </div>
                        </div>
                    </form>

                    <div class="card form-card">
                        <div class="card-body">
                            <h6 class="text-uppercase text-muted small mb-3">Les plus lus</h6>
                            @foreach ($populaires as $index => $populaire)
                                <a href="{{ route('front.blog.show', $populaire) }}" class="d-flex text-decoration-none text-reset mb-3">
                                    <span class="fs-3 fw-bold text-sun me-3 lh-1">{{ $index + 1 }}</span>
                                    <span>
                                        <span class="d-block fw-semibold small">{{ $populaire->titre }}</span>
                                        <span class="small text-muted">{{ $populaire->vues }} lectures</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection
