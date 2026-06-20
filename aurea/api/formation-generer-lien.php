<?php
/**
 * FICHIER : api/formation-generer-lien.php
 * RÔLE    : Génère un lien d'inscription UNIQUE pour une formation
 *           précise (cahier des charges 3.2 / section 5 : "Génération
 *           de Liens Spécifiques par Formation"). Ce lien permet à
 *           l'étudiant de s'inscrire directement à CETTE formation,
 *           en contournant l'accès global à la Maison de Formation.
 *
 *           Appelé en AJAX depuis formateur/formations.php
 *           (bouton "Générer un lien").
 *
 * SÉCURITÉ : Vérifie la propriété de la formation avant de générer
 *           quoi que ce soit. Le token est unique en base
 *           (contrainte UNIQUE sur lien_inscription_token).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

exigerRole('formateur');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['succes' => false, 'erreur' => 'Méthode non autorisée']);
    exit;
}

$donnees = json_decode(file_get_contents('php://input'), true) ?? [];
$formationId = isset($donnees['formation_id']) ? (int) $donnees['formation_id'] : 0;
$formateurId = idUtilisateur();

if (!$formationId) {
    http_response_code(400);
    echo json_encode(['succes' => false, 'erreur' => 'Formation invalide']);
    exit;
}

try {
    // Vérifie que la formation appartient bien au formateur connecté
    $stmtVerif = $pdo->prepare("
        SELECT f.id, f.lien_inscription_token
        FROM formations f
        INNER JOIN maisons_formation mf ON f.maison_id = mf.id
        WHERE f.id = :formation_id AND mf.formateur_id = :formateur_id
        LIMIT 1
    ");
    $stmtVerif->execute([':formation_id' => $formationId, ':formateur_id' => $formateurId]);
    $formation = $stmtVerif->fetch();

    if (!$formation) {
        http_response_code(403);
        echo json_encode(['succes' => false, 'erreur' => 'Accès refusé']);
        exit;
    }

    if ($formation['lien_inscription_token']) {
        // Un lien existe déjà, on le renvoie tel quel plutôt que d'en
        // générer un nouveau (évite de casser un lien déjà partagé)
        $token = $formation['lien_inscription_token'];
    } else {
        $token = bin2hex(random_bytes(20));
        $stmtMaj = $pdo->prepare("UPDATE formations SET lien_inscription_token = :token WHERE id = :id");
        $stmtMaj->execute([':token' => $token, ':id' => $formationId]);
    }

    $protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $lienComplet = $protocole . '://' . $_SERVER['HTTP_HOST'] . '/inscription.php?lien=' . $token;

    echo json_encode(['succes' => true, 'lien' => $lienComplet]);

} catch (PDOException $e) {
    error_log('Erreur génération lien formation : ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['succes' => false, 'erreur' => 'Erreur serveur']);
}
