<?php
/**
 * FICHIER : api/connexion-traitement.php
 * RÔLE    : Vérifie les identifiants soumis par connexion.php,
 *           ouvre la session si valides, redirige selon le rôle.
 *           Ne génère AUCUN HTML — uniquement des redirections.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /connexion.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$motDePasse = $_POST['mot_de_passe'] ?? '';
$retour = $_POST['retour'] ?? '';

if ($email === '' || $motDePasse === '') {
    header('Location: /connexion.php?erreur=identifiants');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $utilisateur = $stmt->fetch();

    // Toujours vérifier avec password_verify, même si l'utilisateur n'existe
    // pas, pour éviter de révéler par le temps de réponse si l'email existe.
    $hashReference = $utilisateur['mot_de_passe'] ?? '$2y$10$invalidedummyhashforfaketimingxxxxxxxxxxxxxxxxxxxxx';
    $motDePasseValide = password_verify($motDePasse, $hashReference);

    if (!$utilisateur || !$motDePasseValide) {
        header('Location: /connexion.php?erreur=identifiants');
        exit;
    }

    if ($utilisateur['statut'] !== 'actif') {
        header('Location: /connexion.php?erreur=suspendu');
        exit;
    }

    // --- Connexion réussie ---
    ouvrirSession($utilisateur);

    // Mise à jour de la dernière connexion (utile pour audit / sécurité)
    $maj = $pdo->prepare("UPDATE users SET derniere_connexion = NOW() WHERE id = :id");
    $maj->execute([':id' => $utilisateur['id']]);

    // Redirection : priorité à la page d'origine si fournie, sinon dashboard du rôle
    if ($retour !== '' && strpos($retour, '/') === 0) {
        header('Location: ' . $retour);
        exit;
    }

    $destinations = [
        'super_admin' => '/admin/tableau-de-bord.php',
        'formateur'   => '/formateur/tableau-de-bord.php',
        'etudiant'    => '/espace/index.php',
    ];
    header('Location: ' . ($destinations[$utilisateur['role']] ?? '/index.html'));
    exit;

} catch (PDOException $e) {
    error_log('Erreur connexion : ' . $e->getMessage());
    header('Location: /connexion.php?erreur=identifiants');
    exit;
}
