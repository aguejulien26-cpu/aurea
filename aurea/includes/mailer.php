<?php
/**
 * FICHIER : includes/mailer.php
 * RÔLE    : Point UNIQUE d'envoi d'emails pour tout le projet.
 *           Encapsule PHPMailer pour que le reste du code n'ait
 *           jamais à manipuler directement la librairie — on
 *           appelle simplement envoyerEmail(...).
 *
 * USAGE   : require_once __DIR__ . '/../includes/mailer.php';
 *           envoyerEmail('etudiant@mail.com', 'Bienvenue', $corpsHtml);
 *
 * ARCHITECTURE : Les TEMPLATES d'email (le contenu HTML) ne sont
 *           PAS écrits ici — ils vivent dans le dossier /emails/
 *           (un fichier par type d'email). Ce fichier ne s'occupe
 *           que du TRANSPORT (connexion SMTP, envoi).
 */

require_once __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Envoie un email HTML via SMTP.
 *
 * @param string $destinataireEmail
 * @param string $destinataireNom
 * @param string $sujet
 * @param string $corpsHtml          Contenu HTML déjà généré (par un template /emails/*.php)
 * @return bool true si l'envoi a réussi, false sinon
 */
function envoyerEmail(string $destinataireEmail, string $destinataireNom, string $sujet, string $corpsHtml): bool {
    $mail = new PHPMailer(true);

    try {
        // --- Configuration du transport SMTP ---
        $mail->isSMTP();
        $mail->Host       = SMTP_HOTE;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_UTILISATEUR;
        $mail->Password   = SMTP_MOT_DE_PASSE;
        $mail->SMTPSecure = SMTP_SECURISATION === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // --- Expéditeur et destinataire ---
        $mail->setFrom(EMAIL_EXPEDITEUR_ADRESSE, EMAIL_EXPEDITEUR_NOM);
        $mail->addAddress($destinataireEmail, $destinataireNom);

        // --- Contenu ---
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $corpsHtml;
        $mail->AltBody  = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $corpsHtml));

        $mail->send();
        return true;

    } catch (PHPMailerException $e) {
        // On journalise l'erreur mais on ne bloque JAMAIS le reste du
        // processus métier (ex : un email qui échoue ne doit pas empêcher
        // la création du compte formateur).
        error_log('Erreur envoi email à ' . $destinataireEmail . ' : ' . $mail->ErrorInfo);
        return false;
    }
}
