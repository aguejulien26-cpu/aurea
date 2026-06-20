<?php
/**
 * FICHIER : admin/tableau-de-bord.php
 * RÔLE    : Tableau de bord du Super Administrateur.
 *           PROTÉGÉ : accessible uniquement si role = super_admin
 *           (vérifié par exigerRole() dès la première ligne).
 *
 * ARCHITECTURE : Cette page affiche la structure. La génération
 *           effective d'un lien d'invitation se fait en AJAX via
 *           api/generer-invitation.php, géré par assets/js/admin.js.
 *           Aucune logique métier ici, uniquement l'affichage.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

exigerRole('super_admin'); // bloque tout accès non autorisé

// Quelques statistiques rapides pour le tableau de bord
$stmtFormateurs = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'formateur' AND statut = 'actif'");
$totalFormateurs = $stmtFormateurs->fetch()['total'];

$stmtEtudiants = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'etudiant'");
$totalEtudiants = $stmtEtudiants->fetch()['total'];

$stmtCandidatures = $pdo->query("SELECT COUNT(*) AS total FROM trainer_applications WHERE statut = 'en_attente'");
$candidaturesEnAttente = $stmtCandidatures->fetch()['total'];

// Historique des liens déjà générés (les plus récents en premier)
$stmtLiens = $pdo->query("
    SELECT token, statut, date_creation, date_expiration, email_autorise
    FROM trainer_invitations
    ORDER BY date_creation DESC
    LIMIT 10
");
$liensRecents = $stmtLiens->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Tableau de bord — Super Admin — Aurea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

    <header class="site-header">
        <div class="container header-inner">
            <a href="../index.html" class="logo">AUREA</a>
            <span class="badge-role"><i class="fa-solid fa-shield-halved"></i> Super Administrateur</span>
            <a href="../deconnexion.php" class="btn btn-outline">Déconnexion</a>
        </div>
    </header>

    <main class="container admin-wrapper">

        <h1 class="admin-titre">Tableau de bord</h1>
        <p class="admin-soustitre">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_nom']); ?></p>

        <!-- ===== Statistiques rapides ===== -->
        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <i class="fa-solid fa-chalkboard-user"></i>
                <div>
                    <span class="admin-stat-valeur"><?php echo $totalFormateurs; ?></span>
                    <span class="admin-stat-label">Formateurs actifs</span>
                </div>
            </div>
            <div class="admin-stat-card">
                <i class="fa-solid fa-user-graduate"></i>
                <div>
                    <span class="admin-stat-valeur"><?php echo $totalEtudiants; ?></span>
                    <span class="admin-stat-label">Étudiants inscrits</span>
                </div>
            </div>
            <a href="candidatures.php?statut=en_attente" class="admin-stat-card admin-stat-alerte admin-stat-cliquable">
                <i class="fa-solid fa-folder-open"></i>
                <div>
                    <span class="admin-stat-valeur"><?php echo $candidaturesEnAttente; ?></span>
                    <span class="admin-stat-label">Candidatures en attente</span>
                </div>
            </a>
        </div>

        <!-- ===== Génération de lien formateur ===== -->
        <section class="admin-section">
            <div class="admin-section-header">
                <h2><i class="fa-solid fa-link"></i> Lien de candidature formateur</h2>
                <p>Génère un lien privé à usage unique. Lui seul permet d'accéder au formulaire de candidature formateur.</p>
            </div>

            <div class="generateur-lien">
                <div class="form-champ">
                    <label for="email_autorise">Email du candidat (optionnel — restreint le lien à cette adresse)</label>
                    <input type="email" id="email_autorise" placeholder="candidat@exemple.com">
                </div>
                <div class="form-champ">
                    <label for="duree_validite">Durée de validité</label>
                    <select id="duree_validite">
                        <option value="24">24 heures</option>
                        <option value="72" selected>3 jours</option>
                        <option value="168">7 jours</option>
                        <option value="720">30 jours</option>
                    </select>
                </div>
                <button type="button" id="bouton-generer-lien" class="btn btn-gold">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Générer le lien
                </button>
            </div>

            <!-- Affiché en JS après génération réussie -->
            <div id="resultat-lien" class="resultat-lien" style="display:none;">
                <input type="text" id="lien-genere" readonly>
                <button type="button" id="bouton-copier-lien" class="btn btn-outline">
                    <i class="fa-solid fa-copy"></i> Copier
                </button>
            </div>
            <p id="message-generation" class="message-generation"></p>
        </section>

        <!-- ===== Historique des liens générés ===== -->
        <section class="admin-section">
            <div class="admin-section-header">
                <h2><i class="fa-solid fa-clock-rotate-left"></i> Liens récents</h2>
            </div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Email restreint</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                        <th>Expire le</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($liensRecents)): ?>
                        <tr><td colspan="5" class="table-vide">Aucun lien généré pour l'instant.</td></tr>
                    <?php else: ?>
                        <?php foreach ($liensRecents as $lien): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars(substr($lien['token'], 0, 12)); ?>…</code></td>
                                <td><?php echo htmlspecialchars($lien['email_autorise'] ?: '—'); ?></td>
                                <td>
                                    <span class="statut-badge statut-<?php echo htmlspecialchars($lien['statut']); ?>">
                                        <?php echo htmlspecialchars($lien['statut']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($lien['date_creation'])); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($lien['date_expiration'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

    </main>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
