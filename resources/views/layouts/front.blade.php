<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="{{ __('SolarShare : partage et location d\'équipements d\'énergie renouvelable.') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('Accueil')) | SolarShare</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">

    {{-- Template FrontOffice : Creative (Start Bootstrap) — https://startbootstrap.com/theme/creative --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link href="https://cdn.jsdelivr.net/npm/startbootstrap-creative@7.0.7/dist/css/styles.css" rel="stylesheet">
    <link href="{{ asset('css/solarshare-front.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body id="page-top" class="@yield('body-class')">

    @include('front.partials.navbar')

    @include('front.partials.flash')

    <main>
        @yield('content')
    </main>

    @include('front.partials.footer')

    @include('partials.confirm-delete-modal')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/solarshare.js') }}"></script>
    @stack('scripts')
</body>
</html>
