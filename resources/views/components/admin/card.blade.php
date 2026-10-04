{{--
    Carte AdminLTE réutilisable.
    Exemple :
    <x-admin.card title="Liste des catégories" icon="tags">
        <x-slot:tools> ...boutons... </x-slot:tools>
        ...contenu...
        <x-slot:footer> ...pagination... </x-slot:footer>
    </x-admin.card>
--}}
@props(['title' => null, 'icon' => null, 'outline' => 'primary', 'bodyClass' => ''])

<div {{ $attributes->merge(['class' => "card card-{$outline} card-outline mb-4"]) }}>
    @if ($title || isset($tools))
        <div class="card-header">
            <h3 class="card-title">
                @if ($icon)<i class="bi bi-{{ $icon }} me-2"></i>@endif{{ $title }}
            </h3>
            @isset($tools)
                <div class="card-tools">{{ $tools }}</div>
            @endisset
        </div>
    @endif

    <div class="card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="card-footer clearfix">{{ $footer }}</div>
    @endisset
</div>
