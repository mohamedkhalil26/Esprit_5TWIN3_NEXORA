@extends('layouts.front')
@section('title', 'Disponibilités et estimation')
@section('body-class', 'nav-solid')
@section('content')
<section class="page-section mt-5"><div class="container" style="max-width:950px">
<h1 class="text-success">Disponibilités et estimation</h1>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="GET" action="{{ route('tools.availability') }}" class="card p-4 mb-4">
<label for="equipement">Équipement</label><select class="form-select mb-3" name="equipement" id="equipement" required>@foreach($catalogue as $choice)<option value="{{ $choice->id }}" @selected(request('equipement') == $choice->id)>{{ $choice->nom }}</option>@endforeach</select>
<div class="row">@foreach(['date_debut'=>'Date de début', 'date_fin'=>'Date de fin'] as $field => $label)<div class="col-sm-6"><label for="{{ $field }}">{{ $label }}</label><input class="form-control mb-3" type="date" id="{{ $field }}" name="{{ $field }}" min="{{ today()->toDateString() }}" value="{{ request($field) }}"></div>@endforeach</div>
<button class="btn btn-success">Afficher les disponibilités</button></form>
@if($item)
<h2 class="h4">{{ $item->nom }} — {{ $item->prix_jour }} DT par jour</h2>
@if($estimate)<div class="alert {{ $estimate['available'] ? 'alert-success' : 'alert-warning' }}">
{{ $estimate['available'] ? 'Disponible pour cette période' : 'Réservation impossible pour cette période ou cet utilisateur (30 jours maximum)' }}.
{{ $estimate['days'] }} jour(s), {{ number_format($estimate['total'],2,',',' ') }} DT, hors caution de {{ $item->caution }} DT.
@if($estimate['available'])<a class="btn btn-success mt-2" href="{{ route('front.reservations.create', ['equipement'=>$item->id, 'date_debut'=>request('date_debut'), 'date_fin'=>request('date_fin')]) }}">Préparer ma réservation</a>@endif</div>@endif
<h3 class="h5">Périodes occupées (dates incluses)</h3><ul>@forelse($periods as $period)<li>Du {{ $period->date_debut->format('d/m/Y') }} au {{ $period->date_fin->format('d/m/Y') }}</li>@empty<li>Aucune réservation active à venir.</li>@endforelse</ul>
<div class="card p-4 mb-3"><h3 class="h5">Calendrier des 60 prochains jours</h3><div class="d-flex flex-wrap gap-2">
@for($i=0; $i<60; $i++)
@php($day = today()->addDays($i))
@php($busy = !$item->disponible || $periods->contains(fn($p) => $day->betweenIncluded($p->date_debut, $p->date_fin)))
<span class="badge {{ $busy ? 'bg-warning text-dark' : 'bg-success' }} p-2" title="{{ $busy ? 'Occupé ou indisponible' : 'Libre' }}">{{ $day->format('d/m') }} · {{ $busy ? 'Occupé' : 'Libre' }}</span>
@endfor</div></div><p>Estimation indicative : la disponibilité et le prix sont revérifiés par le serveur lors de l’enregistrement.</p>
@endif
</div></section>
@endsection
