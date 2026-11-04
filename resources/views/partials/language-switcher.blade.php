@php($switcherId = $id ?? 'locale-select')
<form method="POST" action="{{ route('locale.update') }}" class="language-switcher d-flex align-items-center">
    @csrf
    <label class="visually-hidden" for="{{ $switcherId }}">{{ __('Langue') }}</label>
    <select class="form-select form-select-sm" id="{{ $switcherId }}" name="locale" aria-label="{{ __('Langue') }}" onchange="this.form.submit()">
        <option value="fr" @selected(app()->getLocale() === 'fr')>FR</option>
        <option value="ar" @selected(app()->getLocale() === 'ar')>AR</option>
        <option value="en" @selected(app()->getLocale() === 'en')>EN</option>
    </select>
</form>
