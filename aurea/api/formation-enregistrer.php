<?php
/**
 * FICHIER : api/formation-enregistrer.php
 * RÔLE    : Traite la soumission de formateur/formation.php.
 *           Détecte automatiquement le mode :
 *             - formation_id vide  -> INSERT (création, statut = brouillon)
 *             - formation_id rempli -> UPDATE (si la formation appartient
 *               bien au formateur connecté)
 *
 * SÉCURITÉ : Sur une modification, on revérifie la propriété de la
 *           formation (maison_id = la maison du formateur connecté)
 *           AVANT toute écriture, pour empêcher un formateur de
 *           modifier la formation d'un autre via une manipulation
 *           d'URL/formulaire (IDOR).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

exigerRole('formateur');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../formateur/formations.php');
    exit;
}

$formateurId = idUtilisateur();

$stmtMaison = $pdo->prepare("SELECT id FROM maisons_formation WHERE formateur_id = :id LIMIT 1");
$stmtMaison->execute([':id' => $formateurId]);
$maison = $stmtMaison->fetch();

if (!$maison) {
    die('Erreur : aucune Maison de Formation associée à ce compte.');
}

$formationId = isset($_POST['formation_id']) && ctype_digit($_POST['formation_id']) ? (int) $_POST['formation_id'] : null;
$titre = trim($_POST['titre'] ?? '');
$domaine = trim($_POST['domaine'] ?? '');
$description = trim($_POST['description'] ?? '');
$prixBrut = trim($_POST['prix'] ?? '');
$prix = ($prixBrut === '') ? null : (float) $prixBrut;

if ($titre === '' || $domaine === '' || $description === '') {
    header('Location: ../formateur/formation.php' . ($formationId ? '?id=' . $formationId : '') . '&erreur=champs');
    exit;
}

// --- Gestion optionnelle de l'upload d'image ---
$cheminImage = null; // null = on ne touche pas à l'image existante (cas modification)

if (isset($_FILES['image_couverture']) && $_FILES['image_couverture']['error'] === UPLOAD_ERR_OK) {
    $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];
    $tailleMaxOctets = 4 * 1024 * 1024; // 4 Mo

    $fichier = $_FILES['image_couverture'];
    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionsAutorisees, true)) {
        header('Location: ../formateur/formation.php' . ($formationId ? '?id=' . $formationId : '') . '&erreur=image_format');
        exit;
    }
    if ($fichier['size'] > $tailleMaxOctets) {
        header('Location: ../formateur/formation.php' . ($formationId ? '?id=' . $formationId : '') . '&erreur=image_taille');
        exit;
    }

    $nomFichierUnique = 'formation_' . bin2hex(random_bytes(12)) . '.' . $extension;
    $dossierUpload = __DIR__ . '/../uploads/formations/';
    $cheminDestination = $dossierUpload . $nomFichierUnique;

    if (move_uploaded_file($fichier['tmp_name'], $cheminDestination)) {
        $cheminImage = 'uploads/formations/' . $nomFichierUnique;
    }
}

try {
    if ($formationId) {
        // ===== MODE MODIFICATION =====
        // Vérifie d'abord que cette formation appartient bien à ce formateur
        $stmtVerif = $pdo->prepare("SELECT id, image_couverture FROM formations WHERE id = :id AND maison_id = :maison_id LIMIT 1");
        $stmtVerif->execute([':id' => $formationId, ':maison_id' => $maison['id']]);
        $formationExistante = $stmtVerif->fetch();

        if (!$formationExistante) {
            http_response_code(403);
            die('Accès refusé : cette formation ne vous appartient pas.');
        }

        // Si aucune nouvelle image n'a été envoyée, on conserve l'ancienne
        $imageAUtiliser = $cheminImage ?? $formationExistante['image_couverture'];

        $stmtUpdate = $pdo->prepare("
            UPDATE formations
            SET titre = :titre, domaine = :domaine, description = :description,
                prix = :prix, image_couverture = :image_couverture
            WHERE id = :id
        ");
        $stmtUpdate->execute([
            ':titre'            => $titre,
            ':domaine'          => $domaine,
            ':description'      => $description,
            ':prix'             => $prix,
            ':image_couverture' => $imageAUtiliser,
            ':id'               => $formationId,
        ]);

        header('Location: ../formateur/formations.php?message=modifie');
        exit;

    } else {
        // ===== MODE CRÉATION =====
        // Toujours créée en "brouillon" : le formateur doit explicitement
        // publier depuis la liste, pour éviter une mise en ligne accidentelle.
        $stmtInsert = $pdo->prepare("
            INSERT INTO formations (maison_id, titre, domaine, description, prix, image_couverture, statut)
            VALUES (:maison_id, :titre, :domaine, :description, :prix, :image_couverture, 'brouillon')
        ");
        $stmtInsert->execute([
            ':maison_id'        => $maison['id'],
            ':titre'            => $titre,
            ':domaine'          => $domaine,
            ':description'      => $description,
            ':prix'             => $prix,
            ':image_couverture' => $cheminImage,
        ]);

        header('Location: ../formateur/formations.php?message=cree');
        exit;
    }

} catch (PDOException $e) {
    error_log('Erreur enregistrement formation : ' . $e->getMessage());
    header('Location: ../formateur/formations.php?erreur=serveur');
    exit;
}
