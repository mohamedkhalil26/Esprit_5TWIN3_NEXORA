@extends('layouts.front')
@section('title', __('Mon profil'))
@section('body-class', 'nav-solid')
@section('content')
<section class="page-section mt-5"><div class="container" style="max-width:800px">
<h1 class="text-success">{{ __('Mon profil') }}</h1>
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
@if(!auth()->user()->hasVerifiedEmail())<a class="btn btn-warning mb-3" href="{{ route('verification.notice') }}">{{ __('Vérifier mon adresse e-mail') }}</a>@endif
<div class="card p-4 mb-4">
@if(auth()->user()->photo_path)<img class="rounded-circle mb-3" src="{{ Storage::disk('public')->url(auth()->user()->photo_path) }}" width="90" height="90" alt="Ma photo de profil">@endif
<h2 class="h5">{{ __('Informations personnelles') }}</h2>
<form method="POST" enctype="multipart/form-data" action="{{ route('profile.update') }}">@csrf @method('PATCH')
@foreach(['name' => __('Nom complet'), 'email' => __('Adresse e-mail'), 'telephone' => __('Téléphone'), 'ville' => __('Ville')] as $field => $label)
<label for="{{ $field }}">{{ $label }}</label><input class="form-control mb-3" id="{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : 'text' }}" value="{{ old($field, auth()->user()->$field) }}" {{ in_array($field, ['name','email']) ? 'required' : '' }}>
@endforeach
<label for="photo">{{ __('Photo (JPG, PNG ou WebP, 2 Mo maximum)') }}</label><input class="form-control mb-3" id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
<label for="profile-password">{{ __('Mot de passe actuel') }}</label><input id="profile-password" class="form-control mb-3" type="password" name="current_password" required autocomplete="current-password">
<button class="btn btn-success">{{ __('Enregistrer le profil') }}</button></form></div>
<div class="card p-4 mb-4"><h2 class="h5">{{ __('Modifier mon mot de passe') }}</h2>
<form method="POST" action="{{ route('profile.password') }}">@csrf @method('PUT')
@foreach(['current_password' => __('Mot de passe actuel'), 'password' => __('Nouveau mot de passe (8 caractères, lettres et chiffres)'), 'password_confirmation' => __('Confirmer le nouveau mot de passe')] as $field => $label)
<label for="pw-{{ $field }}">{{ $label }}</label><input id="pw-{{ $field }}" class="form-control mb-3" type="password" name="{{ $field }}" required autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}">
@endforeach
<button class="btn btn-success">{{ __('Modifier le mot de passe') }}</button></form></div>
<div class="card p-4 mb-4"><h2 class="h5">{{ __('Double authentification') }}</h2>
<p>{{ auth()->user()->two_factor_confirmed_at ? __('La protection TOTP est activée.') : __('Activez une protection avec votre application d’authentification.') }}</p>
<form method="POST" action="{{ route(auth()->user()->two_factor_confirmed_at ? 'two-factor.disable' : 'two-factor.setup') }}">@csrf
@if(auth()->user()->two_factor_confirmed_at) @method('DELETE')
<label for="disable-code">{{ __('Code TOTP ou code de récupération') }}</label><input class="form-control mb-3" id="disable-code" name="code" required autocomplete="one-time-code">
@endif
<label for="totp-password">{{ __('Mot de passe actuel') }}</label><input class="form-control mb-3" id="totp-password" name="password" type="password" required autocomplete="current-password">
<button class="btn btn-outline-success">{{ auth()->user()->two_factor_confirmed_at ? __('Désactiver la 2FA') : __('Configurer la 2FA') }}</button></form></div>
<div class="card border-danger p-4"><h2 class="h5">{{ __('Supprimer mon compte') }}</h2>
<p>{{ __('La suppression est définitive. Un compte lié à des équipements, locations, articles ou participations actives nécessite un traitement par l’administrateur pour préserver les données liées.') }}</p>
<form method="POST" action="{{ route('profile.destroy') }}">@csrf @method('DELETE')
<label for="delete-password">{{ __('Mot de passe actuel') }}</label><input class="form-control mb-3" id="delete-password" name="password" type="password" required autocomplete="current-password">
<label for="delete-confirm">{{ __('Saisissez SUPPRIMER') }}</label><input class="form-control mb-3" id="delete-confirm" name="confirmation" required>
<button class="btn btn-danger">{{ __('Supprimer définitivement mon compte') }}</button></form></div>
</div></section>
@endsection
