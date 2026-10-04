@props(['icon' => 'inbox', 'message' => 'Aucun élément pour le moment.'])

<div {{ $attributes->merge(['class' => 'text-center text-muted py-5']) }}>
    <i class="bi bi-{{ $icon }} display-4 d-block mb-3 opacity-50"></i>
    <p class="mb-3">{{ $message }}</p>
    {{ $slot }}
</div>
