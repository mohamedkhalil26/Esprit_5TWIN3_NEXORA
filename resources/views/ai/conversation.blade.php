@extends($audience === 'admin' ? 'layouts.admin' : 'layouts.front')
@section('title', $title)
@section('body-class', 'nav-solid')
@push('styles')<link href="{{ asset('css/nexora-intelligence.css') }}" rel="stylesheet">@endpush

@section('content')
@php
    $hasSuccessfulResponse = $messages->last()?->role === 'assistant';
@endphp
<section class="{{ $audience === 'admin' ? 'py-3' : 'py-5 mt-5' }}">
    <div class="container-fluid px-3 px-lg-4" style="max-width:1440px">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <p class="small text-uppercase fw-semibold text-primary mb-1">{{ __('ai_conversation.identity') }} · {{ __('ai_conversation.'.$audience.'_area') }}</p>
                <h1 class="h3 mb-2">{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $description }}</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('ai.index') }}"><i class="bi bi-grid me-1" aria-hidden="true"></i>{{ __('ai_conversation.all_assistants') }}</a>
        </div>

        @if ($audience === 'admin' && in_array($module, ['reclamations', 'evenements', 'blog'], true))
            <div class="mb-3"><a class="btn btn-sm btn-outline-primary" href="{{ route('ai.show', $module) }}">{{ __('ai_conversation.open_related') }}</a></div>
        @endif
        @if (session('error') && ! session('ai_response_error'))<div class="alert alert-danger" role="alert" aria-live="assertive">{{ session('error') }}</div>@endif
        @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
        @if ($selectedRecord)<div class="alert alert-info" role="status">{{ __('ai_conversation.selected_context', ['record' => $selectedRecord->titre ?? $selectedRecord->sujet]) }}</div>@endif

        <div class="row g-3">
            <aside class="col-lg-3 order-2 order-lg-1">
                <div class="card ai-chat-history h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0">{{ __('ai_conversation.history') }}</h2>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route($audience === 'admin' ? 'ai.admin.show' : 'ai.front.show', $module) }}">{{ __('ai_conversation.new_conversation') }}</a>
                    </div>
                    <div class="list-group list-group-flush ai-chat-history-list">
                        @forelse ($conversations as $item)
                            <div class="list-group-item d-flex align-items-center gap-2">
                                <a class="ai-conversation-link list-group-item-action rounded p-2 flex-grow-1 {{ $conversation?->id === $item->id ? 'active' : '' }}" href="{{ route($audience === 'admin' ? 'ai.admin.show' : 'ai.front.show', ['module' => $module, 'conversation_id' => $item->id]) }}">
                                    <span class="d-block small fw-semibold">{{ $item->title }}</span>
                                    @php
                                        $ageMinutes = (int) $item->updated_at->diffInMinutes(now());
                                    @endphp
                                    <time class="small text-muted" datetime="{{ $item->updated_at->toAtomString() }}">
                                        @if ($ageMinutes === 0)
                                            {{ __('ai_conversation.time.just_now') }}
                                        @elseif ($ageMinutes < 60)
                                            {{ trans_choice('ai_conversation.time.minutes_ago', $ageMinutes, ['count' => $ageMinutes]) }}
                                        @elseif ($ageMinutes < 1440)
                                            @php
                                                $ageHours = (int) floor($ageMinutes / 60);
                                            @endphp
                                            {{ trans_choice('ai_conversation.time.hours_ago', $ageHours, ['count' => $ageHours]) }}
                                        @else
                                            {{ $item->updated_at->locale(app()->getLocale())->translatedFormat('d M Y · H:i') }}
                                        @endif
                                    </time>
                                </a>
                                <form method="POST" action="{{ route($audience === 'admin' ? 'ai.admin.destroy' : 'ai.front.destroy', ['module' => $module, 'conversation' => $item->id]) }}" onsubmit="return confirm(@json(__('ai_conversation.delete_confirmation')))" aria-label="{{ __('ai_conversation.delete_conversation') }}">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" aria-label="{{ __('ai_conversation.delete_conversation') }}" title="{{ __('ai_conversation.delete') }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        @empty
                            <p class="text-muted small p-3 mb-0">{{ __('ai_conversation.empty_history') }}</p>
                        @endforelse
                    </div>
                </div>
            </aside>

            <div class="col-lg-9 order-1 order-lg-2">
                <div class="ai-chat-shell">
                    <div class="ai-chat-header p-3 p-md-4">
                        <div class="d-flex align-items-center gap-3">
                            <span class="rounded-circle bg-white bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width:2.75rem;height:2.75rem"><i class="bi bi-stars fs-4" aria-hidden="true"></i></span>
                            <div><h2 class="h5 mb-1">{{ $title }}</h2><p class="small mb-0 text-white-50">{{ __('ai_conversation.structured_context') }}</p></div>
                        </div>
                    </div>
                    <div class="ai-chat-log p-3 p-md-4" id="ai-chat-log" aria-live="polite" aria-label="{{ __('ai_conversation.messages_label') }}">
                        @forelse ($messages as $message)
                            @if ($message->role === 'user')
                                <div class="ai-message ai-message-user p-3 mb-3"><span class="ai-message-meta">{{ __('ai_conversation.you') }}</span><div>{{ $message->content }}</div></div>
                            @else
                                @php
                                    $payload = json_decode($message->content, true) ?: [];
                                @endphp
                                <div class="ai-message ai-message-assistant p-3 mb-3">
                                    <span class="ai-message-meta">{{ __('ai_conversation.assistant') }}</span>
                                    <p class="mb-0">{{ $payload['answer'] ?? $payload['summary'] ?? __('ai_conversation.response_fallback') }}</p>
                                    @if (!empty($payload['draft']))
                                        <div class="mt-3">
                                            <label class="form-label small fw-semibold" for="claim-draft">{{ __('ai_conversation.claim_draft') }}</label>
                                            <textarea class="form-control" id="claim-draft" rows="4" readonly>{{ $payload['draft'] }}</textarea>
                                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-copy-target="claim-draft">{{ __('ai_conversation.copy_draft') }}</button>
                                            <span class="small text-muted ms-2" data-copy-status aria-live="polite"></span>
                                            @if ($audience === 'front')<a class="btn btn-sm btn-link mt-2" href="{{ route('front.reclamations.create') }}">{{ __('ai_conversation.open_form_without_sending') }}</a>@endif
                                        </div>
                                        <p class="small text-muted mt-2 mb-0">{{ __('ai_conversation.indicative_claim', ['category' => $payload['category'] ?? '—', 'priority' => $payload['priority'] ?? '—', 'sentiment' => $payload['sentiment'] ?? '—', 'urgency' => $payload['urgency'] ?? '—']) }}</p>
                                        @if (!empty($payload['missing_information']))<p class="small text-muted mb-0">{{ __('ai_conversation.missing_information', ['items' => implode(', ', $payload['missing_information'])]) }}</p>@endif
                                    @endif
                                    @foreach (['insights' => __('ai_conversation.insights'), 'recommendations' => __('ai_conversation.recommendations')] as $key => $label)
                                        @if (!empty($payload[$key]))
                                            <h3 class="h6 mt-3 mb-2">{{ $label }}</h3>
                                            <ul class="mb-0">@foreach ($payload[$key] as $item)<li>{{ $item }}</li>@endforeach</ul>
                                        @endif
                                    @endforeach
                                    @if (!empty($payload['description_proposee']))
                                        <div class="mt-3"><label class="form-label fw-semibold" for="equipment-description-{{ $message->id }}">{{ __('ai_conversation.proposed_description') }}</label><textarea class="form-control" id="equipment-description-{{ $message->id }}" rows="5" readonly>{{ $payload['description_proposee'] }}</textarea><button type="button" class="btn btn-sm btn-outline-primary mt-2" data-copy-target="equipment-description-{{ $message->id }}">{{ __('ai_conversation.copy_description') }}</button><span class="small text-muted ms-2" data-copy-status aria-live="polite"></span> <a href="{{ route('admin.equipements.index') }}" class="btn btn-sm btn-link">{{ __('ai_conversation.open_catalog') }}</a></div>
                                    @endif
                                    @if ($module === 'evenements' && (!empty($payload['draft_title']) || !empty($payload['draft_description'])))
                                        <div class="mt-3"><h3 class="h6">{{ __('ai_conversation.event_draft') }}</h3>
                                            @foreach (['draft_title' => __('ai_conversation.proposed_title'), 'draft_description' => __('ai_conversation.proposed_description')] as $key => $label)
                                                @if (!empty($payload[$key]))<label class="form-label small" for="{{ $key }}-{{ $message->id }}">{{ $label }}</label><textarea class="form-control mb-2" id="{{ $key }}-{{ $message->id }}" rows="{{ $key === 'draft_title' ? 2 : 5 }}" readonly>{{ $payload[$key] }}</textarea>@endif
                                            @endforeach
                                            @if (!empty($payload['draft_program']))<p class="small fw-semibold mb-1">{{ __('ai_conversation.proposed_program') }}</p><ul>@foreach ($payload['draft_program'] as $step)<li>{{ $step }}</li>@endforeach</ul>@endif
                                            <a href="{{ route('admin.evenements.create') }}" class="btn btn-sm btn-outline-primary">{{ __('ai_conversation.open_event_form') }}</a>
                                        </div>
                                    @endif
                                    @if ($module === 'blog' && (!empty($payload['draft_title']) || !empty($payload['draft_content'])))
                                        <div class="mt-3"><h3 class="h6">{{ __('ai_conversation.article_draft') }}</h3>
                                            @foreach (['draft_title' => __('ai_conversation.proposed_title'), 'draft_resume' => __('ai_conversation.proposed_summary'), 'draft_outline' => __('ai_conversation.proposed_outline'), 'draft_content' => __('ai_conversation.proposed_content')] as $key => $label)
                                                @if (!empty($payload[$key]))<label class="form-label small" for="{{ $key }}-{{ $message->id }}">{{ $label }}</label>@if(is_array($payload[$key]))<ul>@foreach($payload[$key] as $outline)<li>{{ $outline }}</li>@endforeach</ul>@else<textarea class="form-control mb-2" id="{{ $key }}-{{ $message->id }}" rows="{{ $key === 'draft_content' ? 8 : 3 }}" readonly>{{ $payload[$key] }}</textarea>@endif @endif
                                            @endforeach
                                            <a href="{{ route('admin.articles.create') }}" class="btn btn-sm btn-outline-primary">{{ __('ai_conversation.open_article_form') }}</a>
                                        </div>
                                    @endif
                                    @php
                                        $idsKey = ['equipements' => 'equipment_ids', 'locations' => 'equipment_ids', 'evenements' => 'event_ids', 'blog' => 'article_ids'][$module] ?? null;
                                        $suggestedIds = $idsKey ? ($payload[$idsKey] ?? []) : [];
                                    @endphp
                                    @if ($suggestedIds)
                                        <h3 class="h6 mt-3 mb-2">{{ __('ai_conversation.recommended_items') }}</h3>
                                        @if (!empty($payload['recommendation_details']))<div class="small text-muted mb-2">{{ __('ai_conversation.score_details') }}</div>@foreach($payload['recommendation_details'] as $detail)<div class="small border-start border-primary ps-2 mb-2">{{ $detail['nom'] ?? $detail['titre'] ?? __('ai_conversation.resource') }} · {{ __('ai_conversation.score') }} {{ number_format((float) ($detail['ai_score'] ?? $detail['score'] ?? 0), 2, ',', ' ') }}/100 · {{ __('ai_conversation.confidence') }} {{ number_format((float) ($detail['ai_confidence'] ?? $detail['confiance'] ?? 0), 2, ',', ' ') }}%@if(!empty($detail['ai_score_missing'])) · {{ __('ai_conversation.missing_criteria') }}: {{ implode(', ', $detail['ai_score_missing']) }}@endif</div>@endforeach @endif
                                        <div class="d-grid gap-2">
                                            @foreach ($references->whereIn('id', $suggestedIds) as $reference)
                                                @php
                                                    [$referenceTitle, $referenceUrl, $referenceDescription] = match ($module) {
                                                        'equipements', 'locations' => [$reference->nom, route('front.equipements.show', $reference), number_format((float) $reference->prix_jour, 2, ',', ' ').' DT '.__('ai_conversation.per_day').' · '.($reference->ville ?? __('ai_conversation.city_unspecified'))],
                                                        'evenements' => [$reference->titre, route('front.evenements.show', $reference), $reference->date_debut?->locale(app()->getLocale())->translatedFormat('d M Y H:i').' · '.($reference->ville ?? '').' · '.trans_choice('ai_conversation.places_remaining', (int) ($reference->places_restantes ?? 0), ['count' => (int) ($reference->places_restantes ?? 0)])],
                                                        'blog' => [$reference->titre, route('front.blog.show', $reference->slug), $reference->resume],
                                                    };
                                                @endphp
                                                <article class="card"><div class="card-body py-2 d-flex flex-wrap justify-content-between align-items-center gap-2"><div><h4 class="h6 mb-1">{{ $referenceTitle }}</h4><p class="small text-muted mb-0">{{ $referenceDescription }}</p></div><a class="btn btn-sm btn-outline-primary" href="{{ $referenceUrl }}">{{ __('ai_conversation.view') }}</a></div></article>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @empty
                            <div class="text-center py-4 py-md-5">
                                <span class="ai-icon rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:3.5rem;height:3.5rem"><i class="bi bi-stars fs-3" aria-hidden="true"></i></span>
                                <h3 class="h5">{{ __('ai_conversation.empty_title') }}</h3>
                                <p class="text-muted">{{ __('ai_conversation.empty_description') }}</p>
                                <div class="d-flex flex-wrap justify-content-center gap-2">
                                    @foreach ($suggestions as $suggestion)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-prompt="{{ $suggestion }}">{{ $suggestion }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endforelse
                        @if (session('ai_response_error'))
                            <section class="ai-message-error p-3 p-md-4" role="alert" aria-live="assertive">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="ai-error-icon" aria-hidden="true"><i class="bi bi-exclamation-circle"></i></span>
                                    <div class="flex-grow-1">
                                        <h3 class="h6 mb-1">{{ __('ai_conversation.response_failed') }}</h3>
                                        <p class="small mb-2">{{ session('ai_response_error_message') }}</p>
                                        <p class="small text-muted mb-3">{{ __('ai_conversation.retry_hint') }}</p>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" form="ai-chat-form" name="retry" value="1"><i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>{{ __('ai_conversation.retry') }}</button>
                                    </div>
                                </div>
                            </section>
                        @endif
                    </div>

                    @if ($hasSuccessfulResponse)
                        <form method="POST" action="{{ route('ai.feedback.store') }}" class="border-top p-3" aria-label="{{ __('ai_conversation.feedback_aria') }}">
                            @csrf
                            <input type="hidden" name="feature" value="{{ $module }}">
                            <input type="hidden" name="conversation_id" value="{{ $conversation->id }}">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4"><label class="form-label small" for="feedback-type">{{ __('ai_conversation.feedback_usefulness') }}</label><select class="form-select form-select-sm" id="feedback-type" name="feedback_type"><option value="helpful">{{ __('ai_conversation.helpful') }}</option><option value="not_helpful">{{ __('ai_conversation.not_helpful') }}</option><option value="incorrect">{{ __('ai_conversation.incorrect') }}</option><option value="irrelevant">{{ __('ai_conversation.irrelevant') }}</option></select></div>
                                <div class="col-md-2"><label class="form-label small" for="feedback-rating">{{ __('ai_conversation.rating') }}</label><select class="form-select form-select-sm" id="feedback-rating" name="rating"><option value="">{{ __('ai_conversation.not_rated') }}</option>@for($rating = 1; $rating <= 5; $rating++)<option value="{{ $rating }}">{{ $rating }}/5</option>@endfor</select></div>
                                <div class="col-md-4"><label class="form-label small" for="feedback-comment">{{ __('ai_conversation.optional_comment') }}</label><input class="form-control form-control-sm" id="feedback-comment" name="comment" maxlength="1000"></div>
                                <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100" type="submit">{{ __('ai_conversation.submit_feedback') }}</button></div>
                            </div>
                        </form>
                        @if ($userFeedback->isNotEmpty())
                            <div class="px-3 pb-3"><p class="small text-muted mb-1">{{ __('ai_conversation.recent_feedback') }}</p>@foreach($userFeedback as $feedback)<div class="small d-flex justify-content-between align-items-center gap-2"><span>{{ $feedback->feedback_type }}{{ $feedback->rating ? ' · '.$feedback->rating.'/5' : '' }}</span><form method="POST" action="{{ route('ai.feedback.destroy', $feedback) }}">@csrf @method('DELETE')<button class="btn btn-link btn-sm p-0" type="submit">{{ __('ai_conversation.delete') }}</button></form></div>@endforeach</div>
                        @endif
                    @endif

                    <form method="POST" action="{{ route($audience === 'admin' ? 'ai.admin.send' : 'ai.front.send', $module) }}" class="ai-composer border-top p-3 p-md-4" id="ai-chat-form">
                        @csrf
                        @if ($conversation)<input type="hidden" name="conversation_id" value="{{ $conversation->id }}">@endif
                        @foreach (array_intersect_key($selectedContext, array_flip(['article_id', 'event_id', 'claim_id'])) as $contextKey => $contextId)<input type="hidden" name="{{ $contextKey }}" value="{{ $contextId }}">@endforeach
                        @if ($audience === 'front' && $module === 'locations')
                            <div class="row g-2 mb-3">
                                <div class="col-sm-4"><label class="form-label small" for="budget">{{ __('ai_conversation.front_locations.budget') }}</label><input class="form-control" id="budget" name="budget" type="number" min="0" max="100000" step="0.01" value="{{ old('budget', $selectedContext['budget'] ?? '') }}"></div>
                                <div class="col-sm-4"><label class="form-label small" for="date_debut">{{ __('ai_conversation.front_locations.start_date') }}</label><input class="form-control" id="date_debut" name="date_debut" type="date" min="{{ today()->toDateString() }}" value="{{ old('date_debut', $selectedContext['date_debut'] ?? request('date_debut')) }}"></div>
                                <div class="col-sm-4"><label class="form-label small" for="date_fin">{{ __('ai_conversation.front_locations.end_date') }}</label><input class="form-control" id="date_fin" name="date_fin" type="date" min="{{ today()->toDateString() }}" value="{{ old('date_fin', $selectedContext['date_fin'] ?? request('date_fin')) }}"></div>
                            </div>
                        @elseif ($audience === 'front' && $module === 'equipements')
                            <div class="row g-2 mb-3"><div class="col-sm-4"><label class="form-label small" for="budget">{{ __('ai_conversation.front_equipment.budget') }}</label><input class="form-control" id="budget" name="budget" type="number" min="0" max="100000" step="0.01" value="{{ old('budget', $selectedContext['budget'] ?? '') }}"></div><div class="col-sm-4"><label class="form-label small" for="ai-chat-ville">{{ __('ai_conversation.front_equipment.city') }}</label><input class="form-control" id="ai-chat-ville" name="ville" maxlength="120" value="{{ old('ville', $selectedContext['ville'] ?? '') }}"></div><div class="col-sm-4"><label class="form-label small" for="ai-chat-puissance">{{ __('ai_conversation.front_equipment.power') }}</label><input class="form-control" id="ai-chat-puissance" name="puissance_min" type="number" min="0" max="1000000" value="{{ old('puissance_min', $selectedContext['puissance_min'] ?? '') }}"></div></div>
                        @endif
                        <label class="form-label visually-hidden" for="question">{{ __('ai_conversation.question_label') }}</label>
                        <div class="ai-composer-controls">
                            <textarea class="form-control" id="question" name="question" rows="2" minlength="3" maxlength="2000" placeholder="{{ __('ai_conversation.question_placeholder') }}" required>{{ old('question') }}</textarea>
                            <button class="btn btn-primary" id="ai-send" type="submit"><i class="bi bi-send me-1" aria-hidden="true"></i>{{ __('ai_conversation.send') }}</button>
                        </div>
                        <p class="small text-muted mt-2 mb-0">{{ __('ai_conversation.privacy_note') }}</p>
                        <p id="ai-loading" class="small text-primary mt-2 mb-0 d-none" role="status" aria-live="polite"><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>{{ __('ai_conversation.loading') }}</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-prompt]').forEach(button => button.addEventListener('click', () => {
    const field = document.getElementById('question');
    field.value = button.dataset.prompt;
    field.focus();
}));
document.querySelectorAll('[data-copy-target]').forEach(button => button.addEventListener('click', async () => {
    const field = document.getElementById(button.dataset.copyTarget);
    const status = button.parentElement.querySelector('[data-copy-status]');
    try { await navigator.clipboard.writeText(field.value); status.textContent = @json(__('ai_conversation.copy_done')); }
    catch { field.select(); status.textContent = @json(__('ai_conversation.copy_manual')); }
}));
document.getElementById('ai-chat-form').addEventListener('submit', () => {
    document.getElementById('ai-send').disabled = true;
    document.getElementById('ai-loading').classList.remove('d-none');
});
const log = document.getElementById('ai-chat-log');
if (log && {{ $messages->isNotEmpty() ? 'true' : 'false' }}) log.scrollTop = log.scrollHeight;
</script>
@endpush
