<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('front.evenements.index', 'front.evenements.show') ? 'active' : '' }}" href="{{ route('front.evenements.index') }}">{{ __('Événements') }}</a>
</li>
