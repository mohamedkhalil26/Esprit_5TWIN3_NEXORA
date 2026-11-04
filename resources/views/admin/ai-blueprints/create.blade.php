@extends('layouts.admin')
@section('title', __('Event Blueprint Generator'))
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h1 class="h3 mb-1">{{ __('Event Blueprint Generator') }}</h1><p class="text-muted mb-0">{{ __('Create an editable event proposal. Nothing is published automatically.') }}</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('ai.admin.blueprints.index') }}">{{ __('My drafts') }}</a>
</div>
@if(session('error')) <div class="alert alert-danger" role="alert">{{ session('error') }}</div> @endif
@if($errors->any()) <div class="alert alert-danger" role="alert"><strong>{{ __('Please review the highlighted information.') }}</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
<form method="POST" action="{{ route('ai.admin.blueprints.generate') }}" class="card card-body mb-4" id="blueprint-generator">@csrf
    <h2 class="h5 mb-3">{{ __('Event details') }}</h2>
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" for="theme">{{ __('Theme') }}</label><input id="theme" class="form-control" name="theme" value="{{ old('theme', $input['theme'] ?? '') }}" required maxlength="180"></div>
        <div class="col-md-6"><label class="form-label" for="objective">{{ __('Objective') }}</label><input id="objective" class="form-control" name="objective" value="{{ old('objective', $input['objective'] ?? '') }}" required maxlength="600"></div>
        <div class="col-md-4"><label class="form-label" for="target_audience">{{ __('Target audience') }}</label><input id="target_audience" class="form-control" name="target_audience" value="{{ old('target_audience', $input['target_audience'] ?? '') }}" required maxlength="240"></div>
        <div class="col-md-2"><label class="form-label" for="type">{{ __('Event type') }}</label><select id="type" class="form-select" name="type" required>@foreach(\App\Models\Evenement::TYPES as $value => [$label, $icon])<option value="{{ $value }}" @selected(old('type', $input['type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label" for="duration">{{ __('Duration') }}</label><input id="duration" class="form-control" name="duration" value="{{ old('duration', $input['duration'] ?? '') }}" required maxlength="60"></div>
        <div class="col-md-2"><label class="form-label" for="participants">{{ __('Participants') }}</label><input id="participants" class="form-control" type="number" name="participants" min="1" max="500" value="{{ old('participants', $input['participants'] ?? 20) }}" required></div>
        <div class="col-md-2"><label class="form-label" for="tone">{{ __('Tone') }}</label><input id="tone" class="form-control" name="tone" value="{{ old('tone', $input['tone'] ?? __('Professional')) }}" required maxlength="80"></div>
    </div>
    <div class="mt-3 d-flex flex-wrap gap-2 align-items-center"><button class="btn btn-primary" type="submit" data-submit-label="{{ __('Generate proposal') }}"><i class="bi bi-stars me-1"></i>{{ __('Generate proposal') }}</button><span class="text-muted d-none" data-loading role="status"><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>{{ __('Generating proposal…') }}</span></div>
</form>
@if($proposal)
    <div class="alert alert-info">{{ __('Review and edit the suggestions before saving. Unknown details should be confirmed.') }}</div>
    @include('admin.ai-blueprints._editor', ['action' => route('ai.admin.blueprints.store'), 'method' => 'POST', 'proposal' => $proposal])
@endif
@endsection
@push('scripts')
<script>
document.getElementById('blueprint-generator')?.addEventListener('submit', function () {
    const button = this.querySelector('[type="submit"]');
    button.disabled = true;
    this.querySelector('[data-loading]').classList.remove('d-none');
});
</script>
@endpush
