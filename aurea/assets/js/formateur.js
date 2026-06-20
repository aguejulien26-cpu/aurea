/**
 * FICHIER : assets/js/formateur.js
 * RÔLE    : Logique JS de l'espace formateur, principalement la
 *           page formateur/formations.php :
 *             - Génération AJAX du lien d'inscription spécifique
 *               par formation (api/formation-generer-lien.php)
 *             - Copie dans le presse-papiers des liens déjà générés
 *
 * ARCHITECTURE : Aucune génération de token ici — la sécurité
 *           (vérification de propriété, création du token) est
 *           entièrement faite côté serveur.
 */

document.addEventListener('DOMContentLoaded', function () {
    // --- Boutons "Générer un lien" sur chaque carte de formation ---
    document.querySelectorAll('.bouton-generer-lien-formation').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            genererLienFormation(bouton);
        });
    });

    // --- Boutons "Copier" sur les liens déjà existants ---
    document.querySelectorAll('.btn-copier-lien-formation').forEach(function (bouton) {
        bouton.addEventListener('click', function () {
            const champLien = bouton.previousElementSibling;
            copierTexte(champLien, bouton);
        });
    });
});

/**
 * Appelle l'API pour générer le lien d'inscription spécifique
 * d'une formation, puis recharge la page pour afficher le résultat.
 * @param {HTMLElement} bouton
 */
function genererLienFormation(bouton) {
    const formationId = bouton.dataset.formationId;
    const texteOriginal = bouton.innerHTML;

    bouton.disabled = true;
    bouton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Génération...';

    fetch('../api/formation-generer-lien.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ formation_id: formationId }),
    })
        .then(function (reponse) { return reponse.json(); })
        .then(function (donnees) {
            if (!donnees.succes) {
                throw new Error(donnees.erreur || 'Erreur inconnue');
            }
            // Solution simple pour cette phase : on recharge la page,
            // le lien généré apparaîtra alors dans la carte (rendu PHP).
            window.location.reload();
        })
        .catch(function (erreur) {
            alert('Erreur lors de la génération du lien : ' + erreur.message);
            bouton.disabled = false;
            bouton.innerHTML = texteOriginal;
        });
}

/**
 * Copie le contenu d'un champ input dans le presse-papiers
 * @param {HTMLInputElement} champ
 * @param {HTMLElement} boutonDeclencheur
 */
function copierTexte(champ, boutonDeclencheur) {
    champ.select();
    navigator.clipboard.writeText(champ.value).then(function () {
        const iconeOriginale = boutonDeclencheur.innerHTML;
        boutonDeclencheur.innerHTML = '<i class="fa-solid fa-check"></i>';
        setTimeout(function () {
            boutonDeclencheur.innerHTML = iconeOriginale;
        }, 1200);
    });
}
