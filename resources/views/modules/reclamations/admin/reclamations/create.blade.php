@extends('layouts.admin')

@section('title', 'Nouvelle réclamation')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reclamations.index') }}">Réclamations</a></li> <!-- NOSONAR -->
    <li class="breadcrumb-item active">Nouvelle</li> <!-- NOSONAR -->
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.reclamations.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <x-admin.card title="Informations de la réclamation" icon="exclamation-diamond">
            @include('modules.reclamations.admin.reclamations._form')

            <x-slot:footer>
                <a href="{{ route('admin.reclamations.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary float-end"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
