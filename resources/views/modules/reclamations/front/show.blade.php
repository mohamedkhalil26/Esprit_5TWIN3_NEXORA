@extends('layouts.front')

@section('title', 'Réclamation n°'.$reclamation->id)
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => $reclamation->sujet,
        'subtitle' => 'Réclamation n°'.$reclamation->id.' · '.$reclamation->categorie_label,
        'breadcrumb' => ['Mes réclamations' => route('front.reclamations.index'), 'N°'.$reclamation->id => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card form-card mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted small">Déposée le {{ $reclamation->created_at->translatedFormat('d F Y à H:i') }}</span>
                                <span>
                                    <span class="badge text-bg-{{ $reclamation->priorite_couleur }}">Priorité {{ strtolower($reclamation->priorite_label) }}</span>
                                    <span class="badge text-bg-{{ $reclamation->statut_couleur }}">{{ $reclamation->statut_label }}</span>
                                </span>
                            </div>
                            <p class="mb-0">{!! nl2br(e($reclamation->description)) !!}</p>
                            @if ($reclamation->piece_jointe)
                                <a href="{{ $reclamation->piece_jointe_url }}" target="_blank" class="btn btn-sm btn-outline-secondary mt-3"><i class="bi bi-paperclip"></i> Pièce jointe</a>
                            @endif
                        </div>
                    </div>

                    <h5 class="mb-3"><i class="bi bi-chat-dots me-1"></i>Échanges avec l'équipe SolarShare</h5>
                    @forelse ($reclamation->reponses as $reponse)
                        @php($equipe = $reponse->auteur->isAdmin())
                        <div class="d-flex mb-3 {{ $equipe ? '' : 'justify-content-end' }}">
                            <div class="p-3 rounded-3 shadow-sm {{ $equipe ? 'bg-white' : 'bg-primary text-white' }}" style="max-width: 80%">
                                <div class="small fw-bold mb-1">
                                    {{ $equipe ? 'Équipe SolarShare' : 'Vous' }}
                                    <span class="fw-normal opacity-75">· {{ $reponse->created_at->diffForHumans() }}</span>
                                </div>
                                {!! nl2br(e($reponse->message)) !!}
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">Notre équipe n'a pas encore répondu. Vous serez notifié dès qu'une réponse sera publiée.</p>
                    @endforelse

                    @unless ($reclamation->estFermee())
                        <form method="POST" action="{{ route('front.reclamations.repondre', $reclamation) }}" class="card form-card mt-4" novalidate>
                            @csrf
                            <div class="card-body p-4">
                                <label for="message" class="form-label">Ajouter un message</label>
                                <textarea id="message" name="message" rows="3" class="form-control @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-send me-1"></i>Envoyer</button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-secondary mt-4"><i class="bi bi-lock me-1"></i>Cette réclamation est fermée.</div>
                    @endunless
                </div>
            </div>
        </div>
    </section>
@include('modules.reclamations.partials.history')
@endsection
