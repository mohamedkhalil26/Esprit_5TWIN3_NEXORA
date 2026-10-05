@extends('layouts.front')

@section('title', $evenement->titre)
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => $evenement->titre,
        'subtitle' => $evenement->type_label.' · '.$evenement->date_debut->translatedFormat('l d F Y'),
        'breadcrumb' => ['Événements' => route('front.evenements.index'), \Illuminate\Support\Str::limit($evenement->titre, 30) => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card form-card">
                        @if ($evenement->image)
                            <img src="{{ $evenement->image_url }}" class="card-img-top" style="max-height: 360px; object-fit: cover" alt="">
                        @endif
                        <div class="card-body p-4">
                            <h4 class="mb-3">Au programme</h4>
                            <p class="text-muted mb-0">{!! nl2br(e($evenement->description)) !!}</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card form-card">
                        <div class="card-body p-4">
                            <ul class="list-unstyled mb-4">
                                <li class="mb-3"><i class="bi bi-calendar3 text-primary me-2"></i>{{ $evenement->date_debut->translatedFormat('d F Y') }}</li>
                                <li class="mb-3"><i class="bi bi-clock text-primary me-2"></i>{{ $evenement->date_debut->format('H:i') }} – {{ $evenement->date_fin->format('H:i') }}</li>
                                <li class="mb-3"><i class="bi bi-geo-alt text-primary me-2"></i>{{ $evenement->lieu }}, {{ $evenement->ville }}</li>
                                <li class="mb-3"><i class="bi bi-ticket-perforated text-primary me-2"></i>{{ $evenement->prix_label }}</li>
                                <li><i class="bi bi-people text-primary me-2"></i>{{ $evenement->nb_inscrits }} inscrit(s) · {{ $evenement->places_restantes }} place(s) restante(s)</li>
                            </ul>

                            @php($taux = round($evenement->nb_inscrits / max(1, $evenement->capacite) * 100))
                            <div class="progress mb-4" style="height: 8px">
                                <div class="progress-bar bg-success" style="width: {{ min(100, $taux) }}%"></div>
                            </div>

                            @if ($maParticipation && $maParticipation->statut !== 'annule')
                                <div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i>Vous êtes inscrit(e) à cet événement.</div>
                                @if (! $evenement->est_passe)
                                    <form method="POST" action="{{ route('front.evenements.desinscrire', $evenement) }}"
                                          onsubmit="return confirm('Annuler votre inscription ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle me-1"></i>Me désinscrire</button>
                                    </form>
                                @endif
                            @elseif ($evenement->statut === 'annule')
                                <div class="alert alert-danger mb-0">Cet événement a été annulé.</div>
                            @elseif (! $evenement->inscriptionsOuvertes())
                                <button class="btn btn-secondary w-100" disabled>Inscriptions fermées</button>
                            @else
                                @auth
                                    <form method="POST" action="{{ route('front.evenements.inscrire', $evenement) }}" novalidate>
                                        @csrf
                                        <label for="commentaire" class="form-label small">Un mot pour l'organisateur (facultatif)</label>
                                        <textarea id="commentaire" name="commentaire" rows="2" maxlength="500"
                                                  class="form-control mb-3 @error('commentaire') is-invalid @enderror">{{ old('commentaire') }}</textarea>
                                        @error('commentaire')
                                            <div class="invalid-feedback mb-2">{{ $message }}</div>
                                        @enderror
                                        <button type="submit" class="btn btn-primary btn-lg w-100"><i class="bi bi-calendar-plus me-1"></i>Je m'inscris</button>
                                    </form>
                                @else
                                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg w-100">Connectez-vous pour vous inscrire</a>
                                @endauth
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
