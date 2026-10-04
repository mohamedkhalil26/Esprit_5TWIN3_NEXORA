@extends('layouts.front')

@section('title', 'Nouvelle réclamation')
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Déposer une réclamation',
        'subtitle' => 'Décrivez votre problème, nous vous répondrons rapidement',
        'breadcrumb' => ['Mes réclamations' => route('front.reclamations.index'), 'Nouvelle' => null],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card form-card">
                        <div class="card-body p-4 p-md-5">
                            <form method="POST" action="{{ route('front.reclamations.store') }}" enctype="multipart/form-data" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label">Type de problème <span class="text-danger">*</span></label>
                                    <div class="row g-2">
                                        @foreach (\App\Models\Reclamation::CATEGORIES as $valeur => [$libelle, $icone])
                                            <div class="col-6 col-md-4">
                                                <input type="radio" class="btn-check" name="categorie" id="cat_{{ $valeur }}" value="{{ $valeur }}" @checked(old('categorie') === $valeur)>
                                                <label class="btn btn-outline-primary w-100 py-3" for="cat_{{ $valeur }}">
                                                    <i class="bi bi-{{ $icone }} d-block fs-4 mb-1"></i><small>{{ $libelle }}</small>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('categorie')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="sujet" class="form-label">Sujet <span class="text-danger">*</span></label>
                                    <input type="text" id="sujet" name="sujet" value="{{ old('sujet') }}" maxlength="150"
                                           class="form-control @error('sujet') is-invalid @enderror" placeholder="Résumez votre problème en une phrase">
                                    @error('sujet')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea id="description" name="description" rows="6" maxlength="3000"
                                              class="form-control @error('description') is-invalid @enderror"
                                              placeholder="Quand ? Quel équipement ? Que s'est-il passé ?">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="priorite" class="form-label">Urgence <span class="text-danger">*</span></label>
                                        <select id="priorite" name="priorite" class="form-select @error('priorite') is-invalid @enderror">
                                            @foreach (\App\Models\Reclamation::PRIORITES as $valeur => [$libelle, $couleur])
                                                <option value="{{ $valeur }}" @selected(old('priorite', 'moyenne') === $valeur)>{{ $libelle }}</option>
                                            @endforeach
                                        </select>
                                        @error('priorite')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="piece_jointe" class="form-label">Pièce jointe (photo, facture...)</label>
                                        <input type="file" id="piece_jointe" name="piece_jointe" accept=".jpg,.jpeg,.png,.pdf"
                                               class="form-control @error('piece_jointe') is-invalid @enderror">
                                        @error('piece_jointe')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100 mt-4"><i class="bi bi-send me-1"></i>Envoyer ma réclamation</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
