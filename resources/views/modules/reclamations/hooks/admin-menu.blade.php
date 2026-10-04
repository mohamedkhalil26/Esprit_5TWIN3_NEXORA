@php($actif = request()->routeIs('admin.reclamations.*', 'admin.reponses.*'))
@php($nbOuvertes = \App\Models\Reclamation::where('statut', 'ouverte')->count())
<li class="nav-item {{ $actif ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ $actif ? 'active' : '' }}">
        <i class="nav-icon bi bi-exclamation-diamond"></i>
        <p>
            Réclamations
            @if ($nbOuvertes)<span class="nav-badge badge text-bg-danger me-3">{{ $nbOuvertes }}</span>@endif
            <i class="nav-arrow bi bi-chevron-right"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.reclamations.index') }}" class="nav-link {{ request()->routeIs('admin.reclamations.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-inbox"></i>
                <p>Réclamations</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.reponses.index') }}" class="nav-link {{ request()->routeIs('admin.reponses.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-reply-all"></i>
                <p>Réponses</p>
            </a>
        </li>
    </ul>
</li>
