@extends('layouts.admin')

@section('title', 'Modifier l\'article')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.articles.index') }}">Articles</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.articles.show', $article) }}">{{ \Illuminate\Support\Str::limit($article->titre, 30) }}</a></li>
    <li class="breadcrumb-item active">Modifier</li>
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.articles.update', $article) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')
        <x-admin.card title="Modifier l'article" icon="pencil-square" outline="warning">
            @include('modules.blog.admin.articles._form')

            <x-slot:footer>
                <a href="{{ route('admin.articles.show', $article) }}" class="btn btn-outline-secondary">Annuler</a>
                <button type="submit" class="btn btn-warning float-end"><i class="bi bi-save me-1"></i>Mettre à jour</button>
            </x-slot:footer>
        </x-admin.card>
    </form>
@endsection
