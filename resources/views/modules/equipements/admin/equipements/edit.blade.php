@extends('layouts.admin')

@section('title', 'Modifier l\'équipement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.equipements.index') }}">Équipements</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.equipements.show', $equipement) }}">{{ $equipement->nom }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.equipements.update', $equipement) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        <x-admin.card :title="'Modifier « '.$equipement->nom.' »'" icon="pencil-square" outline="warning">
            @include('modules.equipements.admin.equipements._form')

            <x-slot:footer>
                <a href="{{ route('admin.equipements.show', $equipement) }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-warning float-end"><i class="bi bi-save me-1"></i>Mettre à jour</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
