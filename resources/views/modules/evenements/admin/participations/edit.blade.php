@extends('layouts.admin')

@section('title', 'Modifier l\'inscription')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.participations.index') }}">Participations</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.participations.show', $participation) }}">N°{{ $participation->id }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.participations.update', $participation) }}" novalidate>
        @csrf
        @method('PUT')
        <x-admin.card title="Modifier l'inscription" icon="pencil-square" outline="warning">
            @include('modules.evenements.admin.participations._form')

            <x-slot:footer>
                <a href="{{ route('admin.participations.show', $participation) }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-warning float-end"><i class="bi bi-save me-1"></i>Mettre à jour</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
