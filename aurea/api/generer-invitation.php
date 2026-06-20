<?php
/**
 * FICHIER : api/generer-invitation.php
 * RÔLE    : Crée un nouveau lien (token) de candidature formateur.
 *           APPELÉE UNIQUEMENT par assets/js/admin.js depuis le
 *           tableau de bord Super Admin.
 *
 * SÉCURITÉ : Double protection :
 *           1. exigerRole('super_admin') -> bloque si pas connecté
 *              en tant que Super Admin (vérifie la SESSION, pas
 *              un simple champ caché du formulaire).
 *           2. Le token est généré côté serveur avec random_bytes
 *              (cryptographiquement sûr), jamais côté client.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

exigerRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['succes' => false, 'erreur' => 'Méthode non autorisée']);
    exit;
}

// Lecture des données envoyées en JSON par admin.js
$donnees = json_decode(file_get_contents('php://input'), true) ?? [];

$emailAutorise = isset($donnees['email_autorise']) ? trim($donnees['email_autorise']) : null;
$dureeHeures = isset($donnees['duree_heures']) ? (int) $donnees['duree_heures'] : 72;

// On limite la durée à des valeurs raisonnables (1h à 30 jours)
$dureeHeures = max(1, min($dureeHeures, 720));

if ($emailAutorise === '') {
    $emailAutorise = null;
}

if ($emailAutorise !== null && !filter_var($emailAutorise, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'erreur' => 'Adresse email invalide']);
    exit;
}

try {
    // Token cryptographiquement sûr, 64 caractères hexadécimaux
    $token = bin2hex(random_bytes(32));

    $dateExpiration = (new DateTime())->modify("+{$dureeHeures} hours")->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare("
        INSERT INTO trainer_invitations (token, created_by, email_autorise, statut, date_expiration)
        VALUES (:token, :created_by, :email_autorise, 'actif', :date_expiration)
    ");
    $stmt->execute([
        ':token'           => $token,
        ':created_by'      => idUtilisateur(),
        ':email_autorise'  => $emailAutorise,
        ':date_expiration' => $dateExpiration,
    ]);

    // Construction de l'URL complète à partager
    $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $hote = $_SERVER['HTTP_HOST'];
    $lienComplet = $protocole . '://' . $hote . '/candidature-formateur.php?token=' . $token;

    echo json_encode([
        'succes'          => true,
        'lien'            => $lienComplet,
        'date_expiration' => $dateExpiration,
    ]);

} catch (PDOException $e) {
    error_log('Erreur génération invitation : ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['succes' => false, 'erreur' => 'Impossible de générer le lien']);
}
