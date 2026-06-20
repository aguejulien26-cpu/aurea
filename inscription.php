<?php
/**
 * FICHIER : inscription.php
 * RÔLE    : Page sur laquelle atterrit l'étudiant après avoir
 *           cliqué sur une carte de formation sur la page d'accueil.
 *           Accepte deux modes d'accès :
 *             - inscription.php?id=5            (formation par id)
 *             - inscription.php?lien=TOKEN       (lien spécifique
 *               généré par le formateur, cf. cahier des charges 3.3)
 *
 * ARCHITECTURE : Cette page affiche l'offre + le formulaire.
 *           Le traitement de la création de compte étudiant et
 *           du paiement sera dans api/inscription-traitement.php
 *           (le paiement réel sera branché en Phase 3 avec les
 *           agrégateurs FedaPay / Kkiapay / Stripe).
 */

require_once __DIR__ . '/config/database.php';

$formation = null;
$messageErreur = '';

try {
    if (isset($_GET['lien']) && trim($_GET['lien']) !== '') {
        // Accès via lien spécifique par formation (généré par le formateur)
        $stmt = $pdo->prepare("
            SELECT f.*, mf.nom AS nom_maison, mf.id AS maison_id, u.nom_complet AS nom_formateur
            FROM formations f
            INNER JOIN maisons_formation mf ON f.maison_id = mf.id
            INNER JOIN users u ON mf.formateur_id = u.id
            WHERE f.lien_inscription_token = :token AND f.statut = 'publiee'
            LIMIT 1
        ");
        $stmt->execute([':token' => trim($_GET['lien'])]);
        $formation = $stmt->fetch();

    } elseif (isset($_GET['id']) && ctype_digit($_GET['id'])) {
        // Accès via identifiant simple (depuis la grille de la page d'accueil)
        $stmt = $pdo->prepare("
            SELECT f.*, mf.nom AS nom_maison, mf.id AS maison_id, u.nom_complet AS nom_formateur
            FROM formations f
            INNER JOIN maisons_formation mf ON f.maison_id = mf.id
            INNER JOIN users u ON mf.formateur_id = u.id
            WHERE f.id = :id AND f.statut = 'publiee'
            LIMIT 1
        ");
        $stmt->execute([':id' => (int) $_GET['id']]);
        $formation = $stmt->fetch();
    }

    if (!$formation) {
        $messageErreur = "Cette formation est introuvable ou n'est plus disponible.";
    }

} catch (PDOException $e) {
    error_log('Erreur page inscription : ' . $e->getMessage());
    $messageErreur = "Une erreur est survenue. Veuillez réessayer plus tard.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $formation ? htmlspecialchars($formation['titre']) . ' — Aurea' : 'Formation introuvable — Aurea'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/inscription.css">
</head>
<body>

    <header class="site-header">
        <div class="container header-inner">
            <a href="index.html" class="logo">AUREA</a>
            <a href="index.html" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Retour</a>
        </div>
    </header>

    <main class="container inscription-wrapper">

        <?php if (!$formation): ?>

            <section class="acces-refuse">
                <i class="fa-solid fa-circle-exclamation"></i>
                <h1>Formation introuvable</h1>
                <p><?php echo htmlspecialchars($messageErreur); ?></p>
                <a href="index.html" class="btn btn-gold" style="margin-top:18px;">Voir toutes les formations</a>
            </section>

        <?php else: ?>

            <div class="inscription-grid">

                <!-- ===== Colonne gauche : présentation de la formation ===== -->
                <div class="inscription-presentation">
                    <span class="carte-domaine"><?php echo htmlspecialchars($formation['domaine']); ?></span>
                    <h1><?php echo htmlspecialchars($formation['titre']); ?></h1>
                    <p class="inscription-formateur">
                        <i class="fa-solid fa-user-tie"></i>
                        Par <strong><?php echo htmlspecialchars($formation['nom_formateur']); ?></strong>
                        — <?php echo htmlspecialchars($formation['nom_maison']); ?>
                    </p>

                    <!-- Vidéo de démonstration (cahier des charges 6.1) — autoplay -->
                    <div class="video-demo">
                        <video controls autoplay muted loop poster="">
                            <!-- Le formateur pourra téléverser sa propre vidéo de démo depuis son tableau de bord -->
                            <source src="" type="video/mp4">
                            Votre navigateur ne supporte pas la lecture vidéo.
                        </video>
                        <div class="video-demo-placeholder">
                            <i class="fa-solid fa-circle-play"></i>
                            <span>Vidéo de démonstration du formateur</span>
                        </div>
                    </div>

                    <h2 class="inscription-soustitre">Description</h2>
                    <p class="inscription-description"><?php echo nl2br(htmlspecialchars($formation['description'])); ?></p>
                </div>

                <!-- ===== Colonne droite : formulaire d'inscription ===== -->
                <aside class="inscription-formulaire-zone">
                    <div class="prix-card">
                        <span class="prix-label">Prix de la formation</span>
                        <span class="prix-valeur">
                            <?php echo $formation['prix']
                                ? number_format($formation['prix'], 0, ',', ' ') . ' FCFA'
                                : 'Inclus dans l\'accès global'; ?>
                        </span>
                    </div>

                    <form action="api/inscription-traitement.php" method="POST" class="formulaire-inscription">
                        <input type="hidden" name="formation_id" value="<?php echo (int) $formation['id']; ?>">
                        <input type="hidden" name="maison_id" value="<?php echo (int) $formation['maison_id']; ?>">

                        <div class="form-champ">
                            <label for="nom_complet">Nom complet</label>
                            <input type="text" id="nom_complet" name="nom_complet" required>
                        </div>
                        <div class="form-champ">
                            <label for="email">Adresse email</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-champ">
                            <label for="telephone">Téléphone</label>
                            <input type="tel" id="telephone" name="telephone" required>
                        </div>
                        <div class="form-champ">
                            <label for="mot_de_passe">Choisir un mot de passe</label>
                            <input type="password" id="mot_de_passe" name="mot_de_passe" minlength="8" required>
                        </div>

                        <button type="submit" class="btn btn-gold form-submit">
                            S'inscrire maintenant <i class="fa-solid fa-arrow-right"></i>
                        </button>

                        <p class="inscription-note">
                            <i class="fa-solid fa-circle-info"></i>
                            Après inscription, vous recevrez un email de confirmation, puis serez dirigé vers le paiement sécurisé.
                        </p>
                    </form>
                </aside>

            </div>

        <?php endif; ?>

    </main>

</body>
</html>
