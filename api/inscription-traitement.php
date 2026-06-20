<?php
/**
 * FICHIER : api/inscription-traitement.php
 * RÔLE    : Traite la soumission du formulaire d'inscription.php.
 *           Étapes (conformes au cahier des charges 3.3) :
 *             1. Crée le compte étudiant (ou rejette si email déjà utilisé
 *                ailleurs — compte unique et non partageable)
 *             2. Crée l'enregistrement "inscriptions" en statut "en_attente"
 *             3. Génère un token de validation email + envoie l'email
 *             4. Ouvre la session étudiant
 *             5. Redirige vers une page "Vérifiez votre email" (PAS
 *                directement vers le paiement — la validation email
 *                doit se faire avant, conformément au cahier des charges)
 *
 * NOTE  : Le message AUDIO pré-enregistré ("Veuillez vous diriger
 *         vers l'email que vous avez reçu") est déclenché côté
 *         JS sur la page verifier-email.php, pas ici.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../emails/etudiant-bienvenue.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.html');
    exit;
}

$formationId = isset($_POST['formation_id']) ? (int) $_POST['formation_id'] : 0;
$maisonId = isset($_POST['maison_id']) ? (int) $_POST['maison_id'] : 0;
$nomComplet = trim($_POST['nom_complet'] ?? '');
$email = trim($_POST['email'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$motDePasse = $_POST['mot_de_passe'] ?? '';

// --- Validation simple des champs ---
if (!$formationId || !$maisonId || $nomComplet === '' || $email === '' || $telephone === '' || strlen($motDePasse) < 8) {
    header('Location: /inscription.php?id=' . $formationId . '&erreur=champs');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /inscription.php?id=' . $formationId . '&erreur=email');
    exit;
}

try {
    $pdo->beginTransaction();

    $estNouveauCompte = false;

    // --- Vérifie l'unicité du compte (email = identifiant unique et non partageable) ---
    $stmtVerif = $pdo->prepare("SELECT id, role FROM users WHERE email = :email LIMIT 1");
    $stmtVerif->execute([':email' => $email]);
    $utilisateurExistant = $stmtVerif->fetch();

    if ($utilisateurExistant) {
        // L'email existe déjà : on ne crée pas de doublon de compte.
        // Si c'est déjà un étudiant, on l'inscrit simplement à cette formation.
        if ($utilisateurExistant['role'] !== 'etudiant') {
            $pdo->rollBack();
            header('Location: /inscription.php?id=' . $formationId . '&erreur=email_utilise');
            exit;
        }
        $etudiantId = $utilisateurExistant['id'];
    } else {
        // --- Création du nouveau compte étudiant ---
        // Un token de validation d'email est généré dès la création
        // (cahier des charges 6.2 — "Validation Email" avant paiement).
        $tokenValidation = bin2hex(random_bytes(32));
        $expirationToken = (new DateTime())->modify('+24 hours')->format('Y-m-d H:i:s');

        $stmtCreation = $pdo->prepare("
            INSERT INTO users (
                role, nom_complet, email, mot_de_passe, telephone, statut,
                email_verifie, token_validation_email, token_validation_expiration
            )
            VALUES (
                'etudiant', :nom_complet, :email, :mot_de_passe, :telephone, 'actif',
                0, :token_validation, :token_expiration
            )
        ");
        $stmtCreation->execute([
            ':nom_complet'      => $nomComplet,
            ':email'            => $email,
            ':mot_de_passe'     => password_hash($motDePasse, PASSWORD_DEFAULT),
            ':telephone'        => $telephone,
            ':token_validation' => $tokenValidation,
            ':token_expiration' => $expirationToken,
        ]);
        $etudiantId = (int) $pdo->lastInsertId();
        $estNouveauCompte = true;
    }

    // --- Empêche une double inscription à la même formation ---
    $stmtDoublon = $pdo->prepare("
        SELECT id FROM inscriptions WHERE etudiant_id = :etudiant_id AND formation_id = :formation_id LIMIT 1
    ");
    $stmtDoublon->execute([':etudiant_id' => $etudiantId, ':formation_id' => $formationId]);

    if (!$stmtDoublon->fetch()) {
        $stmtInscription = $pdo->prepare("
            INSERT INTO inscriptions (etudiant_id, formation_id, maison_id, acces_global, statut_paiement)
            VALUES (:etudiant_id, :formation_id, :maison_id, 0, 'en_attente')
        ");
        $stmtInscription->execute([
            ':etudiant_id'  => $etudiantId,
            ':formation_id' => $formationId,
            ':maison_id'    => $maisonId,
        ]);
    }

    $pdo->commit();

    // --- Ouverture de session étudiant ---
    $stmtUtilisateur = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmtUtilisateur->execute([':id' => $etudiantId]);
    ouvrirSession($stmtUtilisateur->fetch());

    if ($estNouveauCompte) {
        // --- Récupère le titre de la formation pour personnaliser l'email ---
        $stmtFormation = $pdo->prepare("SELECT titre FROM formations WHERE id = :id");
        $stmtFormation->execute([':id' => $formationId]);
        $titreFormation = $stmtFormation->fetch()['titre'] ?? 'votre formation';

        envoyerEmailValidationEtudiant($email, $nomComplet, $tokenValidation, $titreFormation);

        // TODO (étape suivante) : déclenchement du message audio pré-enregistré
        // ("Veuillez vous diriger vers l'email que vous avez reçu") côté JS
        // sur verifier-email.php, juste après cette redirection.
        header('Location: /verifier-email.php?email=' . urlencode($email));
        exit;
    }

    // Compte étudiant déjà existant et déjà vérifié -> direction paiement
    // TODO (Phase suivante) : redirection réelle vers FedaPay / Kkiapay / Stripe
    header('Location: /paiement.php?formation_id=' . $formationId);
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Erreur inscription étudiant : ' . $e->getMessage());
    header('Location: /inscription.php?id=' . $formationId . '&erreur=serveur');
    exit;
}
