@extends('layouts.front')

@section('title', 'Nouvelle réclamation')
@section('body-class', 'nav-solid')

@section('content')
    @include('front.partials.page-header', [
        'title' => 'Déposer une réclamation',
        'subtitle' => 'Décrivez votre problème, nous vous répondrons rapidement',
        'breadcrumb' => [
            'Mes réclamations' => route('front.reclamations.index'),
            'Nouvelle' => null
        ],
    ])

    <section class="py-5 bg-cream">
        <div class="container px-4 px-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <div class="card form-card">
                        <div class="card-body p-4 p-md-5">

                            <form method="POST"
                                  action="{{ route('front.reclamations.store') }}"
                                  enctype="multipart/form-data"
                                  novalidate>

                                @csrf

                                {{-- Type de problème --}}
                                <div class="mb-3">
                                    <div class="form-label">
                                        Type de problème
                                        <span class="text-danger">*</span>
                                    </div>

                                    <div class="row g-2">
                                        @foreach (\App\Models\Reclamation::CATEGORIES as $valeur => [$libelle, $icone])

                                            <div class="col-6 col-md-4">

                                                <input type="radio"
                                                       class="btn-check"
                                                       name="categorie"
                                                       id="cat_{{ $valeur }}"
                                                       value="{{ $valeur }}"
                                                       @checked(old('categorie') === $valeur)>

                                                <label class="btn btn-outline-primary w-100 py-3"
                                                       for="cat_{{ $valeur }}">

                                                    <i class="bi bi-{{ $icone }} d-block fs-4 mb-1"></i>

                                                    <small>
                                                        {{ $libelle }}
                                                    </small>

                                                </label>

                                            </div>

                                        @endforeach
                                    </div>

                                    @error('categorie')
                                        <div class="text-danger small mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>


                                {{-- Sujet --}}
                                <div class="mb-3">

                                    <label for="sujet" class="form-label">
                                        Sujet
                                        <span class="text-danger">*</span>
                                    </label>

                                    <input type="text"
                                           id="sujet"
                                           name="sujet"
                                           value="{{ old('sujet') }}"
                                           maxlength="150"
                                           class="form-control @error('sujet') is-invalid @enderror"
                                           placeholder="Résumez votre problème en une phrase">

                                    @error('sujet')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>


                                {{-- Description --}}
                                <div class="mb-3">

                                    <label for="description" class="form-label">
                                        Description
                                        <span class="text-danger">*</span>
                                    </label>

                                    <textarea id="description"
                                              name="description"
                                              rows="6"
                                              maxlength="3000"
                                              class="form-control @error('description') is-invalid @enderror"
                                              placeholder="Quand ? Quel équipement ? Que s'est-il passé ?">{{ old('description') }}</textarea>

                                    @error('description')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                </div>

                                <div class="border rounded p-3 mb-4 bg-white" id="complaint-doctor" data-url="{{ route('front.reclamations.improve') }}">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <div><strong><i class="bi bi-stars me-1" aria-hidden="true"></i>{{ __('complaints.doctor.title') }}</strong>
                                            <div class="small text-muted">{{ __('complaints.doctor.description') }}</div></div>
                                        <button class="btn btn-outline-primary" type="button" id="complaint-doctor-run"><span class="spinner-border spinner-border-sm me-1 d-none" id="complaint-doctor-spinner" aria-hidden="true"></span>{{ __('complaints.doctor.run') }}</button>
                                    </div>
                                    <div class="small text-muted mt-2">{{ __('complaints.doctor.privacy') }}</div>
                                    <div class="alert alert-danger mt-3 d-none" id="complaint-doctor-error" role="alert"></div>
                                    <div class="mt-3 d-none" id="complaint-doctor-result" aria-live="polite">
                                        <div class="border-start border-3 border-secondary ps-3 mb-3"><strong>{{ __('complaints.doctor.original') }}</strong><p class="mb-0" id="complaint-doctor-original"></p></div>
                                        <p><strong>{{ __('complaints.doctor.summary') }}:</strong> <span id="complaint-doctor-summary"></span></p>
                                        <p><strong>{{ __('complaints.doctor.explanation') }}:</strong> <span id="complaint-doctor-explanation"></span></p>
                                        <div><strong>{{ __('complaints.doctor.missing') }}</strong><ul id="complaint-doctor-missing" class="mb-2"></ul></div>
                                        <div class="row g-2">
                                            <div class="col-md-6"><strong>{{ __('complaints.doctor.subject') }}</strong><p id="complaint-doctor-subject" class="mb-1"></p><button type="button" class="btn btn-sm btn-outline-success" data-accept="improved_subject">{{ __('complaints.doctor.accept') }}</button> <button type="button" class="btn btn-sm btn-outline-secondary" data-refuse>{{ __('complaints.doctor.refuse_action') }}</button></div>
                                            <div class="col-md-6"><strong>{{ __('complaints.doctor.description_field') }}</strong><p id="complaint-doctor-description" class="mb-1 text-break"></p><button type="button" class="btn btn-sm btn-outline-success" data-accept="improved_description">{{ __('complaints.doctor.accept') }}</button> <button type="button" class="btn btn-sm btn-outline-secondary" data-refuse>{{ __('complaints.doctor.refuse_action') }}</button></div>
                                            <div class="col-md-6"><strong>{{ __('complaints.doctor.category') }}:</strong> <span id="complaint-doctor-category"></span><br><button type="button" class="btn btn-sm btn-outline-success mt-1" data-accept="category">{{ __('complaints.doctor.accept') }}</button> <button type="button" class="btn btn-sm btn-outline-secondary mt-1" data-refuse>{{ __('complaints.doctor.refuse_action') }}</button></div>
                                            <div class="col-md-6"><strong>{{ __('complaints.doctor.priority') }}:</strong> <span id="complaint-doctor-priority"></span><br><button type="button" class="btn btn-sm btn-outline-success mt-1" data-accept="priority">{{ __('complaints.doctor.accept') }}</button> <button type="button" class="btn btn-sm btn-outline-secondary mt-1" data-refuse>{{ __('complaints.doctor.refuse_action') }}</button></div>
                                        </div>
                                        <p class="small text-muted mt-3 mb-0">{{ __('complaints.doctor.refuse') }}</p>
                                    </div>
                                </div>


                                <div class="row g-3">

                                    {{-- Priorité --}}
                                    <div class="col-md-6">

                                        <label for="priorite" class="form-label">
                                            Urgence
                                            <span class="text-danger">*</span>
                                        </label>

                                        <select id="priorite"
                                                name="priorite"
                                                class="form-select @error('priorite') is-invalid @enderror">

                                            @foreach (\App\Models\Reclamation::PRIORITES as $valeur => [$libelle, $couleur])

                                                <option value="{{ $valeur }}"
                                                        @selected(old('priorite', 'moyenne') === $valeur)>
                                                    {{ $libelle }}
                                                </option>

                                            @endforeach

                                        </select>

                                        @error('priorite')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                    </div>


                                    {{-- Pièce jointe --}}
                                    <div class="col-md-6">

                                        <label for="piece_jointe" class="form-label">
                                            Pièce jointe (photo, facture...)
                                        </label>

                                        <input type="file"
                                               id="piece_jointe"
                                               name="piece_jointe"
                                               accept=".jpg,.jpeg,.png,.pdf"
                                               class="form-control @error('piece_jointe') is-invalid @enderror">

                                        @error('piece_jointe')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                    </div>

                                </div>


                                <button type="submit"
                                        class="btn btn-primary btn-lg w-100 mt-4">

                                    <i class="bi bi-send me-1"></i>
                                    Envoyer ma réclamation

                                </button>

                            </form>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    const root = document.getElementById('complaint-doctor');
    if (!root) return;
    const form = root.closest('form');
    const button = document.getElementById('complaint-doctor-run');
    const spinner = document.getElementById('complaint-doctor-spinner');
    const resultBox = document.getElementById('complaint-doctor-result');
    const errorBox = document.getElementById('complaint-doctor-error');
    const categories = @json(collect(\App\Models\Reclamation::CATEGORIES)->mapWithKeys(fn ($item, $key) => [$key => __($item[0])]));
    const priorities = @json(collect(\App\Models\Reclamation::PRIORITES)->mapWithKeys(fn ($item, $key) => [$key => __($item[0])]));
    let suggestions = null;
    const text = (id, value) => { document.getElementById(id).textContent = value || ''; };
    button.addEventListener('click', async () => {
        errorBox.classList.add('d-none');
        resultBox.classList.add('d-none');
        button.disabled = true;
        spinner.classList.remove('d-none');
        try {
            const response = await fetch(root.dataset.url, {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value},
                body: JSON.stringify({sujet: form.querySelector('[name=sujet]').value, description: form.querySelector('[name=description]').value, categorie: form.querySelector('[name=categorie]:checked')?.value || null, priorite: form.querySelector('[name=priorite]').value})
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || @json(__('complaints.doctor.error')));
            suggestions = payload.suggestions;
            text('complaint-doctor-original', form.querySelector('[name=sujet]').value + '\n' + form.querySelector('[name=description]').value);
            text('complaint-doctor-summary', suggestions.summary);
            text('complaint-doctor-explanation', suggestions.explanation);
            text('complaint-doctor-subject', suggestions.improved_subject);
            text('complaint-doctor-description', suggestions.improved_description);
            text('complaint-doctor-category', categories[suggestions.category] || suggestions.category);
            text('complaint-doctor-priority', priorities[suggestions.priority] || suggestions.priority);
            const list = document.getElementById('complaint-doctor-missing'); list.replaceChildren();
            (suggestions.missing_information || []).forEach(item => { const li = document.createElement('li'); li.textContent = item; list.appendChild(li); });
            resultBox.classList.remove('d-none');
        } catch (error) { errorBox.textContent = error.message; errorBox.classList.remove('d-none'); }
        finally { spinner.classList.add('d-none'); button.disabled = false; }
    });
    root.querySelectorAll('[data-accept]').forEach(control => control.addEventListener('click', () => {
        if (!suggestions) return;
        const field = control.dataset.accept;
        if (field === 'improved_subject') form.querySelector('[name=sujet]').value = suggestions[field];
        if (field === 'improved_description') form.querySelector('[name=description]').value = suggestions[field];
        if (field === 'priority') form.querySelector('[name=priorite]').value = suggestions[field];
        if (field === 'category') { const radio = form.querySelector(`[name=categorie][value="${CSS.escape(suggestions[field])}"]`); if (radio) radio.checked = true; }
    }));
    root.querySelectorAll('[data-refuse]').forEach(control => control.addEventListener('click', () => {
        control.textContent = @json(__('complaints.doctor.refused'));
        control.disabled = true;
    }));
})();
</script>
@endpush
