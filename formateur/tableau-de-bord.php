<?php
/**
 * FICHIER : formateur/tableau-de-bord.php
 * RÔLE    : Page d'accueil de l'espace formateur. Affiche les
 *           statistiques de sa Maison de Formation et un aperçu
 *           rapide de ses formations récentes.
 *           PROTÉGÉ : exigerRole('formateur')
 *
 * ARCHITECTURE : Chaque formateur n'a accès qu'aux données liées
 *           à SA PROPRE maison_formation (jointure systématique sur
 *           formateur_id = idUtilisateur()). Aucune requête de ce
 *           fichier ne doit jamais retourner les données d'un autre
 *           formateur — c'est la garantie d'isolation multi-tenant
 *           exigée par le cahier des charges.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

exigerRole('formateur');

$formateurId = idUtilisateur();

// --- Récupère la Maison de Formation du formateur connecté ---
$stmtMaison = $pdo->prepare("SELECT * FROM maisons_formation WHERE formateur_id = :id LIMIT 1");
$stmtMaison->execute([':id' => $formateurId]);
$maison = $stmtMaison->fetch();

if (!$maison) {
    // Cas anormal : un formateur doit toujours avoir une maison (créée à
    // l'approbation de sa candidature). Sécurité défensive.
    die('Erreur : aucune Maison de Formation associée à ce compte.');
}

// --- Statistiques rapides ---
$stmtNbFormations = $pdo->prepare("SELECT COUNT(*) AS total FROM formations WHERE maison_id = :maison_id");
$stmtNbFormations->execute([':maison_id' => $maison['id']]);
$nbFormations = $stmtNbFormations->fetch()['total'];

$stmtNbPubliees = $pdo->prepare("SELECT COUNT(*) AS total FROM formations WHERE maison_id = :maison_id AND statut = 'publiee'");
$stmtNbPubliees->execute([':maison_id' => $maison['id']]);
$nbPubliees = $stmtNbPubliees->fetch()['total'];

$stmtNbEtudiants = $pdo->prepare("SELECT COUNT(DISTINCT etudiant_id) AS total FROM inscriptions WHERE maison_id = :maison_id");
$stmtNbEtudiants->execute([':maison_id' => $maison['id']]);
$nbEtudiants = $stmtNbEtudiants->fetch()['total'];

// --- Les 5 formations les plus récentes ---
$stmtFormationsRecentes = $pdo->prepare("
    SELECT id, titre, domaine, prix, statut, date_creation
    FROM formations
    WHERE maison_id = :maison_id
    ORDER BY date_creation DESC
    LIMIT 5
");
$stmtFormationsRecentes->execute([':maison_id' => $maison['id']]);
$formationsRecentes = $stmtFormationsRecentes->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Tableau de bord — <?php echo htmlspecialchars($maison['nom']); ?> — Aurea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/formateur.css">
</head>
<body>

    <?php require __DIR__ . '/_navigation.php'; ?>

    <main class="container admin-wrapper">

        <h1 class="admin-titre"><?php echo htmlspecialchars($maison['nom']); ?></h1>
        <p class="admin-soustitre">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_nom']); ?></p>

        <!-- ===== Statistiques rapides ===== -->
        <div class="admin-stats-grid">
            <div class="admin-stat-card">
                <i class="fa-solid fa-book"></i>
                <div>
                    <span class="admin-stat-valeur"><?php echo $nbFormations; ?></span>
                    <span class="admin-stat-label">Formations créées</span>
                </div>
            </div>
            <div class="admin-stat-card">
                <i class="fa-solid fa-circle-check"></i>
                <div>
                    <span class="admin-stat-valeur"><?php echo $nbPubliees; ?></span>
                    <span class="admin-stat-label">Formations publiées</span>
                </div>
            </div>
            <div class="admin-stat-card">
                <i class="fa-solid fa-user-graduate"></i>
                <div>
                    <span class="admin-stat-valeur"><?php echo $nbEtudiants; ?></span>
                    <span class="admin-stat-label">Étudiants inscrits</span>
                </div>
            </div>
        </div>

        <!-- ===== Formations récentes ===== -->
        <section class="admin-section">
            <div class="admin-section-header" style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h2><i class="fa-solid fa-clock-rotate-left"></i> Formations récentes</h2>
                </div>
                <a href="formation.php" class="btn btn-gold btn-petit">
                    <i class="fa-solid fa-plus"></i> Nouvelle formation
                </a>
            </div>

            <?php if (empty($formationsRecentes)): ?>
                <p class="table-vide">Vous n'avez encore créé aucune formation.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Domaine</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($formationsRecentes as $formation): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($formation['titre']); ?></strong></td>
                                <td><?php echo htmlspecialchars($formation['domaine']); ?></td>
                                <td><?php echo $formation['prix'] ? number_format($formation['prix'], 0, ',', ' ') . ' FCFA' : 'Accès global'; ?></td>
                                <td>
                                    <span class="statut-badge statut-<?php echo $formation['statut'] === 'publiee' ? 'actif' : ($formation['statut'] === 'archivee' ? 'expire' : 'utilise'); ?>">
                                        <?php echo htmlspecialchars($formation['statut']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="formation.php?id=<?php echo (int) $formation['id']; ?>" class="btn btn-outline btn-petit">
                                        Modifier <i class="fa-solid fa-pen"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="formations.php" class="lien-voir-tout">Voir toutes mes formations <i class="fa-solid fa-arrow-right"></i></a>
            <?php endif; ?>
        </section>

    </main>

</body>
</html>
