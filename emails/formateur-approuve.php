<?php
/**
 * FICHIER : emails/formateur-approuve.php
 * RÔLE    : Génère et envoie l'email de bienvenue à un formateur
 *           dont la candidature vient d'être approuvée. Contient
 *           ses identifiants temporaires de connexion.
 *           Appelé depuis api/traiter-candidature.php.
 *
 * SÉCURITÉ : Le mot de passe temporaire n'est envoyé QU'UNE FOIS
 *           par email. Le formateur doit le changer à sa première
 *           connexion (TODO à implémenter dans le tableau de bord
 *           formateur : forcer le changement de mot de passe si
 *           c'est la première connexion).
 */

require_once __DIR__ . '/template-base.php';
require_once __DIR__ . '/../includes/mailer.php';

/**
 * Envoie l'email "candidature approuvée" à un nouveau formateur.
 *
 * @param string $email                Email du formateur
 * @param string $nomComplet            Nom complet du formateur
 * @param string $motDePasseTemporaire  Mot de passe généré, en clair (une seule fois)
 * @param string $nomMaison             Nom de sa Maison de Formation créée
 * @return bool
 */
function envoyerEmailFormateurApprouve(string $email, string $nomComplet, string $motDePasseTemporaire, string $nomMaison): bool {

    $lienConnexion = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'aurea.local') . '/connexion.php';

    $contenu = '
        <p>Bonjour <strong style="color:#EDE8DA;">' . htmlspecialchars($nomComplet) . '</strong>,</p>
        <p>Excellente nouvelle : votre candidature pour devenir formateur sur Aurea a été <strong style="color:#9FCB6B;">approuvée</strong> !</p>
        <p>Votre Maison de Formation <strong style="color:#C9A24A;">« ' . htmlspecialchars($nomMaison) . ' »</strong> a été créée et vous attend.</p>

        <table width="100%" cellpadding="0" cellspacing="0" style="background:#221F16; border:1px solid #3A3528; border-radius:8px; margin:20px 0;">
            <tr>
                <td style="padding:16px 20px;">
                    <p style="margin:0 0 8px; color:#7A7460; font-size:12px;">Vos identifiants de connexion</p>
                    <p style="margin:0; color:#EDE8DA; font-size:14px;">Email : ' . htmlspecialchars($email) . '</p>
                    <p style="margin:4px 0 0; color:#EDE8DA; font-size:14px;">Mot de passe temporaire : <strong style="color:#C9A24A;">' . htmlspecialchars($motDePasseTemporaire) . '</strong></p>
                </td>
            </tr>
        </table>

        <p style="font-size:12px; color:#7A7460;">
            ⚠ Pour votre sécurité, merci de changer ce mot de passe dès votre première connexion.
        </p>';

    $html = genererEmailBase(
        'Bienvenue chez Aurea, votre candidature est approuvée',
        $contenu,
        'Me connecter à mon espace',
        $lienConnexion
    );

    return envoyerEmail($email, $nomComplet, 'Votre compte formateur Aurea est prêt', $html);
}
