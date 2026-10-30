@extends(in_array($module, ['reclamations','evenements','blog']) ? 'layouts.admin' : 'layouts.front')
@section('title', 'Assistant IA — '.\App\Http\Controllers\AiController::MODULES[$module])
@section('body-class', 'nav-solid')
@section('content')
<section class="{{ in_array($module, ['reclamations','evenements','blog']) ? 'py-3' : 'py-5 mt-5' }}"><div class="container" style="max-width:1000px">
<h1 class="h3 text-success">Assistant IA — {{ \App\Http\Controllers\AiController::MODULES[$module] }}</h1>
<p>Suggestions générées par OpenRouter, à vérifier avant utilisation. Les données saisies et le contenu sélectionné sont transmis au prestataire IA. Évitez toute information personnelle.</p>
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('ai.generate', $module) }}" class="card p-4 mb-4" id="ai-form">@csrf
@if(in_array($module, ['reclamations','evenements','blog']))
<label for="record">Contenu à analyser (les 100 derniers éléments)</label>
<select class="form-select mb-3" id="record" name="id" required>
@if(isset($result))<option value="{{ $data['id'] }}">Élément analysé n°{{ $data['id'] }}</option>
@else @foreach($items as $id => $title)<option value="{{ $id }}" @selected(old('id', request('id')) == $id)>{{ $title }}</option>@endforeach @endif
</select>
@else
<label for="budget">Budget {{ $module === 'locations' ? 'total pour la location' : 'par jour' }} (DT, hors caution)</label>
<input id="budget" class="form-control mb-3" type="number" name="budget" min="0" max="100000" step="0.01" value="{{ old('budget', $data['budget'] ?? '') }}" required>
@if($module === 'locations')
<div class="row">@foreach(['date_debut' => 'Date de début', 'date_fin' => 'Date de fin'] as $field => $label)<div class="col-sm-6">
<label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" type="date" name="{{ $field }}" value="{{ old($field, $data[$field] ?? '') }}" min="{{ today()->toDateString() }}" class="form-control mb-3" required></div>@endforeach</div>
@endif
@endif
<label for="besoin">{{ in_array($module, ['equipements','locations']) ? 'Vos besoins ou consignes (usage, puissance, durée, contraintes)' : 'Consignes pour l’analyse et les propositions' }}</label>
<textarea id="besoin" class="form-control mb-3" name="besoin" minlength="10" maxlength="2000" required rows="3">{{ old('besoin', $data['besoin'] ?? '') }}</textarea>
<button class="btn btn-success" id="ai-submit">Demander une suggestion IA</button>
<p id="ai-loading" class="mt-2 d-none" role="status">Analyse en cours… Cela peut prendre jusqu’à 60 secondes.</p>
</form>
@isset($result)
<div class="card p-4" aria-live="polite"><h2 class="h4">Résultat IA</h2>
@if(isset($result['equipement_ids']))
@forelse($items as $item)
<div class="border-bottom py-3"><h3 class="h5">{{ $item->nom }}</h3>
<p>{{ $item->prix_jour }} DT / jour · Puissance : {{ $item->puissance_watts ?? 'Non renseignée' }} W · Capacité : {{ $item->capacite_wh ?? 'Non renseignée' }} Wh · {{ $item->ville }} · Caution : {{ $item->caution }} DT</p>
<a class="btn btn-outline-success" href="{{ route('front.equipements.show', $item) }}">Voir la fiche</a>
@if($module === 'locations')<a class="btn btn-success" href="{{ route('front.reservations.create', ['equipement' => $item->id, 'date_debut' => $data['date_debut'], 'date_fin' => $data['date_fin']]) }}">Préparer la réservation</a>@endif</div>
@empty<p>Aucune recommandation pertinente dans les candidats disponibles.</p>@endforelse
<p class="mt-3">Les caractéristiques et prix affichés proviennent de la base SolarShare. La disponibilité sera revérifiée lors de la réservation.</p>
@else
<p>Ces propositions ne sont pas enregistrées. Relisez-les et modifiez-les avant de les valider. Le statut de publication des articles reste inchangé.</p>
<form method="POST" action="{{ route('ai.apply', ['module'=>$module, 'id'=>$data['id']]) }}">@csrf
<input type="hidden" name="ai_validation" value="1">
@foreach($result as $field => $value)
<label class="form-label" for="result-{{ $field }}">{{ ['categorie'=>'Catégorie','priorite'=>'Priorité','resume'=>'Résumé','reponse'=>'Réponse proposée','description'=>'Description','programme'=>'Programme','titre'=>'Titre','mots_cles'=>'Mots-clés','ameliorations'=>'Améliorations'][$field] ?? $field }}</label>
<textarea id="result-{{ $field }}" name="{{ $field }}" class="form-control mb-3" rows="4">{{ old($field, is_array($value) ? implode("\n", $value) : $value) }}</textarea>
@endforeach
<button class="btn btn-success mb-3">{{ $module === 'reclamations' ? 'Valider la catégorie, la priorité et envoyer la réponse' : 'Enregistrer les propositions relues' }}</button>
<p class="small">{{ $module === 'reclamations' ? 'Le résumé reste une aide à la lecture ; videz la réponse pour enregistrer uniquement la catégorie et la priorité.' : ($module === 'blog' ? 'Seuls le titre et le résumé sont enregistrés. Les mots-clés et améliorations restent des suggestions.' : 'Le programme sera ajouté à la description de l’événement.') }}</p>
</form><a class="btn btn-outline-success" href="{{ $editUrl }}" target="_blank" rel="noopener">Ouvrir le formulaire complet</a>
@endif
</div>
@endisset
</div></section>
@endsection
@push('scripts')
<script>document.getElementById('ai-form').addEventListener('submit', function () { document.getElementById('ai-submit').disabled = true; document.getElementById('ai-loading').classList.remove('d-none'); });</script>
@endpush
