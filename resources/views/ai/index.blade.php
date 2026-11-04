@extends((auth()->user()?->isAdmin() ?? false) ? 'layouts.admin' : 'layouts.front')
@section('title', __('ai_hub.title'))
@section('body-class', 'nav-solid')
@push('styles')<link href="{{ asset('css/nexora-intelligence.css') }}" rel="stylesheet">@endpush
@section('content')
<section class="ai-hub-page {{ auth()->user()?->isAdmin() ? 'ai-hub-admin' : '' }}" aria-labelledby="ai-hub-title">
    <div class="container ai-hub-container">
        <header class="ai-hub-hero">
            <div class="ai-hub-hero-copy">
                <span class="ai-hub-eyebrow"><i class="bi bi-sun-fill" aria-hidden="true"></i>{{ __('ai_hub.eyebrow') }}</span>
                <h1 id="ai-hub-title">{{ __('ai_hub.hero_title') }}</h1>
                <p class="ai-hub-subtitle">{{ __('ai_hub.subtitle') }}</p>
                <a class="btn ai-hub-hero-cta" href="#ai-hub-modules">{{ __('ai_hub.explore_modules') }} <i class="bi bi-arrow-down" aria-hidden="true"></i></a>
            </div>
            <div class="ai-hub-hero-art" aria-hidden="true">
                <span class="ai-orbit ai-orbit-one"></span><span class="ai-orbit ai-orbit-two"></span>
                <span class="ai-sun"><i class="bi bi-sun-fill"></i></span>
                <span class="ai-leaf"><i class="bi bi-leaf-fill"></i></span>
            </div>
        </header>

        @guest
            <div class="ai-hub-login-note" role="note">
                <i class="bi bi-person-check" aria-hidden="true"></i>
                <span>{{ __('ai_hub.sign_in_hint') }}</span>
                <a href="{{ route('login') }}">{{ __('ai_hub.sign_in') }} <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
            </div>
        @endguest

        <div class="ai-hub-section-heading" id="ai-hub-modules">
            <div><span>{{ __('ai_hub.section_eyebrow') }}</span><h2>{{ __('ai_hub.section_title') }}</h2></div>
            <p>{{ __('ai_hub.section_note') }}</p>
        </div>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4 ai-hub-grid">
            @foreach ($modules as $module)
                @php($canOpen = ! $module['requires_auth'] || auth()->check())
                <div class="col">
                    <article class="ai-domain-card ai-card-{{ $module['key'] }}">
                        <div class="ai-domain-card-top">
                            <span class="ai-domain-icon" aria-hidden="true"><i class="bi bi-{{ $module['icon'] }}"></i></span>
                            <span class="ai-domain-step">{{ __('ai_hub.card_labels.'.$module['key']) }}</span>
                        </div>
                        <h3>{{ $module['front']['title'] }}</h3>
                        <p>{{ $module['front']['description'] }}</p>
                        @if($canOpen)
                            <a class="ai-card-action" href="{{ $module['front']['url'] }}">{{ __('ai_hub.open_feature') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        @else
                            <a class="ai-card-action" href="{{ route('login') }}">{{ __('ai_hub.sign_in_to_start') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        @endif
                    </article>
                </div>
            @endforeach
        </div>

        <div class="ai-hub-trust"><i class="bi bi-shield-check" aria-hidden="true"></i><p>{{ __('ai_hub.privacy') }}</p></div>
    </div>
</section>
@endsection
