@extends('layouts.front')

@section('title', 'Inscription')
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Créer un compte',
        'subtitle' => 'Rejoignez la communauté du partage d\'énergie verte',
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-7">
                    <div class="card form-card">
                        <div class="card-body p-4 p-md-5">
                            <form method="POST" action="{{ route('register.store') }}" novalidate>
                                @csrf

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                                               class="form-control @error('name') is-invalid @enderror" autofocus>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Adresse e-mail <span class="text-danger">*</span></label>
                                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                                               class="form-control @error('email') is-invalid @enderror">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="telephone" class="form-label">Téléphone</label>
                                        <input type="text" id="telephone" name="telephone" value="{{ old('telephone') }}"
                                               placeholder="+216 22 333 444"
                                               class="form-control @error('telephone') is-invalid @enderror">
                                        @error('telephone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="ville" class="form-label">Ville</label>
                                        <input type="text" id="ville" name="ville" value="{{ old('ville') }}"
                                               class="form-control @error('ville') is-invalid @enderror">
                                        @error('ville')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                        <input type="password" id="password" name="password"
                                               class="form-control @error('password') is-invalid @enderror">
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @else
                                            <div class="form-text">8 caractères minimum, avec lettres et chiffres.</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="password_confirmation" class="form-label">Confirmation <span class="text-danger">*</span></label>
                                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100 py-2 mt-4">
                                    <i class="bi bi-person-plus me-1"></i>Créer mon compte
                                </button>
                            </form>

                            <p class="text-center small text-muted mt-4 mb-0">
                                Déjà inscrit ? <a href="{{ route('login') }}">Connectez-vous</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
