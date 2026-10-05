@extends('layouts.admin')

@section('title', 'Modifier la réponse')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reponses.index') }}">Réponses</a></li> <!-- NOSONAR -->
    <li class="breadcrumb-item"><a href="{{ route('admin.reponses.show', $reponse) }}">N°{{ $reponse->id }}</a></li> <!-- NOSONAR -->
    <li class="breadcrumb-item active">Modifier</li> <!-- NOSONAR -->
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.reponses.update', $reponse) }}" novalidate>
        @csrf
        @method('PUT')
        <x-admin.card title="Modifier la réponse" icon="pencil-square" outline="warning">
            @include('modules.reclamations.admin.reponses._form')

            <x-slot:footer>
                <a href="{{ route('admin.reponses.show', $reponse) }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-warning float-end"><i class="bi bi-save me-1"></i>Mettre à jour</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
