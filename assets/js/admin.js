/**
 * FICHIER : assets/js/admin.js
 * RÔLE    : Logique du tableau de bord Super Admin.
 *           Gère le clic sur "Générer le lien" : envoie une requête
 *           à api/generer-invitation.php, affiche le résultat,
 *           gère le bouton "Copier".
 *
 * ARCHITECTURE : Aucune génération de token ici — la sécurité
 *           (vérification du rôle, création du token aléatoire)
 *           est entièrement faite côté serveur dans
 *           api/generer-invitation.php. Ce fichier ne fait
 *           qu'orchestrer l'appel et l'affichage.
 */

document.addEventListener('DOMContentLoaded', function () {
    const boutonGenerer = document.getElementById('bouton-generer-lien');
    const boutonCopier = document.getElementById('bouton-copier-lien');

    if (boutonGenerer) {
        boutonGenerer.addEventListener('click', genererLienFormateur);
    }
    if (boutonCopier) {
        boutonCopier.addEventListener('click', copierLien);
    }
});

/**
 * Appelle l'API pour générer un nouveau lien de candidature formateur
 */
function genererLienFormateur() {
    const champEmail = document.getElementById('email_autorise');
    const champDuree = document.getElementById('duree_validite');
    const messageZone = document.getElementById('message-generation');
    const boutonGenerer = document.getElementById('bouton-generer-lien');

    const payload = {
        email_autorise: champEmail.value.trim(),
        duree_heures: parseInt(champDuree.value, 10),
    };

    // État de chargement
    boutonGenerer.disabled = true;
    boutonGenerer.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Génération...';
    messageZone.textContent = '';
    messageZone.className = 'message-generation';

    fetch('../api/generer-invitation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    })
        .then(function (reponse) { return reponse.json(); })
        .then(function (donnees) {
            if (!donnees.succes) {
                throw new Error(donnees.erreur || 'Erreur inconnue');
            }
            afficherLienGenere(donnees.lien, donnees.date_expiration);
        })
        .catch(function (erreur) {
            messageZone.textContent = 'Erreur : ' + erreur.message;
            messageZone.classList.add('message-erreur');
        })
        .finally(function () {
            boutonGenerer.disabled = false;
            boutonGenerer.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Générer le lien';
        });
}

/**
 * Affiche le lien généré dans le champ readonly prévu à cet effet
 * @param {string} lien
 * @param {string} dateExpiration
 */
function afficherLienGenere(lien, dateExpiration) {
    const zoneResultat = document.getElementById('resultat-lien');
    const champLien = document.getElementById('lien-genere');
    const messageZone = document.getElementById('message-generation');

    champLien.value = lien;
    zoneResultat.style.display = 'flex';

    messageZone.textContent = 'Lien généré avec succès. Il expire le ' +
        new Date(dateExpiration).toLocaleString('fr-FR') + '.';
    messageZone.classList.add('message-succes');

    // Recharge la page après 2 secondes pour mettre à jour le tableau
    // d'historique (rempli côté PHP). Approche simple pour cette phase.
    setTimeout(function () {
        window.location.reload();
    }, 4000);
}

/**
 * Copie le lien généré dans le presse-papiers
 */
function copierLien() {
    const champLien = document.getElementById('lien-genere');
    champLien.select();
    navigator.clipboard.writeText(champLien.value).then(function () {
        const boutonCopier = document.getElementById('bouton-copier-lien');
        const texteOriginal = boutonCopier.innerHTML;
        boutonCopier.innerHTML = '<i class="fa-solid fa-check"></i> Copié';
        setTimeout(function () {
            boutonCopier.innerHTML = texteOriginal;
        }, 1500);
    });
}
