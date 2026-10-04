{{-- Fenêtre de confirmation partagée, ouverte par <x-delete-button> --}}
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="#">
                @csrf
                @method('DELETE')
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteTitle">
                        <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    Voulez-vous vraiment supprimer <strong data-confirm-label>cet élément</strong> ?
                    <br><small class="text-muted">Cette action est irréversible.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>
