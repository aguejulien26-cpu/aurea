<?php
/**
 * FICHIER : connexion.php
 * RÔLE    : Page de connexion pour TOUS les rôles (super_admin,
 *           formateur, étudiant). Un seul formulaire, le système
 *           redirige ensuite selon le rôle détecté en base.
 *
 * ARCHITECTURE : Cette page affiche uniquement le formulaire.
 *           Le TRAITEMENT (vérification mot de passe, ouverture
 *           de session) est délégué à api/connexion-traitement.php
 *           pour respecter la séparation affichage / logique.
 */

require_once __DIR__ . '/includes/auth.php';

// Si déjà connecté, on ne montre pas le formulaire : redirection directe
if (estConnecte()) {
    redirigerSelonRole(roleUtilisateur());
}

function redirigerSelonRole(string $role): void {
    $destinations = [
        'super_admin' => '/admin/tableau-de-bord.php',
        'formateur'   => '/formateur/tableau-de-bord.php',
        'etudiant'    => '/espace/index.php',
    ];
    header('Location: ' . ($destinations[$role] ?? '/index.html'));
    exit;
}

// Message d'erreur transmis par api/connexion-traitement.php en cas d'échec
$erreur = isset($_GET['erreur']) ? $_GET['erreur'] : '';
$retour = isset($_GET['retour']) ? $_GET['retour'] : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Aurea</title>
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
            <i class="fa-solid fa-lock auth-icon"></i>
            <h1>Connexion</h1>
            <p class="auth-soustitre">Accédez à votre espace Aurea</p>

            <?php if ($erreur === 'identifiants'): ?>
                <div class="auth-alerte">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    Email ou mot de passe incorrect.
                </div>
            <?php elseif ($erreur === 'suspendu'): ?>
                <div class="auth-alerte">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    Ce compte est suspendu. Contactez l'administration.
                </div>
            <?php endif; ?>

            <form action="api/connexion-traitement.php" method="POST" class="auth-form">
                <input type="hidden" name="retour" value="<?php echo htmlspecialchars($retour); ?>">

                <div class="form-champ">
                    <label for="email">Adresse email</label>
                    <input type="email" id="email" name="email" required autofocus>
                </div>
                <div class="form-champ">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>

                <button type="submit" class="btn btn-gold form-submit">
                    Se connecter <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <p class="auth-pied">
                Pas encore de compte étudiant ? <a href="inscription.php">Découvrez nos formations</a>
            </p>
        </div>
    </main>

</body>
</html>
