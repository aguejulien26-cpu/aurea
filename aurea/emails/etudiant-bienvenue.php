<?php
/**
 * FICHIER : emails/etudiant-bienvenue.php
 * RÔLE    : Génère et envoie l'email de validation après inscription
 *           d'un étudiant à une formation (cahier des charges 6.2,
 *           étape "Validation Email"). Contient le lien de
 *           confirmation à cliquer.
 *           Appelé depuis api/inscription-traitement.php.
 *
 * NOTE : Le message AUDIO pré-enregistré ("Veuillez vous diriger
 *           vers l'email que vous avez reçu") est un événement
 *           déclenché côté FRONT-END juste après la soumission du
 *           formulaire d'inscription (avant même l'arrivée de cet
 *           email) — il sera ajouté en JS sur inscription.php,
 *           pas dans ce fichier qui ne gère que l'email lui-même.
 */

require_once __DIR__ . '/template-base.php';
require_once __DIR__ . '/../includes/mailer.php';

/**
 * Envoie l'email de validation à un étudiant nouvellement inscrit.
 *
 * @param string $email
 * @param string $nomComplet
 * @param string $tokenValidation  Token unique pour valider l'email
 * @param string $titreFormation   Titre de la formation choisie
 * @return bool
 */
function envoyerEmailValidationEtudiant(string $email, string $nomComplet, string $tokenValidation, string $titreFormation): bool {

    $lienValidation = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'aurea.local')
        . '/valider-email.php?token=' . urlencode($tokenValidation);

    $contenu = '
        <p>Bonjour <strong style="color:#EDE8DA;">' . htmlspecialchars($nomComplet) . '</strong>,</p>
        <p>Merci de vous être inscrit(e) à la formation <strong style="color:#C9A24A;">« ' . htmlspecialchars($titreFormation) . ' »</strong> sur Aurea.</p>
        <p>Pour activer votre compte et accéder à votre espace, merci de confirmer votre adresse email en cliquant sur le bouton ci-dessous.</p>
        <p style="font-size:12px; color:#7A7460;">Ce lien est valable 24 heures.</p>';

    $html = genererEmailBase(
        'Confirmez votre adresse email',
        $contenu,
        'Confirmer mon email',
        $lienValidation
    );

    return envoyerEmail($email, $nomComplet, 'Confirmez votre inscription sur Aurea', $html);
}
