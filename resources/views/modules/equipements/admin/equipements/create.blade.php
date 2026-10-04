@extends('layouts.admin')

@section('title', 'Nouvel équipement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.equipements.index') }}">Équipements</a></li>
    <li class="breadcrumb-item active">Nouveau</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.equipements.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <x-admin.card title="Informations de l'équipement" icon="lightning-charge">
            @include('modules.equipements.admin.equipements._form')

            <x-slot:footer>
                <a href="{{ route('admin.equipements.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary float-end"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
