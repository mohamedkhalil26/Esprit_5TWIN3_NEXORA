@extends('layouts.admin')
@section('title', __('Edit blueprint'))
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h1 class="h3 mb-1">{{ __('Edit blueprint') }}</h1><p class="text-muted mb-0">{{ __('Review the proposal and confirm any suggested details.') }}</p></div><a class="btn btn-outline-secondary" href="{{ route('ai.admin.blueprints.index') }}">{{ __('Back to drafts') }}</a></div>
@include('admin.ai-blueprints._editor', ['action' => route('ai.admin.blueprints.update', $blueprint), 'method' => 'PUT', 'proposal' => $blueprint->proposal_data, 'blueprint' => $blueprint])
@endsection
