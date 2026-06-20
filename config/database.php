<?php
/**
 * FICHIER : config/database.php
 * RÔLE    : Point UNIQUE de connexion à la base de données.
 *           Tous les fichiers PHP du projet doivent inclure ce
 *           fichier au lieu de recréer leur propre connexion.
 *           Cela permet de changer d'hébergeur en modifiant
 *           UNE seule fois les identifiants ci-dessous.
 *
 * USAGE   : require_once __DIR__ . '/../config/database.php';
 *           puis utiliser la variable $pdo
 *
 * SÉCURITÉ: Ce fichier ne doit JAMAIS être accessible publiquement
 *           depuis le navigateur. Le dossier /config/ doit être
 *           protégé via .htaccess (deny from all) en production.
 */

// --- Identifiants de connexion (à adapter selon l'hébergement) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'aurea_platform');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // remonte les erreurs SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // résultats en tableaux associatifs
    PDO::ATTR_EMULATE_PREPARES   => false,                   // requêtes préparées réelles (anti-injection SQL)
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // En production : ne jamais afficher le message d'erreur brut à l'utilisateur
    error_log('Erreur de connexion BDD : ' . $e->getMessage());
    http_response_code(500);
    die('Erreur serveur. Veuillez réessayer plus tard.');
}
