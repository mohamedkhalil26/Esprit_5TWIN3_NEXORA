@extends('layouts.admin')

@section('title', 'Nouveau commentaire')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.commentaires.index') }}">Commentaires</a></li>
    <li class="breadcrumb-item active">Nouveau</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.commentaires.store') }}" novalidate>
        @csrf
        <x-admin.card title="Ajouter un commentaire" icon="chat-left-text">
            @include('modules.blog.admin.commentaires._form')

            <x-slot:footer>
                <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary float-end"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
