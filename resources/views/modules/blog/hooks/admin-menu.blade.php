@php($actif = request()->routeIs('admin.articles.*', 'admin.commentaires.*'))
@php($nbEnAttente = \App\Models\Commentaire::where('approuve', false)->count())
<li class="nav-item {{ $actif ? 'menu-open' : '' }}">
    <a href="#" class="nav-link {{ $actif ? 'active' : '' }}">
        <i class="nav-icon bi bi-journal-richtext"></i>
        <p>
            Blog
            @if ($nbEnAttente)<span class="nav-badge badge text-bg-warning me-3">{{ $nbEnAttente }}</span>@endif
            <i class="nav-arrow bi bi-chevron-right"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.articles.index') }}" class="nav-link {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-journal-text"></i>
                <p>Articles</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.commentaires.index') }}" class="nav-link {{ request()->routeIs('admin.commentaires.*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-chat-left-text"></i>
                <p>Commentaires</p>
            </a>
        </li>
    </ul>
</li>
