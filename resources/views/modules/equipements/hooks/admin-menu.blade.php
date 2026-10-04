@php($actif = request()->routeIs('admin.categories.*', 'admin.equipements.*'))
<li class="nav-item {{ $actif ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ $actif ? 'active' : '' }}">
        <i class="nav-icon bi bi-sun"></i>
        <p>Équipements <i class="nav-arrow bi bi-chevron-right"></i></p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-tags"></i>
                <p>Catégories</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.equipements.index') }}" class="nav-link {{ request()->routeIs('admin.equipements.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-lightning-charge"></i>
                <p>Équipements</p>
            </a>
        </li>
    </ul>
</li>
