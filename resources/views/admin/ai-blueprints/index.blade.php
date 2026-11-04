@extends('layouts.admin')

@section('title', __('Event Blueprint Generator'))

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <p class="text-uppercase small fw-semibold text-success mb-1">{{ __('ai_hub.modules.evenements.back_title') }}</p>
            <h1 class="h3 mb-1">{{ __('Event Blueprint Generator') }}</h1>
            <p class="text-muted mb-0">{{ __('Your saved proposals are drafts and are not published.') }}</p>
        </div>
        <a class="btn btn-primary" href="{{ route('ai.admin.blueprints.create') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>{{ __('New blueprint') }}</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <x-admin.card :title="__('My drafts')" icon="calendar-event">
        @if($blueprints->isEmpty())
            <div class="text-center py-4 py-lg-5">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success mb-3" style="width:4rem;height:4rem" aria-hidden="true"><i class="bi bi-calendar2-plus fs-3"></i></span>
                <h2 class="h5">{{ __('You have no saved drafts yet.') }}</h2>
                <p class="text-muted mx-auto" style="max-width:36rem">{{ __('Start with an event theme, audience and objective. The assistant will prepare an editable proposal; nothing is published automatically.') }}</p>
                <a class="btn btn-primary" href="{{ route('ai.admin.blueprints.create') }}"><i class="bi bi-stars me-1" aria-hidden="true"></i>{{ __('Create your first blueprint') }}</a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th scope="col">{{ __('Title') }}</th><th scope="col">{{ __('Status') }}</th><th scope="col">{{ __('Modified') }}</th><th scope="col">{{ __('Actions') }}</th></tr></thead>
                    <tbody>
                        @foreach($blueprints as $blueprint)
                            <tr>
                                <td class="fw-semibold">{{ $blueprint->title }}</td>
                                <td><span class="badge text-bg-secondary">{{ __('Draft') }}</span></td>
                                <td>{{ $blueprint->updated_at->format('d/m/Y H:i') }}</td>
                                <td><div class="d-flex flex-wrap gap-1"><a class="btn btn-sm btn-outline-primary" href="{{ route('ai.admin.blueprints.edit', $blueprint) }}">{{ __('Open') }}</a><form method="POST" action="{{ route('ai.admin.blueprints.destroy', $blueprint) }}" onsubmit="return confirm(@js(__('Delete this draft?')))" >@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">{{ __('Delete') }}</button></form></div></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $blueprints->links() }}
        @endif
    </x-admin.card>
@endsection
