<nav class="navbar navbar-expand-xl navbar-light fixed-top py-3" id="mainNav">
    <div class="container px-4 px-lg-5">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
            <img src="{{ asset('images/logo.svg') }}" alt="Logo SolarShare">
            SolarShare
        </a>
        <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false"
                aria-label="{{ __('Ouvrir le menu') }}">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ms-auto my-2 my-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">{{ __('Accueil') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('front.reservations.*', 'ai.recommendations.booking') ? 'active' : '' }}" href="{{ route('front.reservations.create') }}">{{ __('nexora.common.reserve') }}</a>
                </li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('ai.search') ? 'active' : '' }}" href="{{ route('ai.search') }}">{{ __('Recherche') }}</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('front.equipements.*', 'front.evenements.*', 'front.blog.*', 'tools.compare') ? 'active' : '' }}" href="#" id="exploreMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">{{ __('nexora.common.explore') }}</a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exploreMenu">
                        <li><a class="dropdown-item" href="{{ route('tools.compare') }}"><i class="bi bi-arrow-left-right me-2" aria-hidden="true"></i>{{ __('nexora.common.compare') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('front.equipements.index') }}"><i class="bi bi-sun me-2" aria-hidden="true"></i>{{ __('nexora.common.equipment') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('front.evenements.index') }}"><i class="bi bi-calendar-event me-2" aria-hidden="true"></i>{{ __('nexora.common.events') }}</a></li>
                        <li><a class="dropdown-item" href="{{ route('front.blog.index') }}"><i class="bi bi-journal-text me-2" aria-hidden="true"></i>{{ __('nexora.common.blog') }}</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('ai.index') ? 'active' : '' }}" href="{{ route('ai.index') }}"><i class="bi bi-stars me-1" aria-hidden="true"></i>{{ __('ai_hub.nav_label') }}</a>
                </li>

                <li class="nav-item ms-lg-2">@include('partials.language-switcher', ['id' => 'front-locale-select'])</li>

                @guest
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">{{ __('Connexion') }}</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-sun btn-nav" href="{{ route('register') }}">{{ __('Inscription') }}</a>
                    </li>
                @else
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}">{{ __('Mon profil et sécurité') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('favorites.index') }}">{{ __('nexora.common.favorites') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('tools.availability') }}">{{ __('nexora.common.availability') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('ai.index') }}">{{ __('ai_hub.title') }}</a></li>
                            <li><a class="dropdown-item" href="{{ route('notifications.index') }}">{{ __('nexora.common.notifications') }}</a></li>
                            @if (auth()->user()->isAdmin())
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="bi bi-speedometer2 me-2"></i>{{ __('Back office') }}
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            @endif

                            {{-- Liens personnels ajoutés par les modules (hooks/user-menu.blade.php) --}}
                            @foreach (\App\Support\Modules::hooks('user-menu') as $hook)
                                @if ($hook === 'modules.reclamations.hooks.user-menu')
                                    <li>
                                        @include($hook)
                                    </li>
                                @else
                                    @include($hook)
                                @endif
                            @endforeach

                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>{{ __('Déconnexion') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
