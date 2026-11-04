{{-- Fenêtre de confirmation partagée, ouverte par <x-delete-button> --}}
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="#">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteTitle">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>{{ __('Confirmer la suppression') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button>
                </div>
                <div class="modal-body">
                    {{ __('Voulez-vous vraiment supprimer') }} <strong data-confirm-label>{{ __('cet élément') }}</strong> ?
                    <br><small class="text-muted">{{ __('Cette action est irréversible.') }}</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Annuler') }}</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>{{ __('Supprimer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
