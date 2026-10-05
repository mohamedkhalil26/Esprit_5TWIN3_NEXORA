@extends('layouts.admin')

@section('title', 'Réponses aux réclamations')

@section('breadcrumb')
    <li class="breadcrumb-item active">Réponses</li>
@endsection

@section('content')

    <x-admin.card
        title="Toutes les réponses"
        icon="reply-all"
        body-class="p-0">

        <x-slot:tools>
            <a href="{{ route('admin.reponses.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                Nouvelle réponse
            </a>
        </x-slot:tools>


        {{-- Filtres --}}
        <form method="GET" class="row g-2 p-3 border-bottom">

            <div class="col-md-5">

                <label for="q" class="visually-hidden">
                    Rechercher dans les messages
                </label>

                <input
                    id="q"
                    type="search"
                    name="q"
                    value="{{ request('q') }}"
                    class="form-control"
                    placeholder="Rechercher dans les messages..."
                >

            </div>


            <div class="col-md-5">

                <label for="reclamation" class="visually-hidden">
                    Filtrer par réclamation
                </label>

                <select
                    id="reclamation"
                    name="reclamation"
                    class="form-select">

                    <option value="">
                        Toutes les réclamations
                    </option>

                    @foreach ($reclamations as $reclamation)

                        <option
                            value="{{ $reclamation->id }}"
                            @selected(request('reclamation') == $reclamation->id)
                        >
                            N°{{ $reclamation->id }} — {{ $reclamation->sujet }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="col-md-2 d-grid">

                <button
                    class="btn btn-outline-primary"
                    type="submit">

                    <i class="bi bi-funnel me-1"></i>
                    Filtrer

                </button>

            </div>

        </form>


        @if ($reponses->isEmpty())

            <x-empty-state
                icon="chat"
                message="Aucune réponse trouvée."
            />

        @else

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Réclamation</th>
                            <th>Auteur</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($reponses as $reponse)

                            <tr>

                                <td>
                                    {{ $reponse->id }}
                                </td>


                                <td>

                                    <a href="{{ route('admin.reclamations.show', $reponse->reclamation) }}">
                                        N°{{ $reponse->reclamation->id }}
                                        —
                                        {{ \Illuminate\Support\Str::limit($reponse->reclamation->sujet, 30) }}
                                    </a>

                                </td>


                                <td>

                                    {{ $reponse->auteur->name }}

                                    @if ($reponse->auteur->isAdmin())
                                        <span class="badge text-bg-warning ms-1">
                                            admin
                                        </span>
                                    @endif

                                </td>


                                <td class="small text-muted">

                                    {{ \Illuminate\Support\Str::limit($reponse->message, 60) }}

                                </td>


                                <td class="small">

                                    {{ $reponse->created_at->format('d/m/Y H:i') }}

                                </td>


                                <td class="actions text-end">

                                    <a
                                        href="{{ route('admin.reponses.show', $reponse) }}"
                                        class="btn btn-sm btn-outline-info"
                                        title="Voir">

                                        <i class="bi bi-eye"></i>

                                    </a>


                                    <a
                                        href="{{ route('admin.reponses.edit', $reponse) }}"
                                        class="btn btn-sm btn-outline-warning"
                                        title="Modifier">

                                        <i class="bi bi-pencil"></i>

                                    </a>


                                    <x-delete-button
                                        :action="route('admin.reponses.destroy', $reponse)"
                                        :label="'la réponse n°'.$reponse->id"
                                    />

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @endif


        <x-slot:footer>
            {{ $reponses->links() }}
        </x-slot:footer>

    </x-admin.card>

@endsection