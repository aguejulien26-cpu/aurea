<?php
/**
 * FICHIER : api/soumettre-candidature.php
 * RÔLE    : Traite la SOUMISSION du formulaire de
 *           candidature-formateur.php (méthode POST uniquement).
 *           - Revérifie le token (jamais confiance au formulaire seul)
 *           - Upload sécurisé des fichiers (pièce d'identité, photo, selfie)
 *           - Enregistre le dossier dans trainer_applications
 *           - Marque le token comme "utilise" pour empêcher toute
 *             réutilisation du même lien
 *
 * SÉCURITÉ : Re-validation complète du token côté serveur, même si
 *           la page d'origine l'a déjà vérifié (on ne fait jamais
 *           confiance aux données venant du navigateur).
 */

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Méthode non autorisée.');
}

$token = trim($_POST['token'] ?? '');

if ($token === '') {
    die('Requête invalide : token manquant.');
}

try {
    $pdo->beginTransaction();

    // --- Revérification stricte du token (anti-triche / anti-rejeu) ---
    $stmt = $pdo->prepare("
        SELECT id, statut, date_expiration
        FROM trainer_invitations
        WHERE token = :token
        FOR UPDATE
    ");
    $stmt->execute([':token' => $token]);
    $invitation = $stmt->fetch();

    if (!$invitation
        || $invitation['statut'] !== 'actif'
        || strtotime($invitation['date_expiration']) < time()) {
        $pdo->rollBack();
        http_response_code(403);
        die('Ce lien est invalide, expiré ou déjà utilisé.');
    }

    // --- Validation des champs texte obligatoires ---
    $champsRequis = [
        'nom_complet', 'date_naissance', 'nationalite', 'email', 'telephone',
        'profession', 'domaine_activite', 'niveau_etude', 'resume_experience'
    ];
    foreach ($champsRequis as $champ) {
        if (empty($_POST[$champ])) {
            $pdo->rollBack();
            http_response_code(400);
            die('Champ obligatoire manquant : ' . htmlspecialchars($champ));
        }
    }

    // --- Gestion sécurisée des fichiers uploadés ---
    $dossierUpload = __DIR__ . '/../uploads/identity_docs/';
    $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'pdf'];
    $tailleMaxOctets = 5 * 1024 * 1024; // 5 Mo par fichier

    $cheminsFichiers = [];
    $champsFichiers = ['piece_identite', 'photo', 'selfie_verification'];

    foreach ($champsFichiers as $champFichier) {
        if (!isset($_FILES[$champFichier]) || $_FILES[$champFichier]['error'] !== UPLOAD_ERR_OK) {
            $pdo->rollBack();
            http_response_code(400);
            die('Fichier manquant ou invalide : ' . htmlspecialchars($champFichier));
        }

        $fichier = $_FILES[$champFichier];
        $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $extensionsAutorisees, true)) {
            $pdo->rollBack();
            http_response_code(400);
            die('Format de fichier non autorisé pour : ' . htmlspecialchars($champFichier));
        }

        if ($fichier['size'] > $tailleMaxOctets) {
            $pdo->rollBack();
            http_response_code(400);
            die('Fichier trop volumineux (max 5 Mo) : ' . htmlspecialchars($champFichier));
        }

        // Nom de fichier régénéré (jamais le nom d'origine -> sécurité)
        $nomFichierUnique = $champFichier . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
        $cheminDestination = $dossierUpload . $nomFichierUnique;

        if (!move_uploaded_file($fichier['tmp_name'], $cheminDestination)) {
            $pdo->rollBack();
            http_response_code(500);
            die('Erreur lors de l\'enregistrement du fichier : ' . htmlspecialchars($champFichier));
        }

        // On stocke le chemin relatif (pas le chemin absolu du serveur)
        $cheminsFichiers[$champFichier] = 'uploads/identity_docs/' . $nomFichierUnique;
    }

    // --- Enregistrement du dossier de candidature ---
    $stmtInsert = $pdo->prepare("
        INSERT INTO trainer_applications (
            invitation_id, nom_complet, date_naissance, nationalite, email,
            telephone, profession, domaine_activite, niveau_etude,
            resume_experience, reseaux_sociaux,
            piece_identite_path, photo_path, selfie_verification_path
        ) VALUES (
            :invitation_id, :nom_complet, :date_naissance, :nationalite, :email,
            :telephone, :profession, :domaine_activite, :niveau_etude,
            :resume_experience, :reseaux_sociaux,
            :piece_identite_path, :photo_path, :selfie_verification_path
        )
    ");

    $stmtInsert->execute([
        ':invitation_id'             => $invitation['id'],
        ':nom_complet'               => trim($_POST['nom_complet']),
        ':date_naissance'            => $_POST['date_naissance'],
        ':nationalite'               => trim($_POST['nationalite']),
        ':email'                     => trim($_POST['email']),
        ':telephone'                 => trim($_POST['telephone']),
        ':profession'                => trim($_POST['profession']),
        ':domaine_activite'          => trim($_POST['domaine_activite']),
        ':niveau_etude'              => trim($_POST['niveau_etude']),
        ':resume_experience'         => trim($_POST['resume_experience']),
        ':reseaux_sociaux'           => trim($_POST['reseaux_sociaux'] ?? ''),
        ':piece_identite_path'       => $cheminsFichiers['piece_identite'],
        ':photo_path'                => $cheminsFichiers['photo'],
        ':selfie_verification_path' => $cheminsFichiers['selfie_verification'],
    ]);

    // --- Le token est marqué "utilise" -> ne pourra plus jamais resservir ---
    $stmtMaj = $pdo->prepare("
        UPDATE trainer_invitations
        SET statut = 'utilise', date_utilisation = NOW()
        WHERE id = :id
    ");
    $stmtMaj->execute([':id' => $invitation['id']]);

    $pdo->commit();

    // TODO (étape suivante) : envoyer un email de notification au Super Admin
    // TODO (étape suivante) : afficher une vraie page de confirmation stylée

    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8">
          <title>Candidature envoyée — Aurea</title>
          <link rel="stylesheet" href="../assets/css/style.css"></head>
          <body style="text-align:center; padding:80px 20px;">
          <h1>Candidature envoyée avec succès</h1>
          <p style="color:#B5AD96;">Votre dossier est en cours d\'examen par notre équipe. Vous recevrez une réponse par email.</p>
          </body></html>';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erreur soumission candidature : ' . $e->getMessage());
    http_response_code(500);
    die('Une erreur est survenue lors de l\'envoi de votre candidature.');
}
