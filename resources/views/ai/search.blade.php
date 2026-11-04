@extends(auth()->check() && auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.front')

@section('title', 'Recherche intelligente')

@section('content')
<section class="py-5 mt-5"><div class="container" style="max-width:1100px">
    <h1 class="h3">Recherche intelligente NEXORA</h1>
    <p class="text-muted">Recherche locale dans les ressources accessibles. Les résultats privés restent limités à votre compte.</p>
    <form method="GET" action="{{ route('ai.search') }}" class="row g-2 mb-4">
        <div class="col-md-7"><label class="visually-hidden" for="global-search">Recherche</label><input id="global-search" class="form-control" name="q" value="{{ $query }}" maxlength="200" placeholder="Équipement, événement ou article…"></div>
        <div class="col-md-3"><label class="visually-hidden" for="global-search-type">Type</label><select id="global-search-type" class="form-select" name="type"><option value="">Toutes les ressources publiques</option><option value="equipements" @selected($type === 'equipements')>Équipements</option><option value="evenements" @selected($type === 'evenements')>Événements</option><option value="articles" @selected($type === 'articles')>Articles</option>@auth<option value="reservations" @selected($type === 'reservations')>Mes réservations</option><option value="reclamations" @selected($type === 'reclamations')>Mes réclamations</option>@endauth</select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Rechercher</button></div>
    </form>
    @if($query !== '')<p class="small text-muted">{{ $results->count() }} résultat(s) local(aux) pour « {{ $query }} ».</p>@endif
    <div class="row g-3">@forelse($results as $result)<div class="col-md-6"><article class="card h-100"><div class="card-body"><span class="badge text-bg-light mb-2">{{ $result['type'] }}</span><h2 class="h5">{{ $result['title'] }}</h2><p class="text-muted">{{ Str::limit($result['description'], 220) }}</p><a class="btn btn-outline-primary" href="{{ $result['url'] }}">Consulter</a></div></article></div>@empty @if($query !== '')<p class="text-muted">Aucun résultat dans les ressources autorisées.</p>@endif @endforelse</div>
</div></section>
@endsection
