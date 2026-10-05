@extends('layouts.admin')

@section('title', 'Modifier la réclamation n°'.$reclamation->id)

@section('breadcrumb-data')
    {!! json_encode([
        ['label' => 'Réclamations', 'url' => route('admin.reclamations.index')],
        ['label' => 'N°'.$reclamation->id, 'url' => route('admin.reclamations.show', $reclamation)],
        ['label' => 'Modifier', 'active' => true],
    ]) !!}
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.reclamations.update', $reclamation) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        <x-admin.card title="Modifier la réclamation" icon="pencil-square" outline="warning">
            @include('modules.reclamations.admin.reclamations._form')

            <x-slot:footer>
                <a href="{{ route('admin.reclamations.show', $reclamation) }}" class="btn btn-outline-secondary">
                    Annuler
                </a>

                <button type="submit" class="btn btn-warning float-end">
                    <i class="bi bi-save me-1"></i>Mettre à jour
                </button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection