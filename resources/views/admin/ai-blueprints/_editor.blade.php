<form method="POST" action="{{ $action }}" class="card card-body">
    @csrf
    @if($method !== 'POST') @method($method) @endif
    @if(session('success')) <div class="alert alert-success" role="status">{{ session('success') }}</div> @endif
    <h2 class="h5 mb-3">{{ __('Edit proposal') }}</h2>
    <div class="row g-3">
        <div class="col-md-8"><label class="form-label" for="title">{{ __('Title') }}</label><input id="title" class="form-control" name="title" value="{{ old('title', $proposal['title'] ?? '') }}" required minlength="10" maxlength="180"></div>
        <div class="col-md-4"><label class="form-label" for="proposal_type">{{ __('Event type') }}</label><select id="proposal_type" class="form-select" name="type" required>@foreach(\App\Models\Evenement::TYPES as $value => [$label, $icon])<option value="{{ $value }}" @selected(old('type', $proposal['type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label" for="description">{{ __('Description') }}</label><textarea id="description" class="form-control" name="description" rows="5" required minlength="30" maxlength="2500">{{ old('description', $proposal['description'] ?? '') }}</textarea></div>
        @foreach(['target_audience' => 'Target audience', 'suggested_venue' => 'Suggested venue', 'suggested_city' => 'Suggested city', 'suggested_start' => 'Suggested date or period', 'suggested_end' => 'Suggested end', 'recommended_capacity' => 'Recommended capacity', 'estimated_budget' => 'Estimated budget'] as $field => $label)
            <div class="col-md-4"><label class="form-label" for="{{ $field }}">{{ __($label) }}</label><input id="{{ $field }}" class="form-control" name="{{ $field }}" value="{{ old($field, $proposal[$field] ?? '') }}"></div>
        @endforeach
        @foreach(['objectives' => 'Objectives', 'programme' => 'Programme', 'activities' => 'Activities', 'equipment' => 'Equipment needed', 'organisation' => 'Organisation recommendations'] as $field => $label)
            @php($listValue = old($field, $proposal[$field] ?? []))
            @php($listValue = is_array($listValue) ? implode("\n", $listValue) : $listValue)
            <div class="col-md-6"><label class="form-label" for="{{ $field }}">{{ __($label) }}</label><textarea id="{{ $field }}" class="form-control" name="{{ $field }}[]" rows="4" aria-describedby="{{ $field }}_help">{{ $listValue }}</textarea><div class="form-text" id="{{ $field }}_help">{{ __('One item per line.') }}</div></div>
        @endforeach
    </div>
    <div class="mt-3 d-flex flex-wrap gap-2">
        <button class="btn btn-success" type="submit"><i class="bi bi-save me-1"></i>{{ __('Save draft') }}</button>
        @if(isset($blueprint))
            <a class="btn btn-outline-primary" href="{{ route('ai.admin.blueprints.prefill', $blueprint) }}"><i class="bi bi-box-arrow-in-down me-1"></i>{{ __('Prepare event creation') }}</a>
        @endif
    </div>
</form>
