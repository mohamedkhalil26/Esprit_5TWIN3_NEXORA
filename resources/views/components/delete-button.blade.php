{{--
    Bouton de suppression avec confirmation.
    Exemple : <x-delete-button :action="route('admin.categories.destroy', $categorie)" :label="$categorie->nom" />
--}}
@props(['action', 'label' => 'cet élément', 'text' => null, 'size' => 'sm'])

<button type="button"
        {{ $attributes->merge(['class' => "btn btn-{$size} btn-outline-danger"]) }}
        data-bs-toggle="modal"
        data-bs-target="#confirmDeleteModal"
        data-action="{{ $action }}"
        data-label="{{ $label }}"
        title="Supprimer">
    <i class="bi bi-trash"></i>@if ($text) {{ $text }}@endif
</button>
