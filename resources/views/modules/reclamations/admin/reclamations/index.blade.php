@extends('layouts.admin')

@section('title', 'Réclamations')

@section('breadcrumb')
    <li class="breadcrumb-item active">Réclamations</li>
@endsection

@section('content')
    {{-- Compteurs par statut (filtres rapides) --}}
    <div class="row">
        @foreach (\App\Models\Reclamation::STATUTS as $valeur => [$libelle, $couleur])
            <div class="col-6 col-lg-3">
                <a href="{{ route('admin.reclamations.index', ['statut' => $valeur]) }}"
                   class="text-decoration-none text-reset">
                    <div class="info-box {{ request('statut') === $valeur ? 'border border-2 border-'.$couleur : '' }}">
                        <span class="info-box-icon text-bg-{{ $couleur }} shadow-sm">
                            <i class="bi bi-flag-fill"></i>
                        </span>

                        <div class="info-box-content">
                            <span class="info-box-text">{{ $libelle }}</span>
                            <span class="info-box-number">{{ $compteurs[$valeur] ?? 0 }}</span>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    <x-admin.card title="Liste des réclamations"
                  icon="exclamation-diamond"
                  body-class="p-0">

        <x-slot:tools>
            <a href="{{ route('admin.reclamations.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                Nouvelle réclamation
            </a>
        </x-slot:tools>

        {{-- Filtres --}}
        <form method="GET" class="row g-2 p-3 border-bottom">

            <div class="col-md-4">
                <label for="q" class="visually-hidden">
                    Rechercher par sujet ou membre
                </label>

                <input
                    id="q"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    class="form-control"
                    placeholder="Sujet ou membre..."
                >
            </div>

            <div class="col-md-2">
                <label for="statut" class="visually-hidden">
                    Statut
                </label>

                <select id="statut"
                        name="statut"
                        class="form-select">

                    <option value="">Tous statuts</option>

                    @foreach (\App\Models\Reclamation::STATUTS as $valeur => [$libelle, $couleur])
                        <option
                            value="{{ $valeur }}"
                            @selected(request('statut') === $valeur)
                        >
                            {{ $libelle }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="priorite" class="visually-hidden">
                    Priorité
                </label>

                <select id="priorite"
                        name="priorite"
                        class="form-select">

                    <option value="">Toutes priorités</option>

                    @foreach (\App\Models\Reclamation::PRIORITES as $valeur => [$libelle, $couleur])
                        <option
                            value="{{ $valeur }}"
                            @selected(request('priorite') === $valeur)
                        >
                            {{ $libelle }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="categorie" class="visually-hidden">
                    Catégorie
                </label>

                <select id="categorie"
                        name="categorie"
                        class="form-select">

                    <option value="">Toutes catégories</option>

                    @foreach (\App\Models\Reclamation::CATEGORIES as $valeur => [$libelle, $icone])
                        <option
                            value="{{ $valeur }}"
                            @selected(request('categorie') === $valeur)
                        >
                            {{ $libelle }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary"
                        type="submit">
                    <i class="bi bi-funnel me-1"></i>
                    Filtrer
                </button>
            </div>

        </form>

        @if ($reclamations->isEmpty())

            <x-empty-state
                icon="emoji-smile"
                message="Aucune réclamation trouvée."
            />

        @else

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Sujet</th>
                            <th>Membre</th>
                            <th>Catégorie</th>
                            <th>Priorité</th>
                            <th>Statut</th>
                            <th class="text-center">Réponses</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($reclamations as $reclamation)

                            <tr>
                                <td>
                                    {{ $reclamation->id }}
                                </td>

                                <td>
                                    <a
                                        href="{{ route('admin.reclamations.show', $reclamation) }}"
                                        class="fw-semibold"
                                    >
                                        {{ \Illuminate\Support\Str::limit($reclamation->sujet, 40) }}
                                    </a>

                                    @if ($reclamation->piece_jointe)
                                        <i
                                            class="bi bi-paperclip text-muted"
                                            title="Pièce jointe"
                                        ></i>
                                    @endif
                                </td>

                                <td>
                                    {{ $reclamation->auteur->name }}
                                </td>

                                <td>
                                    <i class="bi bi-{{ $reclamation->categorie_icone }} me-1"></i>
                                    {{ $reclamation->categorie_label }}
                                </td>

                                <td>
                                    <span class="badge text-bg-{{ $reclamation->priorite_couleur }}">
                                        {{ $reclamation->priorite_label }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge text-bg-{{ $reclamation->statut_couleur }}">
                                        {{ $reclamation->statut_label }}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <span class="badge rounded-pill text-bg-light border">
                                        {{ $reclamation->reponses_count }}
                                    </span>
                                </td>

                                <td class="small">
                                    {{ $reclamation->created_at->format('d/m/Y') }}
                                </td>

                                <td class="actions text-end">

                                    <a
                                        href="{{ route('admin.reclamations.show', $reclamation) }}"
                                        class="btn btn-sm btn-outline-info"
                                        title="Voir / répondre"
                                    >
                                        <i class="bi bi-chat-dots"></i>
                                    </a>

                                    <a
                                        href="{{ route('admin.reclamations.edit', $reclamation) }}"
                                        class="btn btn-sm btn-outline-warning"
                                        title="Modifier"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <x-delete-button
                                        :action="route('admin.reclamations.destroy', $reclamation)"
                                        :label="'la réclamation n°'.$reclamation->id"
                                    />

                                </td>
                            </tr>

                        @endforeach
                    </tbody>

                </table>
            </div>

        @endif

        <x-slot:footer>
            {{ $reclamations->links() }}
        </x-slot:footer>

    </x-admin.card>
@endsection
