@extends('layouts.admin')

@section('title', 'Nouvelle réservation')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
    <li class="breadcrumb-item active">Nouvelle</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.reservations.store') }}" novalidate>
        @csrf
        <x-admin.card title="Informations de la réservation" icon="calendar-plus">
            @include('modules.locations.admin.reservations._form')

            <x-slot:footer>
                <a href="{{ route('admin.reservations.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary float-end"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
