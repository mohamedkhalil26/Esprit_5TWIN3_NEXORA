@extends('layouts.front')
@section('title', 'Comparer les équipements')
@section('body-class', 'nav-solid')
@section('content')
<section class="page-section mt-5"><div class="container">
<h1 class="text-success">Comparer les équipements</h1><p>Sélectionnez entre deux et quatre équipements. Toutes les valeurs proviennent du catalogue.</p>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="GET" action="{{ route('tools.compare') }}" class="card p-4 mb-4">
<label for="ids">Équipements (Ctrl ou Cmd pour plusieurs choix)</label><select class="form-select mb-3" name="ids[]" id="ids" multiple size="7" required>
@foreach($catalogue as $choice)<option value="{{ $choice->id }}" @selected(in_array($choice->id, (array) request('ids', [])))>{{ $choice->nom }}</option>@endforeach</select>
<button class="btn btn-success">Comparer</button></form>
@if($items->isNotEmpty())<div class="table-responsive"><table class="table table-bordered align-middle"><caption>Comparaison des caractéristiques enregistrées</caption><thead><tr><th scope="col">Caractéristique</th>@foreach($items as $item)<th scope="col"><a href="{{ route('front.equipements.show', $item) }}">{{ $item->nom }}</a></th>@endforeach</tr></thead><tbody>
@foreach(['marque'=>'Marque', 'puissance_watts'=>'Puissance (W)', 'capacite_wh'=>'Capacité (Wh)', 'prix_jour'=>'Prix par jour (DT)', 'caution'=>'Caution (DT)', 'etat_label'=>'État', 'ville'=>'Ville'] as $field => $label)
<tr><th scope="row">{{ $label }}</th>@foreach($items as $item)<td>{{ $item->$field ?? 'Non renseigné' }}</td>@endforeach</tr>@endforeach
<tr><th scope="row">Disponible au catalogue</th>@foreach($items as $item)<td>{{ $item->disponible ? 'Oui' : 'Non' }}</td>@endforeach</tr>
</tbody></table></div>@endif
</div></section>
@endsection
