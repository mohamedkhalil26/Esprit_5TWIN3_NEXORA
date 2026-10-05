@extends('layouts.front')

@section('title', 'Événements')
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Ateliers & événements',
        'subtitle' => 'Apprenez, échangez et découvrez les énergies renouvelables avec la communauté',
        'breadcrumb' => ['Événements' => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <form method="GET" class="card form-card mb-4">
                <div class="card-body row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted" for="q">Rechercher</label>
                        <input type="search" id="q" name="q" value="{{ request('q') }}" class="form-control" placeholder="Titre ou lieu...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted" for="type">Type</label>
                        <select id="type" name="type" class="form-select">
                            <option value="">Tous</option>
                            @foreach (\App\Models\Evenement::TYPES as $valeur => [$libelle, $icone])
                                <option value="{{ $valeur }}" @selected(request('type') === $valeur)>{{ $libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted" for="ville">Ville</label>
                        <input type="text" id="ville" name="ville" value="{{ request('ville') }}" class="form-control">
                    </div>
                    <div class="col-md-2 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Chercher</button>
                    </div>
                </div>
            </form>

            @if ($evenements->isEmpty())
                <x-empty-state icon="calendar-x" message="Aucun événement à venir ne correspond à votre recherche." />
            @else
                <div class="row g-4">
                    @foreach ($evenements as $evenement)
                        <div class="col-md-6">
                            @include('modules.evenements.front._card')
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 d-flex justify-content-center">{{ $evenements->links() }}</div>
            @endif
        </div>
    </section>
@endsection
