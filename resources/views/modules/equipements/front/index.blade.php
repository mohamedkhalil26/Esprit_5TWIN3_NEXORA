@extends('layouts.front')

@section('title', 'Catalogue des équipements')
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Catalogue des équipements',
        'subtitle' => 'Panneaux solaires, batteries, éoliennes... à louer près de chez vous',
        'breadcrumb' => ['Équipements' => null],
    ])

    @auth
        <div class="container px-4 px-lg-5 pt-3 text-end">
            <a class="btn btn-outline-primary btn-sm" href="{{ route('ai.front.show', 'equipements') }}"><i class="bi bi-stars me-1" aria-hidden="true"></i>Conseiller IA équipements</a>
        </div>
    @endauth

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                {{-- Filtres --}}
                <aside class="col-lg-3">
                    <form method="GET" class="card form-card">
                        <div class="card-body">
                            <h6 class="text-uppercase text-muted small mb-3">Rechercher</h6>
                            <input type="search" name="q" value="{{ request('q') }}" class="form-control mb-3" placeholder="Mot-clé...">

                            <h6 class="text-uppercase text-muted small mb-2">Catégorie</h6>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="categorie" id="cat_all" value="" @checked(! request('categorie'))>
                                    <label class="form-check-label" for="cat_all">Toutes</label>
                                </div>
                                @foreach ($categories as $categorie)
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="categorie" id="cat_{{ $categorie->id }}"
                                               value="{{ $categorie->id }}" @checked(request('categorie') == $categorie->id)>
                                        <label class="form-check-label d-flex justify-content-between" for="cat_{{ $categorie->id }}">
                                            <span><i class="bi {{ $categorie->icone }} me-1"></i>{{ $categorie->nom }}</span>
                                            <span class="badge rounded-pill bg-light text-dark">{{ $categorie->equipements_count }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>

                            <h6 class="text-uppercase text-muted small mb-2">Ville</h6>
                            <input type="text" name="ville" value="{{ request('ville') }}" class="form-control mb-3" placeholder="Ex. Tunis">

                            <h6 class="text-uppercase text-muted small mb-2">Prix maximum / jour</h6>
                            <div class="input-group mb-3">
                                <input type="number" min="1" name="prix_max" value="{{ request('prix_max') }}" class="form-control">
                                <span class="input-group-text">DT</span>
                            </div>

                            <input type="hidden" name="tri" value="{{ $tri }}">
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Filtrer</button>
                            <a href="{{ route('front.equipements.index') }}" class="btn btn-link w-100 btn-sm mt-1">Réinitialiser</a>
                        </div>
                    </form>
                </aside>

                {{-- Résultats --}}
                <div class="col-lg-9">
                    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3"><a class="btn btn-outline-primary btn-sm" href="{{ route('ai.recommendations.energy') }}"><i class="bi bi-stars me-1" aria-hidden="true"></i>{{ __('nexora.common.energy_link') }}</a>@auth<a class="btn btn-outline-primary btn-sm" href="{{ route('ai.recommendations.booking') }}"><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>{{ __('nexora.common.booking_link') }}</a>@endauth</div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">{{ $equipements->total() }} équipement(s) disponible(s)</span>
                        <form method="GET" class="d-flex align-items-center">
                            @foreach (request()->except(['tri', 'page']) as $cle => $valeur)
                                <input type="hidden" name="{{ $cle }}" value="{{ $valeur }}">
                            @endforeach
                            <label for="tri" class="small text-muted text-nowrap me-2">Trier par</label>
                            <select id="tri" name="tri" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="recent" @selected($tri === 'recent')>Plus récents</option>
                                <option value="prix_asc" @selected($tri === 'prix_asc')>Prix croissant</option>
                                <option value="prix_desc" @selected($tri === 'prix_desc')>Prix décroissant</option>
                            </select>
                        </form>
                    </div>

                    @if ($equipements->isEmpty())
                        <x-empty-state icon="search" message="Aucun équipement ne correspond à vos critères.">
                            <a href="{{ route('front.equipements.index') }}" class="btn btn-outline-primary">Voir tout le catalogue</a>
                        </x-empty-state>
                    @else
                        <div class="row g-4">
                            @foreach ($equipements as $equipement)
                                <div class="col-md-6 col-xl-4">
                                    @include('modules.equipements.front._card')
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 d-flex justify-content-center">{{ $equipements->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
