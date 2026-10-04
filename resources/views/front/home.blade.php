@extends('layouts.front')

@section('title', 'Accueil')

@section('content')
    {{-- Bannière (masthead du template Creative) --}}
    <header class="masthead">
        <div class="container px-4 px-lg-5 h-100">
            <div class="row gx-4 gx-lg-5 h-100 align-items-center justify-content-center text-center">
                <div class="col-lg-9 align-self-end">
                    <h1 class="text-white font-weight-bold">Partagez l'énergie du <span>soleil</span> et du vent</h1>
                    <hr class="divider">
                </div>
                <div class="col-lg-8 align-self-baseline">
                    <p class="text-white-75 mb-5">
                        Louez près de chez vous des panneaux solaires portables, des batteries ou de petites éoliennes,
                        ou rentabilisez les vôtres en les partageant avec la communauté SolarShare.
                    </p>
                    <a class="btn btn-sun btn-xl me-2 mb-2" href="#comment-ca-marche">Comment ça marche ?</a>
                    @guest
                        <a class="btn btn-outline-light btn-xl mb-2" href="{{ route('register') }}">Rejoindre SolarShare</a>
                    @endguest
                </div>
            </div>
        </div>
    </header>

    {{-- À propos --}}
    <section class="page-section bg-primary" id="a-propos">
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="text-white mt-0">Une énergie plus accessible et plus responsable</h2>
                    <hr class="divider divider-light">
                    <p class="text-white-75 mb-4">
                        Un panneau solaire portable sert quelques jours par an ? Plutôt que d'acheter, louez-le !
                        SolarShare met en relation les particuliers pour mutualiser les équipements d'énergie
                        renouvelable, réduire les coûts et l'empreinte carbone de chacun.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Fonctionnement (section "Services" du template) --}}
    <section class="page-section" id="comment-ca-marche">
        <div class="container px-4 px-lg-5">
            <h2 class="text-center mt-0">Comment ça marche ?</h2>
            <hr class="divider">
            <div class="row gx-4 gx-lg-5">
                @foreach ([
                    ['search', 'Trouvez', 'Parcourez le catalogue d\'équipements disponibles près de chez vous.'],
                    ['calendar-check', 'Réservez', 'Choisissez vos dates et réservez en quelques clics.'],
                    ['lightning-charge', 'Profitez', 'Produisez votre propre énergie verte pendant la location.'],
                    ['arrow-repeat', 'Partagez', 'Proposez à votre tour vos équipements et gagnez un revenu.'],
                ] as [$icone, $titre, $texte])
                    <div class="col-lg-3 col-md-6 text-center">
                        <div class="mt-5">
                            <div class="mb-3"><span class="step-icon"><i class="bi bi-{{ $icone }}"></i></span></div>
                            <h3 class="h4 mb-2">{{ $titre }}</h3>
                            <p class="text-muted mb-0">{{ $texte }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Sections ajoutées par les modules (hooks/home.blade.php) --}}
    @foreach (\App\Support\Modules::hooks('home') as $hook)
        @include($hook)
    @endforeach

    {{-- Appel à l'action --}}
    <section class="page-section bg-dark text-white">
        <div class="container px-4 px-lg-5 text-center">
            <h2 class="mb-4">Prêt à passer à l'énergie partagée ?</h2>
            @guest
                <a class="btn btn-sun btn-xl" href="{{ route('register') }}">Créer mon compte gratuitement</a>
            @else
                <p class="text-white-50 mb-0">Bienvenue {{ auth()->user()->name }}, bonne exploration !</p>
            @endguest
        </div>
    </section>
@endsection
