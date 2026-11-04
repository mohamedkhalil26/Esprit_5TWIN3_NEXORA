@extends('layouts.front')
@section('title', __('Sécurité du compte'))
@section('body-class', 'nav-solid')
@section('content')
<section class="page-section mt-5"><div class="container" style="max-width:650px">
<div class="card shadow-sm p-4"><h1 class="h3 text-success mb-4">{{ __('Sécurité du compte') }}</h1>
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
@if($mode === 'forgot')
    <h2 class="h5">{{ __('Mot de passe oublié') }}</h2>
    <form method="POST" action="{{ route('password.email') }}">@csrf
        <label for="email">{{ __('Adresse e-mail') }}</label><input id="email" class="form-control mb-3" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
        <button class="btn btn-success">{{ __('Envoyer le lien') }}</button>
    </form>
@elseif($mode === 'reset')
    <form method="POST" action="{{ route('password.update') }}">@csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label for="email">{{ __('Adresse e-mail') }}</label><input id="email" class="form-control mb-3" type="email" name="email" value="{{ old('email', request('email')) }}" required autocomplete="email">
        <label for="password">{{ __('Nouveau mot de passe') }}</label><input id="password" class="form-control mb-3" type="password" name="password" required autocomplete="new-password">
        <label for="confirmation">{{ __('Confirmer le mot de passe') }}</label><input id="confirmation" class="form-control mb-3" type="password" name="password_confirmation" required autocomplete="new-password">
        <button class="btn btn-success">{{ __('Réinitialiser') }}</button>
    </form>
@elseif($mode === 'verify')
    <p>{{ __('Consultez votre messagerie pour vérifier votre adresse e-mail et accéder aux fonctionnalités réservées aux membres vérifiés.') }}</p>
    <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn btn-success">{{ __('Renvoyer le lien de vérification') }}</button></form>
@elseif($mode === 'challenge' || $mode === 'setup')
    @if($mode === 'setup')
        <p>{{ __('Scannez ce QR code avec votre application d’authentification, puis saisissez son code. Cette activation expire après 10 minutes.') }}</p>
        <img src="{{ $qr }}" alt="{{ __('QR code de configuration TOTP') }}" width="240" height="240">
        <p>{{ __('Clé de configuration manuelle') }} : <code>{{ $secret }}</code></p>
    @else
        <p>{{ __('Saisissez le code de votre application ou un code de récupération. La connexion expire après 5 minutes.') }}</p>
    @endif
    <form method="POST" action="{{ route($mode === 'setup' ? 'two-factor.confirm' : 'two-factor.authenticate') }}">@csrf
        <label for="code">{{ __('Code de sécurité') }}</label><input id="code" name="code" class="form-control mb-3" required autocomplete="one-time-code" maxlength="100">
        <button class="btn btn-success">{{ __('Vérifier') }}</button>
    </form>
@elseif($mode === 'recovery')
    <div class="alert alert-success">{{ __('Double authentification activée.') }}</div>
    <p>{{ __('Conservez ces codes dans un endroit sûr. Ils sont affichés une seule fois et utilisables une seule fois chacun.') }}</p>
    <ul>@foreach($codes as $code)<li><code>{{ $code }}</code></li>@endforeach</ul>
    <a href="{{ route('profile.edit') }}" class="btn btn-success">{{ __('Retour au profil') }}</a>
@endif
</div></div></section>
@endsection
