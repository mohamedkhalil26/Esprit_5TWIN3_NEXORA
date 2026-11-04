@extends('layouts.admin')

@section('title', __('complaints.intelligence.title'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">{{ __('complaints.intelligence.title') }}</h1>
        <a class="btn btn-outline-secondary" href="{{ route('admin.reclamations.index') }}">{{ __('complaints.intelligence.list') }}</a>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([__('complaints.intelligence.total') => $statistics['total'], __('complaints.intelligence.pending') => $statistics['pending'], __('complaints.intelligence.progress') => $statistics['in_progress'], __('complaints.intelligence.resolved') => $statistics['resolved']] as $label => $value)
            <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }}</div><div class="fs-3 fw-bold">{{ $value }}</div></div></div></div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4"><div class="card h-100"><div class="card-header fw-semibold">{{ __('complaints.intelligence.categories') }}</div><div class="card-body"><ul class="list-group list-group-flush">@foreach ($statistics['by_category'] as $key => $count)<li class="list-group-item d-flex justify-content-between"><span>{{ __(\App\Models\Reclamation::CATEGORIES[$key][0]) }}</span><strong>{{ $count }}</strong></li>@endforeach</ul></div></div></div>
        <div class="col-lg-4"><div class="card h-100"><div class="card-header fw-semibold">{{ __('complaints.intelligence.priorities') }}</div><div class="card-body"><ul class="list-group list-group-flush">@foreach ($statistics['by_priority'] as $key => $count)<li class="list-group-item d-flex justify-content-between"><span>{{ __(\App\Models\Reclamation::PRIORITES[$key][0]) }}</span><strong>{{ $count }}</strong></li>@endforeach</ul><hr><div>{{ __('complaints.intelligence.average') }}: <strong>{{ $statistics['average_resolution_hours'] === null ? __('complaints.intelligence.no_data') : number_format($statistics['average_resolution_hours'], 1).' h' }}</strong></div></div></div></div>
        <div class="col-lg-4"><div class="card h-100"><div class="card-header fw-semibold">{{ __('complaints.intelligence.monthly') }}</div><div class="card-body">@foreach ($statistics['monthly'] as $month)<div class="d-flex justify-content-between border-bottom py-1"><span>{{ $month['label'] }}</span><strong>{{ $month['total'] }}</strong></div>@endforeach</div></div></div>
    </div>

    <div class="card mb-4"><div class="card-header fw-semibold">{{ __('complaints.intelligence.ai_title') }}</div><div class="card-body">
        <p class="text-muted">{{ __('complaints.intelligence.ai_description') }}</p>
        @if ($analysisMessage)<div class="alert alert-info">{{ $analysisMessage }}</div>@endif
        @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($analysis)
            <div class="alert alert-light border"><p class="mb-2">{{ $analysis['summary'] }}</p>
                @if (!empty($analysis['recurring_categories']))<h3 class="h6">{{ __('complaints.intelligence.recurring') }}</h3><ul>@foreach ($analysis['recurring_categories'] as $category)<li>{{ $category['label'] }} — {{ $category['count'] }}</li>@endforeach</ul>@endif
                <h3 class="h6">{{ __('complaints.intelligence.recommendations') }}</h3><ul class="mb-0">@foreach ($analysis['recommendations'] as $recommendation)<li>{{ $recommendation }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('admin.reclamations.intelligence.analyze', request()->query()) }}">@csrf<button class="btn btn-outline-primary" type="submit"><i class="bi bi-stars me-1"></i>{{ __('complaints.intelligence.analyze') }}</button></form>
    </div></div>

    <div class="card"><div class="card-header fw-semibold">{{ __('complaints.intelligence.records') }}</div>
        <form method="GET" class="row g-2 p-3 border-bottom">
            <div class="col-md-3"><label class="visually-hidden" for="q">{{ __('complaints.intelligence.search') }}</label><input id="q" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('complaints.intelligence.search') }}"></div>
            <div class="col-md-2"><select name="statut" class="form-select"><option value="">{{ __('complaints.intelligence.all_status') }}</option>@foreach (\App\Models\Reclamation::STATUTS as $key => [$label])<option value="{{ $key }}" @selected(($filters['statut'] ?? '') === $key)>{{ __($label) }}</option>@endforeach</select></div>
            <div class="col-md-2"><select name="categorie" class="form-select"><option value="">{{ __('complaints.intelligence.all_categories') }}</option>@foreach (\App\Models\Reclamation::CATEGORIES as $key => [$label])<option value="{{ $key }}" @selected(($filters['categorie'] ?? '') === $key)>{{ __($label) }}</option>@endforeach</select></div>
            <div class="col-md-2"><select name="priorite" class="form-select"><option value="">{{ __('complaints.intelligence.all_priorities') }}</option>@foreach (\App\Models\Reclamation::PRIORITES as $key => [$label])<option value="{{ $key }}" @selected(($filters['priorite'] ?? '') === $key)>{{ __($label) }}</option>@endforeach</select></div>
            <div class="col-md-1"><input type="date" name="du" class="form-control" aria-label="{{ __('complaints.intelligence.from') }}" value="{{ $filters['du'] ?? '' }}"></div><div class="col-md-1"><input type="date" name="au" class="form-control" aria-label="{{ __('complaints.intelligence.to') }}" value="{{ $filters['au'] ?? '' }}"></div>
            <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit">{{ __('complaints.intelligence.filter') }}</button></div>
        </form>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>#</th><th>{{ __('complaints.intelligence.subject') }}</th><th>{{ __('complaints.intelligence.member') }}</th><th>{{ __('complaints.intelligence.categories') }}</th><th>{{ __('complaints.intelligence.priorities') }}</th><th>{{ __('complaints.intelligence.status') }}</th><th>{{ __('complaints.intelligence.date') }}</th><th>{{ __('complaints.intelligence.actions') }}</th></tr></thead><tbody>
        @forelse ($reclamations as $reclamation)<tr><td>{{ $reclamation->id }}</td><td><a href="{{ route('admin.reclamations.show', $reclamation) }}">{{ \Illuminate\Support\Str::limit($reclamation->sujet, 45) }}</a><div class="small text-muted">{{ \Illuminate\Support\Str::limit($reclamation->description, 90) }}</div></td><td>{{ $reclamation->auteur?->name }}</td><td>{{ $reclamation->categorie_label }}</td><td>{{ $reclamation->priorite_label }}</td><td>{{ $reclamation->statut_label }}</td><td>{{ $reclamation->created_at?->format('d/m/Y') }}</td><td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.reclamations.show', $reclamation) }}" aria-label="{{ __('complaints.intelligence.open') }}">{{ __('complaints.intelligence.open') }}</a><a class="btn btn-sm btn-outline-secondary" href="{{ route('ai.show', ['module' => 'reclamations', 'id' => $reclamation->id]) }}">{{ __('complaints.intelligence.ai_record') }}</a></td></tr>@empty<tr><td colspan="8" class="text-center p-4">{{ __('complaints.intelligence.empty') }}</td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $reclamations->links() }}</div>
    </div>
@endsection
