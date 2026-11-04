<nav class="app-header navbar navbar-expand bg-body">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="{{ __('Afficher / masquer le menu') }}">
                    <i class="bi bi-list"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">{{ __('Tableau de bord') }}</a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="{{ route('home') }}" class="nav-link" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i>{{ __('Voir le site') }}</a>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto">
            <li class="nav-item d-flex align-items-center me-2">
                @include('partials.language-switcher', ['id' => 'admin-locale-select'])
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" data-lte-toggle="fullscreen" aria-label="{{ __('Plein écran') }}">
                    <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                    <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
                </a>
            </li>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                    <span class="avatar me-2">{{ auth()->user()->initiales }}</span>
                    <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="dropdown-header">{{ auth()->user()->email }}</li>
                    <li><a class="dropdown-item" href="{{ route('home') }}"><i class="bi bi-house me-2"></i>{{ __('Front office') }}</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>{{ __('Déconnexion') }}</button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
