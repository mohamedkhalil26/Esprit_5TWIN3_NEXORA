@extends('layouts.front')

@section('title', 'Réserver '.$equipement->nom)
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Réserver un équipement',
        'subtitle' => $equipement->nom.' · '.$equipement->ville,
        'breadcrumb' => ['Équipements' => route('front.equipements.index'), $equipement->nom => route('front.equipements.show', $equipement), 'Réservation' => null],
    ])

    <div class="container px-4 px-lg-5 pt-3 text-end">
        <a class="btn btn-outline-primary btn-sm" href="{{ route('ai.front.show', ['module' => 'locations', 'date_debut' => request('date_debut'), 'date_fin' => request('date_fin')]) }}"><i class="bi bi-stars me-1" aria-hidden="true"></i>Besoin d’aide pour comparer ?</a>
    </div>

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row g-4 justify-content-center">
                <div class="col-lg-7">
                    <div class="card form-card">
                        <div class="card-body p-4 p-md-5">
                            <h4 class="mb-4">Vos dates de location</h4>
                            <form method="POST" action="{{ route('front.reservations.store') }}" novalidate>
                                @csrf
                                <input type="hidden" name="equipement_id" value="{{ $equipement->id }}">
                                @error('equipement_id')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="date_debut" class="form-label">Du <span class="text-danger">*</span></label>
                                        <input type="date" id="date_debut" name="date_debut" min="{{ today()->format('Y-m-d') }}"
                                               value="{{ old('date_debut', request('date_debut')) }}"
                                               class="form-control @error('date_debut') is-invalid @enderror">
                                        @error('date_debut')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_fin" class="form-label">Au <span class="text-danger">*</span></label>
                                        <input type="date" id="date_fin" name="date_fin" min="{{ today()->format('Y-m-d') }}"
                                               value="{{ old('date_fin', request('date_fin')) }}"
                                               class="form-control @error('date_fin') is-invalid @enderror">
                                        @error('date_fin')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label for="message" class="form-label">Message au propriétaire</label>
                                        <textarea id="message" name="message" rows="3" maxlength="1000"
                                                  class="form-control @error('message') is-invalid @enderror"
                                                  placeholder="Ex. Je compte l'utiliser pour un week-end en camping...">{{ old('message') }}</textarea>
                                        @error('message')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center bg-light rounded p-3 mt-4">
                                    <span><span id="nb-jours">0</span> jour(s) × {{ number_format($equipement->prix_jour, 2, ',', ' ') }} DT</span>
                                    <span class="fs-4 fw-bold text-primary"><span id="total">0,00</span> DT</span>
                                </div>

                                <button type="submit" class="btn btn-primary btn-lg w-100 mt-4">
                                    <i class="bi bi-send me-1"></i>Envoyer ma demande
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-ss mb-4">
                        <div class="placeholder-visual" style="height: 140px"><i class="bi {{ $equipement->categorie->icone }}"></i></div>
                        <div class="card-body">
                            <h5>{{ $equipement->nom }}</h5>
                            <p class="small text-muted mb-1">Proposé par {{ $equipement->proprietaire->name }}</p>
                            <p class="small text-muted mb-0">Caution : {{ number_format($equipement->caution, 2, ',', ' ') }} DT</p>
                        </div>
                    </div>

                    <div class="card form-card">
                        <div class="card-body">
                            <h6><i class="bi bi-calendar-x text-danger me-1"></i>Périodes déjà réservées</h6>
                            @forelse ($periodesOccupees as $periode)
                                <span class="badge bg-light text-dark border me-1 mb-1">
                                    {{ $periode->date_debut->format('d/m') }} → {{ $periode->date_fin->format('d/m/Y') }}
                                </span>
                            @empty
                                <p class="small text-muted mb-0">Aucune : l'équipement est libre !</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const prix = {{ (float) $equipement->prix_jour }};
            const debut = document.getElementById('date_debut');
            const fin = document.getElementById('date_fin');
            const calculer = () => {
                let jours = 0;
                if (debut.value && fin.value) {
                    jours = Math.max(0, Math.round((new Date(fin.value) - new Date(debut.value)) / 86400000) + 1);
                }
                fin.min = debut.value || fin.min;
                document.getElementById('nb-jours').textContent = jours;
                document.getElementById('total').textContent = (jours * prix).toLocaleString('fr-FR', {minimumFractionDigits: 2});
            };
            debut.addEventListener('change', calculer);
            fin.addEventListener('change', calculer);
            calculer();
        })();
    </script>
@endpush
