@extends('layouts.admin')

@section('title', 'Nouvelle catégorie')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">Catégories</a></li>
    <li class="breadcrumb-item active">Nouvelle</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.categories.store') }}" novalidate>
        @csrf
        <x-admin.card title="Informations de la catégorie" icon="tag">
            @include('modules.equipements.admin.categories._form')

            <x-slot:footer>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary float-end"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
