<?php
/**
 * FICHIER : deconnexion.php
 * RÔLE    : Détruit la session active et redirige vers l'accueil.
 *           Accessible depuis n'importe quel tableau de bord.
 */

require_once __DIR__ . '/includes/auth.php';

fermerSession();

header('Location: /index.html');
exit;
