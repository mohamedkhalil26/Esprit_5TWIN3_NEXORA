@extends('layouts.admin')

@section('title', 'Nouvel événement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.evenements.index') }}">Événements</a></li>
    <li class="breadcrumb-item active">Nouveau</li>
@endsection

@section('content')
    @if(request()->query('blueprint'))
        <div class="alert alert-info">{{ __('The event form is prefilled from a draft. Review suggested details, choose valid dates and confirm by saving.') }}</div>
    @endif
    <form method="POST" action="{{ route('admin.evenements.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <x-admin.card title="Informations de l'événement" icon="calendar-event">
            @include('modules.evenements.admin.evenements._form')

            <x-slot:footer>
                <a href="{{ route('admin.evenements.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary float-end"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
