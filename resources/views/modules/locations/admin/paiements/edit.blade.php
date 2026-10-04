@extends('layouts.admin')

@section('title', 'Modifier le paiement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.paiements.index') }}">Paiements</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.paiements.show', $paiement) }}">{{ $paiement->reference }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.paiements.update', $paiement) }}" novalidate>
        @csrf
        @method('PUT')
        <x-admin.card :title="'Modifier le paiement '.$paiement->reference" icon="pencil-square" outline="warning">
            @include('modules.locations.admin.paiements._form')

            <x-slot:footer>
                <a href="{{ route('admin.paiements.show', $paiement) }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-warning float-end"><i class="bi bi-save me-1"></i>Mettre à jour</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
