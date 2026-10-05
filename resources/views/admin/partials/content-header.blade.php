{{--
    En-tête de page du back office.
    Les vues définissent : @section('title'), et optionnellement @section('breadcrumb') et @section('header-actions').
--}}
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h3 class="mb-0">@yield('title', 'Tableau de bord')</h3>
            </div>
            <div class="col-sm-6">
                @php($breadcrumbItems = json_decode($__env->yieldContent('breadcrumb-data'), true) ?? [])
                <ol class="breadcrumb float-sm-end mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Accueil</a></li>
                    @if ($breadcrumbItems)
                        @foreach ($breadcrumbItems as $item)
                            @if ($item['active'] ?? false)
                                <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
                            @else
                                <li class="breadcrumb-item"><a href="{{ $item['url'] }}">{{ $item['label'] }}</a></li>
                            @endif
                        @endforeach
                    @else
                        @hasSection('breadcrumb')
                            @yield('breadcrumb')
                        @else
                            <li class="breadcrumb-item active" aria-current="page">@yield('title', 'Tableau de bord')</li>
                        @endif
                    @endif
                </ol>
            </div>
        </div>
    </div>
</div>
