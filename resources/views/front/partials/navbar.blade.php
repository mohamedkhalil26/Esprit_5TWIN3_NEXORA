<nav class="navbar navbar-expand-lg navbar-light fixed-top py-3" id="mainNav">
    <div class="container px-4 px-lg-5">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('home') }}">
            <img src="{{ asset('images/logo.svg') }}" alt="Logo SolarShare">
            SolarShare
        </a>
        <button class="navbar-toggler navbar-toggler-right" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false"
                aria-label="Ouvrir le menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarResponsive">
            <ul class="navbar-nav ms-auto my-2 my-lg-0 align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Accueil</a>
                </li>

                {{-- Liens ajoutés par les modules (hooks/front-menu.blade.php) --}}
                @foreach (\App\Support\Modules::hooks('front-menu') as $hook)
                    @include($hook)
                @endforeach

                @guest
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">Connexion</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-sun btn-nav" href="{{ route('register') }}">Inscription</a>
                    </li>
                @else
                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>{{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            @if (auth()->user()->isAdmin())
                                <li>
                                    <a class="dropdown-item" href="{{ route('admin.dashboard') }}">
                                        <i class="bi bi-speedometer2 me-2"></i>Back office
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
                                        <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
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
