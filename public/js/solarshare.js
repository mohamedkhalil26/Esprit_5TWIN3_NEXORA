/*
 * SolarShare — scripts communs aux deux templates
 */
document.addEventListener('DOMContentLoaded', () => {
    // Fenêtre de confirmation de suppression (partagée par tous les modules)
    const modal = document.getElementById('confirmDeleteModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', (event) => {
            const bouton = event.relatedTarget;
            modal.querySelector('form').action = bouton.dataset.action;
            modal.querySelector('[data-confirm-label]').textContent = bouton.dataset.label || 'cet élément';
        });
    }

    // Disparition automatique des messages flash
    document.querySelectorAll('.alert[data-autohide]').forEach((alerte) => {
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(alerte).close(), 5000);
    });

    // Barre de navigation "Creative" : réduction au défilement
    const nav = document.getElementById('mainNav');
    if (nav) {
        const reduire = () => {
            if (window.scrollY === 0 && !document.body.classList.contains('nav-solid')) {
                nav.classList.remove('navbar-shrink');
            } else {
                nav.classList.add('navbar-shrink');
            }
        };
        reduire();
        document.addEventListener('scroll', reduire);
    }

    // Aperçu de l'image choisie dans un formulaire (input[data-preview="#id"])
    document.querySelectorAll('input[type=file][data-preview]').forEach((input) => {
        input.addEventListener('change', () => {
            const cible = document.querySelector(input.dataset.preview);
            const fichier = input.files[0];
            if (cible && fichier) {
                cible.src = URL.createObjectURL(fichier);
                cible.classList.remove('d-none');
            }
        });
    });
});
