@extends('layouts.front')
@section('title', 'Mes articles favoris')
@section('body-class', 'nav-solid')
@section('content')
<section class="page-section mt-5"><div class="container"><h1 class="text-success">Mes articles favoris</h1>
<div class="row">@forelse($articles as $article)<div class="col-md-4 mb-3"><div class="card h-100 p-4"><h2 class="h5"><a href="{{ route('front.blog.show', $article->slug) }}">{{ $article->titre }}</a></h2><p>{{ $article->resume }}</p>
<form method="POST" action="{{ route('favorites.destroy', $article) }}">@csrf @method('DELETE')<button class="btn btn-outline-danger">Retirer des favoris</button></form></div></div>@empty<p>Aucun article enregistré. <a href="{{ route('front.blog.index') }}">Découvrir le blog</a></p>@endforelse</div>
{{ $articles->links() }}</div></section>
@endsection
