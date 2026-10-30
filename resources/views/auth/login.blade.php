@extends('layouts.front')

@section('title', 'Connexion')
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Connexion',
        'subtitle' => 'Accédez à votre espace SolarShare',
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-5">
                    <div class="card form-card">
                        <div class="card-body p-4 p-md-5">
                            <form method="POST" action="{{ route('login.store') }}" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label for="email" class="form-label">Adresse e-mail</label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                                               class="form-control @error('email') is-invalid @enderror" autofocus>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Mot de passe</label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" id="password" name="password"
                                               class="form-control @error('password') is-invalid @enderror">
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
                                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="bi bi-box-arrow-in-right me-1"></i>Se connecter
                                </button>
                            </form><p class="mt-3"><a href="{{ route('password.request') }}">Mot de passe oublié ?</a></p>

                            <p class="text-center small text-muted mt-4 mb-0">
                                Pas encore de compte ? <a href="{{ route('register') }}">Inscrivez-vous</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
