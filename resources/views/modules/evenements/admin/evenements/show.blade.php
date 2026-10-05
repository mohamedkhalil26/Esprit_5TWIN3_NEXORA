@extends('layouts.admin')

@section('title', $evenement->titre)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.evenements.index') }}">Événements</a></li>
    <li class="breadcrumb-item active">{{ \Illuminate\Support\Str::limit($evenement->titre, 30) }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5">
            <x-admin.card title="Détail de l'événement" icon="calendar-event">
                <x-slot:tools>
                    <span class="badge text-bg-{{ $evenement->statut_couleur }}">{{ $evenement->statut_label }}</span>
                </x-slot:tools>

                @if ($evenement->image)
                    <img src="{{ $evenement->image_url }}" alt="" class="img-fluid rounded mb-3">
                @endif

                <p class="text-muted">{!! nl2br(e($evenement->description)) !!}</p>

                <dl class="row detail-list mb-0">
                    <dt class="col-sm-4">Type</dt>
                    <dd class="col-sm-8"><i class="bi bi-{{ $evenement->type_icone }} me-1"></i>{{ $evenement->type_label }}</dd>
                    <dt class="col-sm-4">Date</dt>
                    <dd class="col-sm-8">{{ $evenement->date_debut->translatedFormat('l d F Y') }}<br>{{ $evenement->date_debut->format('H:i') }} → {{ $evenement->date_fin->format('H:i') }}</dd>
                    <dt class="col-sm-4">Lieu</dt>
                    <dd class="col-sm-8">{{ $evenement->lieu }}, {{ $evenement->ville }}</dd>
                    <dt class="col-sm-4">Prix</dt>
                    <dd class="col-sm-8">{{ $evenement->prix_label }}</dd>
                    <dt class="col-sm-4">Places</dt>
                    <dd class="col-sm-8">{{ $evenement->nb_inscrits }} inscrits / {{ $evenement->capacite }} ({{ $evenement->places_restantes }} restantes)</dd>
                </dl>

                <x-slot:footer>
                    <a href="{{ route('admin.evenements.edit', $evenement) }}" class="btn btn-warning btn-sm"><i class="bi bi-pencil me-1"></i>Modifier</a>
                    <a href="{{ route('front.evenements.show', $evenement) }}" target="_blank" class="btn btn-outline-primary btn-sm"><i class="bi bi-globe"></i></a>
                    <x-delete-button :action="route('admin.evenements.destroy', $evenement)" :label="$evenement->titre" text="Supprimer" class="float-end" />
                </x-slot:footer>
            </x-admin.card>
        </div>

        <div class="col-lg-7">
            {{-- Relation N-N : membres inscrits (données du pivot "participations") --}}
            <x-admin.card title="Participants ({{ $participants->count() }})" icon="people" body-class="p-0">
                <x-slot:tools>
                    <a href="{{ route('admin.participations.create', ['evenement' => $evenement->id]) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-person-plus me-1"></i>Inscrire un membre
                    </a>
                </x-slot:tools>

                @if ($participants->isEmpty())
                    <x-empty-state icon="people" message="Aucun inscrit pour le moment." />
                @else
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Membre</th>
                            <th>Statut</th>
                            <th>Inscrit le</th>
                            <th class="text-end"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($participants as $membre)
                            @php([$libelle, $couleur] = \App\Models\Participation::STATUTS[$membre->pivot->statut])
                            <tr>
                                <td>
                                    <span class="avatar me-2">{{ $membre->initiales }}</span>{{ $membre->name }}
                                    @if ($membre->pivot->commentaire)
                                        <div class="small text-muted fst-italic ms-5">« {{ $membre->pivot->commentaire }} »</div>
                                    @endif
                                </td>
                                <td><span class="badge text-bg-{{ $couleur }}">{{ $libelle }}</span></td>
                                <td class="small">{{ $membre->pivot->created_at->format('d/m/Y') }}</td>
                                <td class="actions text-end">
                                    <a href="{{ route('admin.participations.edit', $membre->pivot->id) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                    <x-delete-button :action="route('admin.participations.destroy', $membre->pivot->id)" :label="'l\'inscription de '.$membre->name" />
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </x-admin.card>
        </div>
    </div>
@endsection
