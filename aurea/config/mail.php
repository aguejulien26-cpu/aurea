<?php
/**
 * FICHIER : config/mail.php
 * RÔLE    : Configuration CENTRALE des paramètres d'envoi d'emails
 *           (serveur SMTP). Comme pour les agrégateurs de paiement,
 *           ces réglages devraient à terme être stockés en base et
 *           modifiables depuis le tableau de bord Super Admin
 *           (table `parametres_smtp` — voir TODO en bas de fichier).
 *           Pour l'instant, configuration en dur ici, à adapter
 *           selon le fournisseur SMTP choisi (Gmail, SendGrid,
 *           Mailgun, ou le SMTP de l'hébergeur).
 *
 * USAGE   : require_once __DIR__ . '/../config/mail.php';
 *           puis utiliser les constantes SMTP_* dans includes/mailer.php
 */

define('SMTP_HOTE', 'smtp.exemple-hebergeur.com');
define('SMTP_PORT', 587);                          // 587 = TLS, 465 = SSL
define('SMTP_SECURISATION', 'tls');                 // 'tls' ou 'ssl'
define('SMTP_UTILISATEUR', 'noreply@aurea.local');
define('SMTP_MOT_DE_PASSE', 'CHANGER_MOI');

define('EMAIL_EXPEDITEUR_ADRESSE', 'noreply@aurea.local');
define('EMAIL_EXPEDITEUR_NOM', 'Aurea');

// TODO (Phase 5 — paramètres dynamiques) :
// Créer une table `parametres_smtp` (hote, port, utilisateur, mot_de_passe
// chiffré, expediteur_nom, expediteur_email) gérée depuis un écran
// "Paramètres > Email" du tableau de bord Super Admin, exactement sur
// le même principe que les agrégateurs de paiement (FedaPay/Kkiapay/Stripe).
// Cela évitera de toucher au code pour changer de fournisseur SMTP.
