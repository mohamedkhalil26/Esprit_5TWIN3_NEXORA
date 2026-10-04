@extends('layouts.admin')

@section('title', 'Modifier la catégorie')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Catégories</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.show', $categorie) }}">{{ $categorie->nom }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.categories.update', $categorie) }}" novalidate>
        @csrf
        @method('PUT')
        <x-admin.card :title="'Modifier « '.$categorie->nom.' »'" icon="pencil-square" outline="warning">
            @include('modules.equipements.admin.categories._form')

            <x-slot:footer>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-warning float-end"><i class="bi bi-save me-1"></i>Mettre à jour</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
