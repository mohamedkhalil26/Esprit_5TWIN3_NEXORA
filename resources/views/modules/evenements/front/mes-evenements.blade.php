@extends('layouts.front')

@section('title', 'Mes événements')
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Mes événements',
        'subtitle' => 'Les ateliers et rencontres auxquels vous êtes inscrit(e)',
        'breadcrumb' => ['Mes événements' => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            @if ($participations->isEmpty())
                <x-empty-state icon="calendar" message="Vous n'êtes inscrit(e) à aucun événement.">
                    <a href="{{ route('front.evenements.index') }}" class="btn btn-primary">Voir les événements à venir</a>
                </x-empty-state>
            @else
                <div class="card form-card">
                    <div class="list-group list-group-flush">
                        @foreach ($participations as $participation)
                            <a href="{{ route('front.evenements.show', $participation->evenement) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <div class="fw-semibold">{{ $participation->evenement->titre }}</div>
                                    <small class="text-muted">{{ $participation->evenement->date_debut->translatedFormat('d F Y à H:i') }} · {{ $participation->evenement->ville }}</small>
                                </div>
                                <span class="badge text-bg-{{ $participation->statut_couleur }}">{{ $participation->statut_label }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="mt-4 d-flex justify-content-center">{{ $participations->links() }}</div>
            @endif
        </div>
    </section>
@endsection
