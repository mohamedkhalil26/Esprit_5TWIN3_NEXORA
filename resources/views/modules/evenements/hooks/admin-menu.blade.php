@php($actif = request()->routeIs('admin.evenements.*', 'admin.participations.*'))
<li class="nav-item {{ $actif ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ $actif ? 'active' : '' }}">
        <i class="nav-icon bi bi-calendar-event"></i>
        <p>Événements <i class="nav-arrow bi bi-chevron-right"></i></p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.evenements.index') }}" class="nav-link {{ request()->routeIs('admin.evenements.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-calendar3"></i>
                <p>Événements</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.participations.index') }}" class="nav-link {{ request()->routeIs('admin.participations.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-people"></i>
                <p>Participations</p>
            </a>
        </li>
    </ul>
</li>
