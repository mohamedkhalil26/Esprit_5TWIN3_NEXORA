@extends('layouts.admin')

@section('title', 'Nouvel article')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.articles.index') }}">Articles</a></li>
    <li class="breadcrumb-item active">Nouveau</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.articles.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <x-admin.card title="Rédiger un article" icon="journal-plus">
            @include('modules.blog.admin.articles._form')

            <x-slot:footer>
                <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary float-end"><i class="bi bi-check-lg me-1"></i>Enregistrer</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
