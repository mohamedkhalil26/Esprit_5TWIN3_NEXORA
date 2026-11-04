@extends('layouts.front')
@section('title', 'Quiz - '.$article->titre)
@section('content')
<section class="py-5 mt-5"><div class="container" style="max-width:900px"><h1 class="h3">Quiz de compréhension</h1><p class="text-muted">{{ $article->titre }}</p>
@if(!$quiz)<p class="text-muted">Aucun quiz disponible pour cette langue.</p><form method="POST" action="{{ route('ai.quiz.generate', $article) }}">@csrf<button class="btn btn-primary">Générer le quiz</button></form>@else<form method="POST" action="{{ route('ai.quiz.submit', $quiz) }}">@csrf @foreach($quiz->questions as $question)<fieldset class="card mb-3"><div class="card-body"><legend class="h6">{{ $question['question'] }}</legend>@foreach($question['choices'] as $choice)<label class="d-block"><input type="radio" name="answers[{{ $question['id'] }}]" value="{{ $choice }}"> {{ $choice }}</label>@endforeach</div></fieldset>@endforeach<button class="btn btn-success">Corriger mes réponses</button></form>@endif
</div></section>
@endsection