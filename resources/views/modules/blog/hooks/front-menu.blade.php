<li class="nav-item">
    <a class="nav-link {{ request()->routeIs('front.blog.*') ? 'active' : '' }}" href="{{ route('front.blog.index') }}">{{ __('Blog') }}</a>
</li>
