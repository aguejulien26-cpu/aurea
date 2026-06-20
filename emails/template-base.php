<?php
/**
 * FICHIER : emails/template-base.php
 * RÔLE    : Habillage HTML COMMUN à tous les emails du site
 *           (en-tête avec logo, pied de page, couleurs de marque).
 *           Chaque email spécifique (emails/formateur-approuve.php,
 *           etc.) appelle la fonction genererEmailBase() en lui
 *           passant juste son contenu central.
 *
 * ARCHITECTURE : Centraliser l'habillage ici évite de dupliquer
 *           le même HTML (header, footer) dans chaque template
 *           d'email, et permet de changer le design de TOUS les
 *           emails en un seul endroit.
 *
 * NOTE : Les emails HTML ne supportent pas les CSS externes ni les
 *           variables CSS modernes -> tout est en style inline,
 *           contrainte technique propre à l'emailing.
 */

/**
 * Génère le HTML complet d'un email à partir d'un contenu central.
 *
 * @param string $titre        Titre affiché en haut de l'email
 * @param string $contenuHtml  Corps spécifique au message (déjà en HTML)
 * @param string|null $boutonTexte  Texte du bouton d'action (optionnel)
 * @param string|null $boutonLien   URL du bouton d'action (optionnel)
 * @return string HTML complet prêt à être envoyé
 */
function genererEmailBase(string $titre, string $contenuHtml, ?string $boutonTexte = null, ?string $boutonLien = null): string {

    $blocBouton = '';
    if ($boutonTexte && $boutonLien) {
        $blocBouton = '
            <tr>
                <td style="padding: 24px 40px 8px;" align="center">
                    <a href="' . htmlspecialchars($boutonLien) . '"
                       style="background:#C9A24A; color:#15140F; text-decoration:none;
                              font-weight:600; font-size:14px; padding:12px 28px;
                              border-radius:8px; display:inline-block; font-family:Arial, sans-serif;">
                        ' . htmlspecialchars($boutonTexte) . '
                    </a>
                </td>
            </tr>';
    }

    return '
    <!DOCTYPE html>
    <html lang="fr">
    <head><meta charset="UTF-8"></head>
    <body style="margin:0; padding:0; background:#0E0D09; font-family:Arial, sans-serif;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background:#0E0D09; padding:32px 0;">
            <tr>
                <td align="center">
                    <table width="520" cellpadding="0" cellspacing="0"
                           style="background:#15140F; border:1px solid #3A3528; border-radius:12px; overflow:hidden;">

                        <!-- En-tête -->
                        <tr>
                            <td style="padding:28px 40px; border-bottom:1px solid #3A3528;" align="center">
                                <span style="color:#EDE8DA; font-size:20px; font-weight:700; letter-spacing:1px;">AUREA</span>
                            </td>
                        </tr>

                        <!-- Titre -->
                        <tr>
                            <td style="padding:32px 40px 8px;">
                                <h1 style="color:#EDE8DA; font-size:19px; margin:0 0 16px; font-weight:600;">' . htmlspecialchars($titre) . '</h1>
                            </td>
                        </tr>

                        <!-- Contenu spécifique -->
                        <tr>
                            <td style="padding:0 40px; color:#B5AD96; font-size:14px; line-height:1.7;">
                                ' . $contenuHtml . '
                            </td>
                        </tr>

                        ' . $blocBouton . '

                        <!-- Pied de page -->
                        <tr>
                            <td style="padding:32px 40px 24px; border-top:1px solid #3A3528; margin-top:24px;">
                                <p style="color:#7A7460; font-size:11px; margin:0;">
                                    &copy; ' . date('Y') . ' Aurea — Tous droits réservés.<br>
                                    Vous recevez cet email suite à une action sur la plateforme Aurea.
                                </p>
                            </td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}
