<li class="nav-item"><a class="nav-link" href="{{ route('tools.compare') }}">Comparateur</a></li>
<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('front.equipements.*') ? 'active' : '' }}" href="{{ route('front.equipements.index') }}">Équipements</a>
</li>
