@extends('layouts.admin')

@section('title', 'Modifier la réservation n°'.$reservation->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reservations.show', $reservation) }}">N°{{ $reservation->id }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.reservations.update', $reservation) }}" novalidate>
        @csrf
        @method('PUT')
        <x-admin.card title="Modifier la réservation" icon="pencil-square" outline="warning">
            @include('modules.locations.admin.reservations._form')

            <x-slot:footer>
                <a href="{{ route('admin.reservations.show', $reservation) }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-warning float-end"><i class="bi bi-save me-1"></i>Mettre à jour</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
