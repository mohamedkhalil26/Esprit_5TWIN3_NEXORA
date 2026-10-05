@extends('layouts.admin')

@section('title', 'Nouvelle inscription')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.participations.index') }}">Participations</a></li>
    <li class="breadcrumb-item active">Nouvelle</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.participations.store') }}" novalidate>
        @csrf
        <x-admin.card title="Inscrire un membre à un événement" icon="person-plus">
            @include('modules.evenements.admin.participations._form')

            <x-slot:footer>
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary float-end"><i class="bi bi-check-lg me-1"></i>Inscrire</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
