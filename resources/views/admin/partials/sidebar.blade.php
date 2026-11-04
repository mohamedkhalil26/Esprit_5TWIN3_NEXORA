<aside class="app-sidebar shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ route('admin.dashboard') }}" class="brand-link">
            <img src="{{ asset('images/logo.svg') }}" alt="Logo SolarShare" class="brand-image">
            <span class="brand-text">SolarShare</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation"
                aria-label="Navigation principale" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <p>{{ __('Tableau de bord') }}</p>
                    </a>
                </li>

                @php
                    $moduleOrder = ['equipements' => 0, 'locations' => 1, 'evenements' => 2, 'reclamations' => 3, 'blog' => 4];
                    $adminMenuHooks = collect(\App\Support\Modules::hooks('admin-menu'))
                        ->sortBy(fn ($hook) => $moduleOrder[explode('.', $hook)[1] ?? ''] ?? PHP_INT_MAX)
                        ->values();
                    $activeMenuGroup = null;
                @endphp
                @forelse ($adminMenuHooks as $hook)
                    @php
                        $moduleName = explode('.', $hook)[1] ?? '';
                        $menuGroup = match ($moduleName) {
                            'equipements', 'locations' => __('CATALOGUE ET LOCATIONS'),
                            'evenements', 'reclamations' => __('COMMUNAUTÉ'),
                            'blog' => __('CONTENUS'),
                            default => __('AUTRES MODULES'),
                        };
                    @endphp
                    @if ($menuGroup !== $activeMenuGroup)
                        <li class="nav-header">{{ $menuGroup }}</li>
                        @php($activeMenuGroup = $menuGroup)
                    @endif
                    @if ($moduleName === 'reclamations')
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

                <li class="nav-header">{{ __('COMPTE') }}</li>
                <li class="nav-item"><a class="nav-link" href="{{ route('profile.edit') }}"><i class="nav-icon bi bi-person-lock"></i><p>{{ __('Mon profil et sécurité') }}</p></a></li>
                <li class="nav-header">{{ __('INTELLIGENCE ET RAPPORTS') }}</li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}" href="{{ route('ai.index') }}"><i class="nav-icon bi bi-stars"></i><p>{{ __('ai_hub.title') }}</p></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('ai.admin.show') && request()->route('module') === 'equipements' ? 'active' : '' }}" href="{{ route('ai.admin.show', 'equipements') }}"><i class="nav-icon bi bi-sun"></i><p>{{ __('ai_hub.modules.equipements.back_title') }}</p></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('ai.admin.show') && request()->route('module') === 'locations' ? 'active' : '' }}" href="{{ route('ai.admin.show', 'locations') }}"><i class="nav-icon bi bi-calendar-check"></i><p>{{ __('ai_hub.modules.locations.back_title') }}</p></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.reclamations.intelligence*') ? 'active' : '' }}" href="{{ route('admin.reclamations.intelligence') }}"><i class="nav-icon bi bi-chat-square-text"></i><p>{{ __('ai_hub.modules.reclamations.back_title') }}</p></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('ai.admin.show') && request()->route('module') === 'blog' ? 'active' : '' }}" href="{{ route('ai.admin.show', 'blog') }}"><i class="nav-icon bi bi-journal-richtext"></i><p>{{ __('ai_hub.modules.blog.back_title') }}</p></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('ai.admin.insights.index') ? 'active' : '' }}" href="{{ route('ai.admin.insights.index') }}"><i class="nav-icon bi bi-graph-up"></i><p>{{ __('Insights et demande') }}</p></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('ai.admin.blueprints.*') ? 'active' : '' }}" href="{{ route('ai.admin.blueprints.index') }}"><i class="nav-icon bi bi-calendar-plus"></i><p>{{ __('Blueprints événementiels') }}</p></a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('ai.admin.feedback.index') ? 'active' : '' }}" href="{{ route('ai.admin.feedback.index') }}"><i class="nav-icon bi bi-chat-square-heart"></i><p>{{ __('Retours IA') }}</p></a></li>
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link" target="_blank">
                        <i class="nav-icon bi bi-globe2"></i>
                        <p>{{ __('Voir le front office') }}</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
