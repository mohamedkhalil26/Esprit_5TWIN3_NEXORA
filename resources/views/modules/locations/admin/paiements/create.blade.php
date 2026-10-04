@extends('layouts.admin')

@section('title', 'Nouveau paiement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.paiements.index') }}">Paiements</a></li>
    <li class="breadcrumb-item active">Nouveau</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.paiements.store') }}" novalidate>
        @csrf
        <x-admin.card title="Enregistrer un paiement" icon="credit-card">
            @include('modules.locations.admin.paiements._form')

            <x-slot:footer>
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary float-end"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
