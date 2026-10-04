@extends('layouts.admin')

@section('title', 'Modifier la réclamation n°'.$reclamation->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reclamations.index') }}">Réclamations</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reclamations.show', $reclamation) }}">N°{{ $reclamation->id }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.reclamations.update', $reclamation) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        <x-admin.card title="Modifier la réclamation" icon="pencil-square" outline="warning">
            @include('modules.reclamations.admin.reclamations._form')

            <x-slot:footer>
                <a href="{{ route('admin.reclamations.show', $reclamation) }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-warning float-end"><i class="bi bi-save me-1"></i>Mettre à jour</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
