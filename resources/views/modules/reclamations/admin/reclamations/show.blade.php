@extends('layouts.admin')

@section('title', 'Réclamation n°'.$reclamation->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reclamations.index') }}">Réclamations</a></li>
    <li class="breadcrumb-item active">N°{{ $reclamation->id }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5">
            <x-admin.card title="Détail" icon="exclamation-diamond">
                <x-slot:tools>
                    <span class="badge text-bg-{{ $reclamation->statut_couleur }}">{{ $reclamation->statut_label }}</span>
                </x-slot:tools>

                <h5>{{ $reclamation->sujet }}</h5>
                <p class="text-muted">{!! nl2br(e($reclamation->description)) !!}</p>

                <dl class="row detail-list mb-0">
                    <dt class="col-sm-5">Membre</dt>
                    <dd class="col-sm-7">{{ $reclamation->auteur->name }}<div class="small text-muted">{{ $reclamation->auteur->email }}</div></dd>
                    <dt class="col-sm-5">Catégorie</dt>
                    <dd class="col-sm-7"><i class="bi bi-{{ $reclamation->categorie_icone }} me-1"></i>{{ $reclamation->categorie_label }}</dd>
                    <dt class="col-sm-5">Priorité</dt>
                    <dd class="col-sm-7"><span class="badge text-bg-{{ $reclamation->priorite_couleur }}">{{ $reclamation->priorite_label }}</span></dd>
                    <dt class="col-sm-5">Déposée le</dt>
                    <dd class="col-sm-7">{{ $reclamation->created_at->format('d/m/Y à H:i') }}</dd>
                    @if ($reclamation->piece_jointe)
                        <dt class="col-sm-5">Pièce jointe</dt>
                        <dd class="col-sm-7"><a href="{{ $reclamation->piece_jointe_url }}" target="_blank"><i class="bi bi-paperclip"></i> Ouvrir</a></dd>
                    @endif
                </dl>

                <x-slot:footer>
                    <a href="{{ route('admin.reclamations.edit', $reclamation) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                    <x-delete-button :action="route('admin.reclamations.destroy', $reclamation)" :label="'la réclamation n°'.$reclamation->id" text="Supprimer" class="float-end" />
                </x-slot:footer>
            </x-admin.card>
        </div>

        <div class="col-lg-7">
            {{-- Jointure 1-N : fil des réponses --}}
            <x-admin.card title="Échanges ({{ $reclamation->reponses->count() }})" icon="chat-dots">
                @forelse ($reclamation->reponses as $reponse)
                    @php($deLAdmin = $reponse->auteur->isAdmin())
                    <div class="d-flex mb-3 {{ $deLAdmin ? 'flex-row-reverse text-end' : '' }}">
                        <span class="avatar flex-shrink-0 {{ $deLAdmin ? 'ms-2' : 'me-2' }}" style="{{ $deLAdmin ? 'background:#f6b819;color:#2b2100' : '' }}">{{ $reponse->auteur->initiales }}</span>
                        <div class="p-3 rounded {{ $deLAdmin ? 'bg-success-subtle' : 'bg-body-secondary' }}" style="max-width: 80%">
                            <div class="small fw-semibold mb-1">
                                {{ $reponse->auteur->name }} · <span class="text-muted fw-normal">{{ $reponse->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="text-start">{!! nl2br(e($reponse->message)) !!}</div>
                            <div class="mt-2">
                                <a href="{{ route('admin.reponses.edit', $reponse) }}" class="btn btn-xs btn-link p-0 me-2 small">Modifier</a>
                                <x-delete-button :action="route('admin.reponses.destroy', $reponse)" label="cette réponse" class="btn-link p-0 border-0 small" size="xs" />
                            </div>
                        </div>
                    </div>
                @empty
                    <x-empty-state icon="chat" message="Aucune réponse pour le moment." class="py-3" />
                @endforelse

                <x-slot:footer>
                    <form method="POST" action="{{ route('admin.reponses.store') }}" novalidate>
                        @csrf
                        <input type="hidden" name="reclamation_id" value="{{ $reclamation->id }}">
                        <div class="mb-2">
                            <textarea name="message" rows="3" class="form-control @error('message') is-invalid @enderror"
                                      placeholder="Écrire une réponse au membre...">{{ old('message') }}</textarea>
                            @error('message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <select name="nouveau_statut" class="form-select form-select-sm w-auto">
                                <option value="">Statut inchangé</option>
                                @foreach (\App\Models\Reclamation::STATUTS as $valeur => [$libelle, $couleur])
                                    <option value="{{ $valeur }}" @selected(old('nouveau_statut') === $valeur)>Passer à : {{ $libelle }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm ms-auto"><i class="bi bi-send me-1"></i>Répondre</button>
                        </div>
                    </form>
                </x-slot:footer>
            </x-admin.card>
        </div>
    </div>
@endsection
