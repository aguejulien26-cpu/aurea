<?php
/**
 * FICHIER : emails/candidature-refusee.php
 * RÔLE    : Génère et envoie un email courtois informant le
 *           candidat que son dossier n'a pas été retenu.
 *           Appelé depuis api/traiter-candidature.php.
 */

require_once __DIR__ . '/template-base.php';
require_once __DIR__ . '/../includes/mailer.php';

/**
 * Envoie l'email "candidature refusée".
 *
 * @param string $email
 * @param string $nomComplet
 * @return bool
 */
function envoyerEmailCandidatureRefusee(string $email, string $nomComplet): bool {

    $contenu = '
        <p>Bonjour <strong style="color:#EDE8DA;">' . htmlspecialchars($nomComplet) . '</strong>,</p>
        <p>Nous vous remercions pour l\'intérêt porté à Aurea et pour le temps consacré à votre candidature de formateur.</p>
        <p>Après examen attentif de votre dossier, nous ne sommes malheureusement pas en mesure d\'y donner une suite favorable pour le moment.</p>
        <p>Cette décision ne remet pas en cause vos compétences : elle peut tenir à de nombreux critères propres à notre plateforme à ce stade de son développement.</p>
        <p>Nous vous souhaitons beaucoup de succès dans vos projets.</p>
        <p style="margin-top:20px;">Cordialement,<br><strong style="color:#EDE8DA;">L\'équipe Aurea</strong></p>';

    $html = genererEmailBase(
        'Concernant votre candidature formateur',
        $contenu
    );

    return envoyerEmail($email, $nomComplet, 'Votre candidature formateur Aurea', $html);
}
