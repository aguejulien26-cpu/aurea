<?php
/**
 * FICHIER : valider-email.php
 * RÔLE    : Traite le clic sur le lien de validation reçu par email
 *           (emails/etudiant-bienvenue.php). Si le token est valide
 *           et non expiré, marque le compte comme vérifié et
 *           redirige vers le paiement de la formation pour laquelle
 *           l'étudiant s'est inscrit.
 *
 * ARCHITECTURE : Cette page fait à la fois la vérification ET
 *           l'affichage du résultat (succès/erreur), car c'est une
 *           action ponctuelle déclenchée par un clic externe (email)
 *           plutôt qu'un formulaire classique du site.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$succes = false;
$messageErreur = '';
$formationIdRedirection = null;

if ($token === '') {
    $messageErreur = "Lien de validation invalide.";
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT id, email, nom_complet, token_validation_expiration
            FROM users
            WHERE token_validation_email = :token AND role = 'etudiant'
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $utilisateur = $stmt->fetch();

        if (!$utilisateur) {
            $messageErreur = "Ce lien de validation est invalide ou a déjà été utilisé.";
        } elseif (strtotime($utilisateur['token_validation_expiration']) < time()) {
            $messageErreur = "Ce lien de validation a expiré. Veuillez vous reconnecter pour en recevoir un nouveau.";
        } else {
            // --- Validation réussie : on active le compte ---
            $stmtMaj = $pdo->prepare("
                UPDATE users
                SET email_verifie = 1, token_validation_email = NULL, token_validation_expiration = NULL
                WHERE id = :id
            ");
            $stmtMaj->execute([':id' => $utilisateur['id']]);

            $succes = true;

            // Retrouve la dernière formation à laquelle l'étudiant s'est inscrit
            // (la plus récente, en attente de paiement) pour le rediriger directement
            $stmtInscription = $pdo->prepare("
                SELECT formation_id FROM inscriptions
                WHERE etudiant_id = :etudiant_id AND statut_paiement = 'en_attente'
                ORDER BY date_inscription DESC LIMIT 1
            ");
            $stmtInscription->execute([':etudiant_id' => $utilisateur['id']]);
            $inscription = $stmtInscription->fetch();
            $formationIdRedirection = $inscription['formation_id'] ?? null;
        }
    } catch (PDOException $e) {
        error_log('Erreur validation email : ' . $e->getMessage());
        $messageErreur = "Une erreur est survenue. Veuillez réessayer plus tard.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validation email — Aurea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>

    <header class="site-header">
        <div class="container header-inner">
            <a href="index.html" class="logo">AUREA</a>
        </div>
    </header>

    <main class="container auth-wrapper">
        <div class="auth-card">
            <?php if ($succes): ?>
                <i class="fa-solid fa-circle-check auth-icon" style="color:#9FCB6B;"></i>
                <h1>Email confirmé !</h1>
                <p class="auth-soustitre">Votre compte est maintenant actif. Vous allez être redirigé vers le paiement.</p>
                <a href="paiement.php?formation_id=<?php echo (int) $formationIdRedirection; ?>" class="btn btn-gold form-submit">
                    Continuer vers le paiement <i class="fa-solid fa-arrow-right"></i>
                </a>
            <?php else: ?>
                <i class="fa-solid fa-circle-exclamation auth-icon" style="color:#E08A7C;"></i>
                <h1>Validation impossible</h1>
                <p class="auth-soustitre"><?php echo htmlspecialchars($messageErreur); ?></p>
                <a href="connexion.php" class="btn btn-outline form-submit">Aller à la connexion</a>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
