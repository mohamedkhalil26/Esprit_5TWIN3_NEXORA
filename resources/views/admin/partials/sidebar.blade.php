<aside class="app-sidebar shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ route('admin.dashboard') }}" class="brand-link">
            <img src="{{ asset('images/logo.svg') }}" alt="Logo SolarShare" class="brand-image">
            <span class="brand-text">Solar<span>Share</span></span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                aria-label="Navigation principale" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <p>Tableau de bord</p>
                    </a>
                </li>

                <li class="nav-header">GESTION</li>

                {{-- Menus ajoutés par les modules (hooks/admin-menu.blade.php) --}}
                @forelse (\App\Support\Modules::hooks('admin-menu') as $hook)
                    @if ($hook === 'modules.reclamations.hooks.admin-menu')
                        <li class="nav-item {{ request()->routeIs('admin.reclamations.*', 'admin.reponses.*') ? 'menu-open' : '' }}">
                            @include($hook)
                        </li>
                    @else
                        @include($hook)
                    @endif
                @empty
                    <li class="nav-item">
                        <span class="nav-link text-white-50"><i class="nav-icon bi bi-puzzle"></i><p>Aucun module intégré</p></span>
                    </li>
                @endforelse

                <li class="nav-header">SITE</li>
                <li class="nav-item"><a class="nav-link" href="{{ route('profile.edit') }}"><i class="nav-icon bi bi-person-lock"></i><p>Mon profil et sécurité</p></a></li>
                <li class="nav-header">ASSISTANTS IA</li>
                @foreach(\App\Http\Controllers\AiController::MODULES as $module => $label)
                <li class="nav-item"><a class="nav-link" href="{{ route('ai.show', $module) }}"><i class="nav-icon bi bi-stars"></i><p>{{ $label }}</p></a></li>
                @endforeach
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link" target="_blank">
                        <i class="nav-icon bi bi-globe2"></i>
                        <p>Voir le front office</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
