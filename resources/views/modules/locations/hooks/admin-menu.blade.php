@php($actif = request()->routeIs('admin.reservations.*', 'admin.paiements.*'))
<li class="nav-item {{ $actif ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ $actif ? 'active' : '' }}">
        <i class="nav-icon bi bi-calendar-check"></i>
        <p>Locations <i class="nav-arrow bi bi-chevron-right"></i></p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.reservations.index') }}" class="nav-link {{ request()->routeIs('admin.reservations.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-calendar-range"></i>
                <p>Réservations</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.paiements.index') }}" class="nav-link {{ request()->routeIs('admin.paiements.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-credit-card"></i>
                <p>Paiements</p>
            </a>
        </li>
    </ul>
</li>
