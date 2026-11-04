@extends('layouts.admin')

@section('title', __('ai_insights.title'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-success mb-1">{{ __('ai_insights.eyebrow') }}</p>
            <h1 class="h3 mb-1">{{ __('ai_insights.title') }}</h1>
            <p class="text-muted mb-0">{{ __('ai_insights.description') }}</p>
        </div>
        <form method="GET" class="d-flex flex-wrap align-items-end gap-2">
            <div><label class="form-label small mb-1" for="months">{{ __('ai_insights.period') }}</label><select class="form-select" id="months" name="months"><option value="3" @selected($months === 3)>3 {{ __('ai_insights.months') }}</option><option value="6" @selected($months === 6)>6 {{ __('ai_insights.months') }}</option><option value="12" @selected($months === 12)>12 {{ __('ai_insights.months') }}</option></select></div>
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-arrow-repeat me-1" aria-hidden="true"></i>{{ __('ai_insights.refresh') }}</button>
        </form>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            [__('ai_insights.kpi_reservations'), $kpis['reservations_total'], 'calendar-check', 'primary'],
            [__('ai_insights.kpi_cancellations'), $kpis['cancellation_rate'] === null ? 'N/D' : $kpis['cancellation_rate'].' %', 'calendar-x', 'warning'],
            [__('ai_insights.kpi_urgent_claims'), $kpis['claims_urgent'], 'exclamation-triangle', 'danger'],
            [__('ai_insights.kpi_available'), $kpis['equipments_available'], 'sun', 'success'],
        ] as [$label, $value, $icon, $color])
            <div class="col-6 col-xl-3"><div class="info-box h-100 mb-0"><span class="info-box-icon text-bg-{{ $color }} shadow-sm"><i class="bi bi-{{ $icon }}" aria-hidden="true"></i></span><div class="info-box-content"><span class="info-box-text">{{ $label }}</span><span class="info-box-number">{{ $value }}</span></div></div></div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['reservations', 'calendar-check', 'primary'],
            ['cancellations', 'calendar-x', 'warning'],
            ['claims', 'chat-square-text', 'danger'],
            ['events', 'calendar-event', 'success'],
        ] as [$series, $icon, $color])
            @php($seriesMax = max(1, max($insights[$series])))
            <div class="col-12 col-md-6"><x-admin.card :title="__('ai_insights.series.'.$series)" :icon="$icon">
                <div class="ai-trend-chart" role="img" aria-label="{{ __('ai_insights.chart_label', ['series' => __('ai_insights.series.'.$series)]) }}">
                    @foreach($insights['periods'] as $index => $period)
                        @php($count = $insights[$series][$index])
                        <div class="ai-trend-column"><span class="ai-trend-value">{{ $count }}</span><div class="ai-trend-track"><span class="ai-trend-bar ai-trend-bar-{{ $color }}" style="height: {{ $count > 0 ? max(5, 100 * $count / $seriesMax) : 2 }}%"></span></div><span class="ai-trend-period">{{ $period }}</span></div>
                    @endforeach
                </div>
            </x-admin.card></div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <x-admin.card :title="__('ai_insights.monthly_detail')" icon="table">
                <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th scope="col">{{ __('ai_insights.period') }}</th><th scope="col">{{ __('ai_insights.series.reservations') }}</th><th scope="col">{{ __('ai_insights.series.cancellations') }}</th><th scope="col">{{ __('ai_insights.series.claims') }}</th><th scope="col">{{ __('ai_insights.series.events') }}</th></tr></thead><tbody>
                    @foreach($insights['periods'] as $index => $period)
                        <tr><th scope="row">{{ $period }}</th><td>{{ $insights['reservations'][$index] }}</td><td>{{ $insights['cancellations'][$index] }}</td><td>{{ $insights['claims'][$index] }}</td><td>{{ $insights['events'][$index] }}</td></tr>
                    @endforeach
                </tbody></table></div>
            </x-admin.card>
        </div>
        <div class="col-12 col-xl-4">
            <x-admin.card :title="__('ai_insights.demand_by_category')" icon="bar-chart-line">
                @if(count($insights['equipment_demand']))
                    @php($categoryMax = max($insights['equipment_demand']))
                    <div class="ai-category-list">@foreach($insights['equipment_demand'] as $category => $total)<div class="ai-category-row"><div class="d-flex justify-content-between gap-2 mb-1"><span class="text-truncate">{{ $category }}</span><strong>{{ $total }}</strong></div><div class="progress" role="progressbar" aria-label="{{ $category }}" aria-valuenow="{{ $total }}" aria-valuemin="0" aria-valuemax="{{ $categoryMax }}"><div class="progress-bar bg-success" style="width: {{ max(4, 100 * $total / $categoryMax) }}%"></div></div></div>@endforeach</div>
                @else
                    <div class="text-center py-4"><i class="bi bi-inbox fs-2 text-muted" aria-hidden="true"></i><p class="text-muted mb-0 mt-2">{{ __('ai_insights.empty_demand') }}</p></div>
                @endif
            </x-admin.card>
        </div>
    </div>
    <p class="small text-muted mt-3 mb-0"><i class="bi bi-database-check me-1" aria-hidden="true"></i>{{ __('ai_insights.data_note') }}</p>
@endsection

