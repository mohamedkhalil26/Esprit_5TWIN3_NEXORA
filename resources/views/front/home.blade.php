@extends('layouts.front')

@section('title', __('Accueil'))

@section('content')
    {{-- Bannière (masthead du template Creative) --}}
    <header class="masthead">
        <div class="container px-4 px-lg-5 h-100">
            <div class="row gx-4 gx-lg-5 h-100 align-items-center justify-content-center text-center">
                <div class="col-lg-9 align-self-end">
                    <h1 class="text-white font-weight-bold">{{ __('Partagez l\'énergie du') }} <span>{{ __('soleil') }}</span> {{ __('et du vent') }}</h1>
                    <hr class="divider">
                </div>
                <div class="col-lg-8 align-self-baseline">
                    <p class="text-white-75 mb-5">
                        {{ __('Louez près de chez vous des panneaux solaires portables, des batteries ou de petites éoliennes, ou rentabilisez les vôtres en les partageant avec la communauté SolarShare.') }}
                    </p>
                    <a class="btn btn-sun btn-xl me-2 mb-2" href="#comment-ca-marche">{{ __('Comment ça marche ?') }}</a>
                    <a class="btn btn-outline-light btn-xl me-2 mb-2" href="{{ route('ai.index') }}"><i class="bi bi-stars me-2" aria-hidden="true"></i>{{ __('ai_hub.nav_label') }}</a>
                    @guest
                        <a class="btn btn-outline-light btn-xl mb-2" href="{{ route('register') }}">{{ __('Rejoindre SolarShare') }}</a>
                    @endguest
                </div>
            </div>
        </div>
    </header>

    @auth
        <section class="page-section bg-light" aria-labelledby="personal-dashboard-title">
            <div class="container px-4 px-lg-5">
                <h2 id="personal-dashboard-title" class="h3 text-center">{{ __('Mon activité SolarShare') }}</h2>
                <p class="text-center text-muted">{{ __('Indicateurs calculés à partir de vos données enregistrées.') }}</p>
                <div class="row g-3 mt-2">
                    @foreach ([['Réservations', $aiKpis['reservations'], 'calendar-check'], ['Réservations actives', $aiKpis['active_reservations'], 'clock'], ['Réclamations ouvertes', $aiKpis['open_claims'], 'chat-square-text'], ['Événements à venir', $aiKpis['upcoming_events'], 'calendar-event']] as [$label, $value, $icon])
                        <div class="col-12 col-sm-6 col-lg-3"><div class="card h-100"><div class="card-body d-flex align-items-center gap-3"><i class="bi bi-{{ $icon }} fs-3 text-primary" aria-hidden="true"></i><div><div class="small text-muted">{{ __($label) }}</div><strong class="fs-4">{{ $value }}</strong></div></div></div></div>
                    @endforeach
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                    <a class="btn btn-primary" href="{{ route('front.reservations.index') }}"><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>{{ __('Mes réservations') }}</a>
                    <a class="btn btn-outline-primary" href="{{ route('front.reclamations.index') }}"><i class="bi bi-chat-square-text me-1" aria-hidden="true"></i>{{ __('Mes réclamations') }}</a>
                    <a class="btn btn-outline-primary" href="{{ route('front.evenements.mes') }}"><i class="bi bi-calendar-event me-1" aria-hidden="true"></i>{{ __('Mes événements') }}</a>
                    <a class="btn btn-outline-secondary" href="{{ route('ai.index') }}"><i class="bi bi-stars me-1" aria-hidden="true"></i>{{ __('ai_hub.nav_label') }}</a>
                </div>
            </div>
        </section>
    @endauth

    {{-- À propos --}}
    <section class="page-section bg-primary" id="a-propos">
        <div class="container px-4 px-lg-5">
            <div class="row gx-4 gx-lg-5 justify-content-center">
                <div class="col-lg-8 text-center">
                    <h2 class="text-white mt-0">{{ __('Une énergie plus accessible et plus responsable') }}</h2>
                    <hr class="divider divider-light">
                    <p class="text-white-75 mb-4">
                        {{ __('Un panneau solaire portable sert quelques jours par an ? Plutôt que d\'acheter, louez-le ! SolarShare met en relation les particuliers pour mutualiser les équipements d\'énergie renouvelable, réduire les coûts et l\'empreinte carbone de chacun.') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Fonctionnement (section "Services" du template) --}}
    <section class="page-section" id="comment-ca-marche">
        <div class="container px-4 px-lg-5">
            <h2 class="text-center mt-0">{{ __('Comment ça marche ?') }}</h2>
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
                            <h3 class="h4 mb-2">{{ __($titre) }}</h3>
                            <p class="text-muted mb-0">{{ __($texte) }}</p>
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

    {{-- Aperçu des parcours IA alimenté par le même catalogue que /assistants. --}}
    <section class="page-section bg-light" aria-labelledby="ai-preview-title">
        <div class="container px-4 px-lg-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
                <div>
                    <p class="small text-uppercase text-primary fw-semibold mb-2">SolarShare · Intelligence</p>
                    <h2 id="ai-preview-title" class="mt-0 mb-2">{{ __('ai_hub.home_title') }}</h2>
                    <p class="text-muted mb-0">{{ __('ai_hub.home_subtitle') }}</p>
                </div>
                <a class="btn btn-primary" href="{{ route('ai.index') }}">{{ __('ai_hub.nav_label') }} <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
            </div>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                @foreach ($aiModules as $module)
                    <div class="col">
                        <article class="card ai-home-card h-100">
                            <div class="card-body d-flex flex-column p-4">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="rounded-circle ai-home-icon" aria-hidden="true"><i class="bi bi-{{ $module['icon'] }}"></i></span>
                                    <h3 class="h5 mb-0">{{ $module['name'] }}</h3>
                                </div>
                                <p class="text-muted flex-grow-1 mb-3">{{ $module['front']['description'] }}</p>
                                <a class="stretched-link fw-semibold" href="{{ ! $module['requires_auth'] || auth()->check() ? $module['front']['url'] : route('login') }}">{{ ! $module['requires_auth'] || auth()->check() ? $module['front']['title'] : __('ai_hub.sign_in_to_start') }} <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></a>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Appel à l'action --}}
    <section class="page-section bg-dark text-white">
        <div class="container px-4 px-lg-5 text-center">
            <h2 class="mb-4">{{ __('Prêt à passer à l\'énergie partagée ?') }}</h2>
            @guest
                <a class="btn btn-sun btn-xl" href="{{ route('register') }}">{{ __('Créer un compte gratuitement') }}</a>
            @else
                <p class="text-white-50 mb-0">{{ __('Bienvenue') }} {{ auth()->user()->name }}, {{ __('bonne exploration !') }}</p>
            @endguest
        </div>
    </section>
@endsection
