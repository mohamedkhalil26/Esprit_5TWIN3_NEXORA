@extends('layouts.admin')

@section('title', 'Modifier le commentaire')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.commentaires.index') }}">Commentaires</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.commentaires.show', $commentaire) }}">N°{{ $commentaire->id }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.commentaires.update', $commentaire) }}" novalidate>
        @csrf
        @method('PUT')
        <x-admin.card title="Modifier le commentaire" icon="pencil-square" outline="warning">
            @include('modules.blog.admin.commentaires._form')

            <x-slot:footer>
                <a href="{{ route('admin.commentaires.show', $commentaire) }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-warning float-end"><i class="bi bi-save me-1"></i>Mettre à jour</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
