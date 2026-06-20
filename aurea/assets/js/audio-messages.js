/**
 * FICHIER : assets/js/audio-messages.js
 * RÔLE    : Joue les messages audio pré-enregistrés exigés par le
 *           cahier des charges (3.3 / 6.2) :
 *             - 'post-inscription' : juste après l'inscription
 *             - 'post-paiement'    : juste après le paiement réussi
 *
 *           Ces fichiers audio sont fournis par le promoteur et
 *           doivent être placés dans assets/audio/<nom>.mp3
 *
 * ARCHITECTURE : Centralisé ici plutôt que dupliqué sur chaque page,
 *           pour ne configurer les chemins de fichiers audio qu'à
 *           un seul endroit.
 *
 * NOTE NAVIGATEUR : Les navigateurs modernes bloquent l'autoplay
 *           audio AVEC son tant qu'il n'y a pas eu d'interaction
 *           utilisateur sur la page. Comme l'étudiant vient de
 *           cliquer sur "S'inscrire" juste avant d'arriver ici,
 *           cette interaction récente suffit généralement à
 *           autoriser la lecture. Un bouton de secours est prévu
 *           si jamais le navigateur bloque malgré tout.
 */

const CHEMINS_AUDIO = {
    'post-inscription': 'assets/audio/post-inscription.mp3',
    'post-paiement': 'assets/audio/post-paiement.mp3',
};

/**
 * Joue le message audio correspondant à la clé donnée.
 * @param {string} cle - 'post-inscription' ou 'post-paiement'
 */
function jouerMessageAudio(cle) {
    const chemin = CHEMINS_AUDIO[cle];
    if (!chemin) {
        console.warn('Message audio inconnu :', cle);
        return;
    }

    const audio = new Audio(chemin);

    audio.play().catch(function () {
        // Le navigateur a bloqué la lecture automatique : on affiche
        // un bouton discret pour que l'utilisateur déclenche le son
        // lui-même en un clic.
        afficherBoutonAudioSecours(audio);
    });
}

/**
 * Affiche un petit bouton flottant permettant de lancer le message
 * audio manuellement si l'autoplay a été bloqué par le navigateur.
 * @param {HTMLAudioElement} audio
 */
function afficherBoutonAudioSecours(audio) {
    const bouton = document.createElement('button');
    bouton.textContent = '🔊 Écouter le message';
    bouton.setAttribute('aria-label', 'Écouter le message audio');
    bouton.style.cssText = `
        position: fixed; bottom: 24px; right: 24px;
        background: #C9A24A; color: #15140F; border: none;
        padding: 12px 20px; border-radius: 8px; font-size: 13px;
        font-weight: 600; cursor: pointer; z-index: 999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    `;
    bouton.addEventListener('click', function () {
        audio.play();
        bouton.remove();
    });
    document.body.appendChild(bouton);
}
