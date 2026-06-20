/**
 * FICHIER : assets/js/main.js
 * RÔLE    : Toute la logique dynamique de la page d'accueil.
 *           1. Appelle api/formations.php (fetch AJAX)
 *           2. Construit les cartes de formation à partir du
 *              <template id="modele-carte-formation"> défini
 *              dans index.html
 *           3. Affiche le domaine + le nom du formateur sur
 *              chaque carte (demande explicite du client)
 *           4. Gère le clic -> redirection vers la page
 *              d'inscription de la formation choisie
 *           5. Construit dynamiquement les boutons de filtre
 *              par domaine
 *
 * ARCHITECTURE : Ce fichier ne contient AUCUN HTML codé en dur
 *           pour le contenu (titres, noms...) — uniquement la
 *           structure du template clonée et remplie avec les
 *           données reçues de l'API. Cela respecte la séparation
 *           stricte structure / logique / données.
 */

document.addEventListener('DOMContentLoaded', function () {
    chargerFormations();
});

// Stocke la liste complète pour pouvoir filtrer sans re-fetch
let toutesLesFormations = [];

/**
 * Récupère les formations depuis l'API et lance l'affichage
 */
function chargerFormations() {
    fetch('api/formations.php')
        .then(function (reponse) {
            if (!reponse.ok) {
                throw new Error('Erreur réseau : ' + reponse.status);
            }
            return reponse.json();
        })
        .then(function (donnees) {
            if (!donnees.succes) {
                throw new Error(donnees.erreur || 'Erreur inconnue');
            }
            toutesLesFormations = donnees.formations;
            afficherFormations(toutesLesFormations);
            construireFiltresDomaines(toutesLesFormations);
            mettreAJourStatistiques(donnees.total);
        })
        .catch(function (erreur) {
            afficherErreurChargement(erreur);
        });
}

/**
 * Affiche une liste de formations dans la grille
 * @param {Array} formations
 */
function afficherFormations(formations) {
    const grille = document.getElementById('grille-formations');
    const modele = document.getElementById('modele-carte-formation');

    grille.innerHTML = ''; // on vide (y compris le message "chargement...")

    if (formations.length === 0) {
        grille.innerHTML = '<p class="chargement">Aucune formation disponible pour le moment.</p>';
        return;
    }

    formations.forEach(function (formation) {
        const carte = modele.content.cloneNode(true);

        // Image de couverture (ou dégradé par défaut si absente)
        const image = carte.querySelector('.carte-image');
        if (formation.image_couverture) {
            image.style.backgroundImage = "url('" + formation.image_couverture + "')";
            image.style.backgroundSize = 'cover';
            image.style.backgroundPosition = 'center';
        }

        // Domaine de la formation
        carte.querySelector('.carte-domaine').textContent = formation.domaine;

        // Titre
        carte.querySelector('.carte-titre').textContent = formation.titre;

        // Nom du formateur (demande explicite : afficher qui enseigne)
        carte.querySelector('.carte-formateur span').textContent = formation.nom_formateur;

        // Prix (ou "Inclus dans l'accès global" si NULL)
        const prixTexte = formation.prix
            ? parseFloat(formation.prix).toLocaleString('fr-FR') + ' FCFA'
            : 'Accès global';
        carte.querySelector('.carte-prix').textContent = prixTexte;

        // Clic sur la carte entière -> page d'inscription de CETTE formation
        const elementCarte = carte.querySelector('.carte-formation');
        elementCarte.addEventListener('click', function () {
            allerVersInscription(formation);
        });
        // Accessibilité clavier (Entrée valide aussi la sélection)
        elementCarte.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') allerVersInscription(formation);
        });

        grille.appendChild(carte);
    });
}

/**
 * Redirige l'étudiant vers la page d'inscription de la formation choisie.
 * Utilise le lien d'inscription spécifique si disponible (token unique),
 * sinon l'identifiant simple de la formation.
 * @param {Object} formation
 */
function allerVersInscription(formation) {
    if (formation.lien_inscription_token) {
        window.location.href = 'inscription.php?lien=' + encodeURIComponent(formation.lien_inscription_token);
    } else {
        window.location.href = 'inscription.php?id=' + encodeURIComponent(formation.id);
    }
}

/**
 * Construit dynamiquement les boutons de filtre par domaine
 * à partir des domaines réellement présents dans les formations.
 * @param {Array} formations
 */
function construireFiltresDomaines(formations) {
    const conteneur = document.getElementById('filtre-domaines');

    // Liste unique des domaines, sans doublons
    const domaines = [...new Set(formations.map(function (f) { return f.domaine; }))];

    domaines.forEach(function (domaine) {
        const bouton = document.createElement('button');
        bouton.className = 'filtre-btn';
        bouton.dataset.domaine = domaine;
        bouton.textContent = domaine;
        bouton.addEventListener('click', function () {
            filtrerParDomaine(domaine, bouton);
        });
        conteneur.appendChild(bouton);
    });

    // Le bouton "Tous les domaines" existe déjà en HTML, on l'active
    const boutonTous = conteneur.querySelector('[data-domaine=""]');
    boutonTous.addEventListener('click', function () {
        filtrerParDomaine('', boutonTous);
    });
}

/**
 * Filtre l'affichage des cartes selon le domaine choisi
 * @param {string} domaine - vide = tous les domaines
 * @param {HTMLElement} boutonActif
 */
function filtrerParDomaine(domaine, boutonActif) {
    // Met à jour le style "actif" sur les boutons de filtre
    document.querySelectorAll('.filtre-btn').forEach(function (btn) {
        btn.classList.remove('actif');
    });
    boutonActif.classList.add('actif');

    const resultats = domaine
        ? toutesLesFormations.filter(function (f) { return f.domaine === domaine; })
        : toutesLesFormations;

    afficherFormations(resultats);
}

/**
 * Met à jour les compteurs de la barre de statistiques
 * @param {number} totalFormations
 */
function mettreAJourStatistiques(totalFormations) {
    const elementMaisons = document.getElementById('stat-maisons');
    if (elementMaisons) {
        elementMaisons.textContent = totalFormations + '+';
    }
    // Le compteur de certificats sera branché plus tard sur une vraie API dédiée
}

/**
 * Affiche un message d'erreur clair si l'API échoue
 * @param {Error} erreur
 */
function afficherErreurChargement(erreur) {
    console.error('Erreur de chargement des formations :', erreur);
    const grille = document.getElementById('grille-formations');
    grille.innerHTML = '<p class="chargement">Impossible de charger les formations. Veuillez réessayer plus tard.</p>';
}
