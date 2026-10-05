@extends('layouts.admin')

@section('title', 'Modifier l\'événement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.evenements.index') }}">Événements</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.evenements.show', $evenement) }}">{{ \Illuminate\Support\Str::limit($evenement->titre, 30) }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.evenements.update', $evenement) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        <x-admin.card :title="'Modifier « '.$evenement->titre.' »'" icon="pencil-square" outline="warning">
            @include('modules.evenements.admin.evenements._form')

            <x-slot:footer>
                <a href="{{ route('admin.evenements.show', $evenement) }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-warning float-end"><i class="bi bi-save me-1"></i>Mettre à jour</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
