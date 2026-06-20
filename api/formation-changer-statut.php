<?php
/**
 * FICHIER : api/formation-changer-statut.php
 * RÔLE    : Change le statut d'une formation (brouillon/publiee/archivee).
 *           C'est le clic "Publier" sur formateur/formations.php qui
 *           fait apparaître la formation sur la page d'accueil
 *           publique (la grille de la page d'accueil ne lit QUE les
 *           formations avec statut = 'publiee', voir api/formations.php).
 *
 * SÉCURITÉ : Vérifie systématiquement que la formation appartient
 *           au formateur connecté avant toute modification.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

exigerRole('formateur');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../formateur/formations.php');
    exit;
}

$formationId = isset($_POST['formation_id']) ? (int) $_POST['formation_id'] : 0;
$nouveauStatut = $_POST['nouveau_statut'] ?? '';

$statutsAutorises = ['brouillon', 'publiee', 'archivee'];
if (!$formationId || !in_array($nouveauStatut, $statutsAutorises, true)) {
    header('Location: ../formateur/formations.php');
    exit;
}

$formateurId = idUtilisateur();

try {
    // Vérifie la propriété via une jointure : la formation doit appartenir
    // à une maison_formation dont le formateur_id = utilisateur connecté.
    $stmtUpdate = $pdo->prepare("
        UPDATE formations f
        INNER JOIN maisons_formation mf ON f.maison_id = mf.id
        SET f.statut = :nouveau_statut
        WHERE f.id = :formation_id AND mf.formateur_id = :formateur_id
    ");
    $stmtUpdate->execute([
        ':nouveau_statut' => $nouveauStatut,
        ':formation_id'   => $formationId,
        ':formateur_id'   => $formateurId,
    ]);

    if ($stmtUpdate->rowCount() === 0) {
        // Aucune ligne affectée = soit la formation n'existe pas,
        // soit elle n'appartient pas à ce formateur
        http_response_code(403);
        die('Accès refusé.');
    }

    header('Location: ../formateur/formations.php?message=statut');
    exit;

} catch (PDOException $e) {
    error_log('Erreur changement statut formation : ' . $e->getMessage());
    header('Location: ../formateur/formations.php?erreur=serveur');
    exit;
}
