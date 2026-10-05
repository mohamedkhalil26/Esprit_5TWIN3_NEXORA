@extends('layouts.front')

@section('title', 'Mes réclamations')
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Mes réclamations',
        'subtitle' => 'Un souci avec une location ? Notre équipe est là pour vous aider',
        'breadcrumb' => ['Mes réclamations' => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('front.reclamations.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nouvelle réclamation</a>
            </div>

            @if ($reclamations->isEmpty())
                <x-empty-state icon="emoji-smile" message="Vous n'avez déposé aucune réclamation. Tout va bien !" />
            @else
                <div class="row g-3">
                    @foreach ($reclamations as $reclamation)
                        <div class="col-md-6">
                            <a href="{{ route('front.reclamations.show', $reclamation) }}" class="text-decoration-none text-reset">
                                <div class="card card-ss h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="small text-muted"><i class="bi bi-{{ $reclamation->categorie_icone }} me-1"></i>{{ $reclamation->categorie_label }}</span>
                                            <span class="badge text-bg-{{ $reclamation->statut_couleur }}">{{ $reclamation->statut_label }}</span>
                                        </div>
                                        <h5 class="mb-2">{{ $reclamation->sujet }}</h5>
                                        <p class="small text-muted mb-3">{{ \Illuminate\Support\Str::limit($reclamation->description, 110) }}</p>
                                        <div class="d-flex justify-content-between small text-muted">
                                            <span><i class="bi bi-clock me-1"></i>{{ $reclamation->created_at->diffForHumans() }}</span>
                                            <span><i class="bi bi-chat-dots me-1"></i>{{ $reclamation->reponses_count }} réponse(s)</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 d-flex justify-content-center">{{ $reclamations->links() }}</div>
            @endif
        </div>
    </section>
@endsection
