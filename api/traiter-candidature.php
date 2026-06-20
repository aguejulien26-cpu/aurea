<?php
/**
 * FICHIER : api/traiter-candidature.php
 * RÔLE    : Traite la décision du Super Admin (approuver/refuser)
 *           sur une candidature formateur.
 *
 *           SI APPROUVÉ :
 *             1. Crée le compte utilisateur (role = formateur)
 *                avec un mot de passe temporaire généré aléatoirement
 *             2. Crée sa Maison de Formation associée
 *             3. Marque la candidature comme "approuve"
 *             (L'envoi de l'email avec les identifiants sera ajouté
 *              à l'étape "notifications" — TODO indiqué plus bas)
 *
 *           SI REFUSÉ :
 *             1. Marque simplement la candidature comme "refuse"
 *
 * SÉCURITÉ : exigerRole('super_admin') bloque toute tentative
 *           d'appel direct par quelqu'un d'autre que le Super Admin
 *           connecté. Toute l'opération est faite dans une seule
 *           transaction SQL pour éviter un état incohérent (ex :
 *           compte créé mais Maison de Formation manquante).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../emails/formateur-approuve.php';
require_once __DIR__ . '/../emails/candidature-refusee.php';

exigerRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/candidatures.php');
    exit;
}

$candidatureId = isset($_POST['candidature_id']) ? (int) $_POST['candidature_id'] : 0;
$decision = $_POST['decision'] ?? '';
$nomMaison = trim($_POST['nom_maison'] ?? '');

if (!$candidatureId || !in_array($decision, ['approuve', 'refuse'], true)) {
    header('Location: ../admin/candidatures.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // Verrouille la ligne pour éviter un double traitement simultané
    $stmt = $pdo->prepare("SELECT * FROM trainer_applications WHERE id = :id FOR UPDATE");
    $stmt->execute([':id' => $candidatureId]);
    $candidature = $stmt->fetch();

    if (!$candidature || $candidature['statut'] !== 'en_attente') {
        $pdo->rollBack();
        header('Location: ../admin/candidature-detail.php?id=' . $candidatureId . '&message=deja_traite');
        exit;
    }

    if ($decision === 'approuve') {

        // --- Vérifie qu'aucun compte n'existe déjà avec cet email ---
        $stmtVerifEmail = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtVerifEmail->execute([':email' => $candidature['email']]);

        if ($stmtVerifEmail->fetch()) {
            $pdo->rollBack();
            header('Location: ../admin/candidature-detail.php?id=' . $candidatureId . '&message=email_existe');
            exit;
        }

        // --- Génère un mot de passe temporaire sécurisé ---
        $motDePasseTemporaire = bin2hex(random_bytes(6)); // 12 caractères hexadécimaux

        // --- Création du compte formateur ---
        $stmtCreationCompte = $pdo->prepare("
            INSERT INTO users (role, nom_complet, email, mot_de_passe, telephone, statut, email_verifie)
            VALUES ('formateur', :nom_complet, :email, :mot_de_passe, :telephone, 'actif', 1)
        ");
        $stmtCreationCompte->execute([
            ':nom_complet'  => $candidature['nom_complet'],
            ':email'        => $candidature['email'],
            ':mot_de_passe' => password_hash($motDePasseTemporaire, PASSWORD_DEFAULT),
            ':telephone'    => $candidature['telephone'],
        ]);
        $formateurId = (int) $pdo->lastInsertId();

        // --- Création automatique de sa Maison de Formation ---
        $nomMaisonFinal = $nomMaison !== '' ? $nomMaison : ($candidature['nom_complet'] . ' Academy');

        $stmtCreationMaison = $pdo->prepare("
            INSERT INTO maisons_formation (formateur_id, nom, description, prix_acces_global, statut)
            VALUES (:formateur_id, :nom, :description, 0.00, 'actif')
        ");
        $stmtCreationMaison->execute([
            ':formateur_id' => $formateurId,
            ':nom'          => $nomMaisonFinal,
            ':description'  => 'Maison de formation de ' . $candidature['nom_complet'] . ', spécialisée en ' . $candidature['domaine_activite'] . '.',
        ]);

        // --- Mise à jour du statut de la candidature ---
        $stmtMajCandidature = $pdo->prepare("
            UPDATE trainer_applications SET statut = 'approuve', date_traitement = NOW() WHERE id = :id
        ");
        $stmtMajCandidature->execute([':id' => $candidatureId]);

        $pdo->commit();

        // --- Envoi de l'email avec les identifiants temporaires ---
        // On envoie APRÈS le commit : si l'email échoue, le compte
        // formateur reste quand même valablement créé en base.
        envoyerEmailFormateurApprouve(
            $candidature['email'],
            $candidature['nom_complet'],
            $motDePasseTemporaire,
            $nomMaisonFinal
        );

        header('Location: ../admin/candidature-detail.php?id=' . $candidatureId . '&message=approuve');
        exit;

    } else {
        // --- Refus simple, pas de création de compte ---
        $stmtRefus = $pdo->prepare("
            UPDATE trainer_applications SET statut = 'refuse', date_traitement = NOW() WHERE id = :id
        ");
        $stmtRefus->execute([':id' => $candidatureId]);

        $pdo->commit();

        // --- Envoi de l'email de refus courtois ---
        envoyerEmailCandidatureRefusee($candidature['email'], $candidature['nom_complet']);

        header('Location: ../admin/candidature-detail.php?id=' . $candidatureId . '&message=refuse');
        exit;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erreur traitement candidature : ' . $e->getMessage());
    header('Location: ../admin/candidature-detail.php?id=' . $candidatureId . '&message=erreur');
    exit;
}
