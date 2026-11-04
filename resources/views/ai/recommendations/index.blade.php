@extends('layouts.front')

@php
    $titleKey = 'nexora.'.$mode.'.title';
    $formKeys = match ($mode) {
        'energy' => ['need' => 'need', 'category' => 'category', 'power' => 'power', 'capacity' => 'capacity', 'budget' => 'budget', 'city' => 'city', 'submit' => 'search'],
        'booking' => ['need' => 'need', 'date_start' => 'start', 'date_end' => 'end', 'budget' => 'budget', 'city' => 'city', 'submit' => 'search'],
        default => ['type' => 'type', 'city' => 'city', 'date' => 'date', 'budget' => 'budget', 'submit' => 'search'],
    };
@endphp

@section('title', __($titleKey))

@section('content')
<section class="py-5 mt-5 bg-cream">
    <div class="container px-4 px-lg-5" style="max-width: 1180px">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <span class="badge text-bg-primary mb-2"><i class="bi bi-stars me-1" aria-hidden="true"></i>NEXORA</span>
                <h1 class="h2 mb-2">{{ __($titleKey) }}</h1>
                <p class="text-muted mb-0">{{ __('nexora.'.$mode.'.intro') }}</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if($mode !== 'energy')<a class="btn btn-sm btn-outline-primary" href="{{ route('ai.recommendations.energy') }}">{{ __('nexora.common.energy_link') }}</a>@endif
                @auth
                    @if($mode !== 'booking')<a class="btn btn-sm btn-outline-primary" href="{{ route('ai.recommendations.booking') }}">{{ __('nexora.common.booking_link') }}</a>@endif
                @endauth
                @if($mode !== 'events')<a class="btn btn-sm btn-outline-primary" href="{{ route('ai.recommendations.events') }}">{{ __('nexora.common.events_link') }}</a>@endif
            </div>
        </div>

        <form method="GET" action="{{ match($mode) {'energy' => route('ai.recommendations.energy'), 'booking' => route('ai.recommendations.booking'), default => route('ai.recommendations.events')} }}" class="card form-card shadow-sm mb-4">
            <div class="card-body p-3 p-md-4">
                <div class="row g-3 align-items-end">
                    @foreach($formKeys as $name => $key)
                        @if($name === 'submit')
                            <div class="col-12 col-md-4 col-lg-3 d-grid"><button class="btn btn-primary" type="submit"><i class="bi bi-search me-1" aria-hidden="true"></i>{{ __('nexora.'.$mode.'.'.$key) }}</button></div>
                        @elseif($name === 'type')
                            <div class="col-12 col-sm-6 col-lg-3"><label class="form-label" for="filter-{{ $name }}">{{ __('nexora.events.type') }}</label><select class="form-select" id="filter-{{ $name }}" name="type"><option value="">{{ __('nexora.events.all_types') }}</option>@foreach(\App\Models\Evenement::TYPES as $type => [$label])<option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ __('nexora.event_types.'.$type) }}</option>@endforeach</select></div>
                        @elseif($name === 'category')
                            <div class="col-12 col-sm-6 col-lg-3"><label class="form-label" for="filter-category">{{ __('nexora.energy.category') }}</label><select class="form-select" id="filter-category" name="category"><option value="">—</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string)($filters['category'] ?? '') === (string)$category->id)>{{ $category->nom }}</option>@endforeach</select></div>
                        @else
                            @php
                                $inputType = in_array($name, ['date', 'date_start', 'date_end'], true) ? 'date' : (in_array($name, ['power', 'capacity', 'budget'], true) ? 'number' : 'text');
                            @endphp
                            <div class="col-12 col-sm-6 col-lg-{{ $mode === 'energy' ? '3' : '4' }}">
                                <label class="form-label" for="filter-{{ $name }}">{{ __('nexora.'.$mode.'.'.$key) }}</label>
                                <input class="form-control" id="filter-{{ $name }}" name="{{ $name }}" type="{{ $inputType }}" value="{{ old($name, $filters[$name] ?? '') }}" @if($mode === 'booking' && in_array($name, ['date_start', 'date_end'], true)) required @endif @if($inputType === 'number') min="0" step="0.01" @endif @if($inputType === 'date') min="{{ $name === 'date_end' ? ($filters['date_start'] ?? today()->toDateString()) : today()->toDateString() }}" @endif @if($inputType === 'text') maxlength="180" @endif>
                                @error($name)<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>
                        @endif
                    @endforeach
                </div>
                @if($mode === 'booking' && $searched && $days)
                    <p class="small text-muted mt-3 mb-0">{{ trans_choice('nexora.booking.duration', $days, ['count' => $days]) }} · {{ __('nexora.booking.total') }} {{ __('nexora.common.dt') }}</p>
                @endif
            </div>
        </form>

        @if(!$searched)
            <div class="alert alert-info d-flex gap-2 align-items-center"><i class="bi bi-info-circle" aria-hidden="true"></i><span>{{ __('nexora.recommendation.start') }}</span></div>
        @elseif($items->isEmpty())
            <div class="alert alert-light border d-flex gap-2 align-items-center"><i class="bi bi-search" aria-hidden="true"></i><span>{{ __('nexora.recommendation.empty') }}</span></div>
        @else
            <p class="small text-muted">{{ $items->count() }} {{ __($titleKey) }}</p>
            <div class="row g-3">
                @foreach($items as $item)
                    @php
                        $isEvent = $mode === 'events';
                        $matched = collect($item->recommendation['components'])->filter(fn($score) => $score >= 70)->keys();
                        $missing = $item->recommendation['missing'];
                        $imageUrl = $isEvent ? $item->image_url : $item->image_url;
                    @endphp
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="card card-ss h-100 shadow-sm">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" class="card-img-top" style="height:190px;object-fit:cover" alt="{{ $isEvent ? $item->titre : $item->nom }}">
                            @else
                                <div class="placeholder-visual" style="height:190px"><i class="bi {{ $isEvent ? 'bi-calendar-event' : ($item->categorie->icone ?? 'bi-sun') }}" aria-hidden="true"></i></div>
                            @endif
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <h2 class="h5 mb-0">{{ $isEvent ? $item->titre : $item->nom }}</h2>
                                    <span class="badge {{ $isEvent && !$item->inscriptionsOuvertes() ? 'text-bg-secondary' : 'text-bg-success' }}">{{ $isEvent && !$item->inscriptionsOuvertes() ? __('nexora.events.full') : __('nexora.common.available') }}</span>
                                </div>
                                <p class="small text-muted mb-2"><i class="bi bi-geo-alt me-1" aria-hidden="true"></i>{{ $item->ville }}@if($isEvent), {{ $item->lieu }}@endif</p>
                                @if($isEvent)
                                    <p class="small mb-2">{{ __('nexora.event_types.'.$item->type) }} · {{ $item->date_debut->translatedFormat('d F Y · H:i') }} · {{ (float)$item->prix > 0 ? number_format((float)$item->prix, 2, ',', ' ').' DT' : __('nexora.events.free') }}</p>
                                    <p class="small mb-2">{{ trans_choice('nexora.events.places', $item->places_restantes, ['count' => $item->places_restantes]) }}</p>
                                @else
                                    <p class="small text-muted mb-1">{{ $item->categorie?->nom }} · {{ $item->marque }}</p>
                                    <p class="small mb-2">{{ $item->puissance_watts ?? '—' }} W · {{ $item->capacite_wh ?? '—' }} Wh · {{ number_format((float)$item->prix_jour, 2, ',', ' ') }} DT {{ __('nexora.common.per_day') }}</p>
                                    @if($mode === 'booking')<p class="fw-semibold mb-2">{{ __('nexora.booking.total') }} : {{ number_format($item->booking_total, 2, ',', ' ') }} DT · {{ trans_choice('nexora.booking.duration', $days, ['count' => $days]) }}</p>@endif
                                @endif
                                <div class="d-flex align-items-center gap-2 mt-1 mb-2"><span class="small fw-semibold">{{ __('nexora.recommendation.score') }}</span><strong>{{ number_format($item->recommendation['score'], 0) }}%</strong></div>
                                <div class="progress mb-3" role="progressbar" aria-label="{{ __('nexora.recommendation.score') }}" aria-valuenow="{{ $item->recommendation['score'] }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width:{{ $item->recommendation['score'] }}%"></div></div>
                                <details class="small mb-3"><summary class="fw-semibold">{{ __('nexora.recommendation.explanation') }}</summary><p class="mt-2 mb-2">{{ $item->recommendation_explanation }}</p><strong>{{ __('nexora.recommendation.criteria') }}</strong><p class="mb-1">{{ $matched->isEmpty() ? '—' : $matched->map(fn($criterion) => __('nexora.criteria.'.$criterion))->join(', ') }}</p><strong>{{ __('nexora.recommendation.missing') }}</strong><p class="mb-0">{{ empty($missing) ? '—' : collect($missing)->map(fn($criterion) => __('nexora.criteria.'.$criterion))->join(', ') }}</p></details>
                                <div class="mt-auto d-flex flex-wrap gap-2">
                                    @if($isEvent)
                                        <a class="btn btn-outline-primary btn-sm flex-fill" href="{{ route('front.evenements.show', $item) }}">{{ __('nexora.events.view') }}</a>
                                        @if($item->inscriptionsOuvertes())
                                            @auth
                                                @if($item->participations->isNotEmpty())<span class="badge text-bg-success align-content-center">{{ __('nexora.events.registered') }}</span>@else<form method="POST" action="{{ route('front.evenements.inscrire', $item) }}" class="flex-fill">@csrf<button class="btn btn-primary btn-sm w-100" type="submit">{{ __('nexora.events.participate') }}</button></form>@endif
                                            @else<a class="btn btn-primary btn-sm flex-fill" href="{{ route('login') }}">{{ __('nexora.events.login') }}</a>@endauth
                                        @endif
                                    @else
                                        <a class="btn btn-outline-primary btn-sm flex-fill" href="{{ route('front.equipements.show', $item) }}">{{ __('nexora.'.$mode.'.details') }}</a>
                                        @if($mode === 'booking')<a class="btn btn-primary btn-sm flex-fill" href="{{ route('front.reservations.create', ['equipement' => $item->id, 'date_debut' => $filters['date_start'], 'date_fin' => $filters['date_end']]) }}">{{ __('nexora.booking.reserve') }}</a>@else<a class="btn btn-primary btn-sm flex-fill" href="{{ route('front.reservations.create', ['equipement' => $item->id]) }}">{{ __('nexora.energy.reserve') }}</a>@endif
                                    @endif
                                </div>
                                @auth<a class="small mt-2" href="{{ route('ai.front.show', $isEvent ? 'evenements' : ($mode === 'booking' ? 'locations' : 'equipements')) }}">{{ __('nexora.common.ask_assistant') }}</a>@endauth
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('form[method="GET"]').forEach(form => form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');
    if (button) { button.disabled = true; button.setAttribute('aria-busy', 'true'); button.textContent = @json(__('nexora.common.loading')); }
}));
</script>
@endpush

