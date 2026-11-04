{{--
    En-tête des pages internes du front office.
    Utilisation : @include('front.partials.page-header', ['title' => '...', 'subtitle' => '...', 'breadcrumb' => ['Libellé' => url, 'Page courante' => null]])
--}}
<header class="page-header-ss">
    <div class="container px-4 px-lg-5">
        <h1 class="display-6">{{ $title }}</h1>
        <hr class="divider">
        @isset($subtitle)
            <p class="lead">{{ $subtitle }}</p>
        @endisset
        @isset($breadcrumb)
            <nav aria-label="Fil d'Ariane" class="mt-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Accueil') }}</a></li>
                    @foreach ($breadcrumb as $libelle => $lien)
                        @if ($lien)
                            <li class="breadcrumb-item"><a href="{{ $lien }}">{{ $libelle }}</a></li>
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $libelle }}</li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endisset
    </div>
</header>
