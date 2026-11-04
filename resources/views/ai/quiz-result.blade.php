@extends('layouts.front')
@section('title', 'Résultat du quiz')
@section('content')<section class="py-5 mt-5"><div class="container"><h1 class="h3">Résultat</h1><p class="lead">{{ $attempt->score }} / {{ $attempt->total }}</p><a class="btn btn-outline-primary" href="{{ route('ai.quiz.show', $quiz->article) }}">Recommencer</a></div></section>@endsection