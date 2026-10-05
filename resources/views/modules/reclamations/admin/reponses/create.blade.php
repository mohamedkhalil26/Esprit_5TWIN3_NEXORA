@extends('layouts.admin')

@section('title', 'Nouvelle réponse')

@section('breadcrumb-data')
    @json([
        ['label' => 'Réponses', 'url' => route('admin.reponses.index')],
        ['label' => 'Nouvelle', 'active' => true],
    ])
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.reponses.store') }}" novalidate>
        @csrf
        <x-admin.card title="Répondre à une réclamation" icon="reply">
            @include('modules.reclamations.admin.reponses._form')

            <x-slot:footer>
                <a href="{{ route('admin.reponses.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary float-end"><i class="bi bi-send me-1"></i>Envoyer</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
