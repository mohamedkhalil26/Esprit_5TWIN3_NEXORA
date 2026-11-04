<li class="nav-item"><a class="nav-link" href="{{ route('tools.compare') }}">{{ __('Comparateur') }}</a></li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('front.equipements.*') ? 'active' : '' }}" href="{{ route('front.equipements.index') }}">{{ __('Équipements') }}</a>
</li>
