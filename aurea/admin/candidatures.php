<?php
/**
 * FICHIER : admin/candidatures.php
 * RÔLE    : Liste toutes les candidatures formateur (en_attente,
 *           approuve, refuse) pour le Super Admin. Permet de filtrer
 *           par statut et de cliquer sur une candidature pour voir
 *           le dossier complet (admin/candidature-detail.php).
 *
 * ARCHITECTURE : Page de lecture seule. Aucune action de validation
 *           ne se fait ici — uniquement sur la page de détail, pour
 *           forcer le Super Admin à consulter le dossier complet
 *           (pièce d'identité, selfie...) avant toute décision.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

exigerRole('super_admin');

// Filtre par statut (par défaut : en_attente, le plus utile au quotidien)
$statutsAutorises = ['en_attente', 'approuve', 'refuse', 'tous'];
$filtreStatut = $_GET['statut'] ?? 'en_attente';
if (!in_array($filtreStatut, $statutsAutorises, true)) {
    $filtreStatut = 'en_attente';
}

$sql = "
    SELECT ta.id, ta.nom_complet, ta.email, ta.profession, ta.domaine_activite,
           ta.statut, ta.date_soumission
    FROM trainer_applications ta
";
$params = [];

if ($filtreStatut !== 'tous') {
    $sql .= " WHERE ta.statut = :statut";
    $params[':statut'] = $filtreStatut;
}

$sql .= " ORDER BY ta.date_soumission DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$candidatures = $stmt->fetchAll();

// Compteurs pour les onglets de filtre
$stmtCompteurs = $pdo->query("
    SELECT statut, COUNT(*) AS total FROM trainer_applications GROUP BY statut
");
$compteurs = ['en_attente' => 0, 'approuve' => 0, 'refuse' => 0];
foreach ($stmtCompteurs->fetchAll() as $ligne) {
    $compteurs[$ligne['statut']] = (int) $ligne['total'];
}
$totalTous = array_sum($compteurs);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Candidatures Formateurs — Aurea</title>
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

        <a href="tableau-de-bord.php" class="lien-retour"><i class="fa-solid fa-arrow-left"></i> Tableau de bord</a>
        <h1 class="admin-titre">Candidatures formateurs</h1>
        <p class="admin-soustitre">Examinez les dossiers et validez ou refusez les candidatures.</p>

        <!-- ===== Onglets de filtre par statut ===== -->
        <div class="onglets-statut">
            <a href="?statut=en_attente" class="onglet <?php echo $filtreStatut === 'en_attente' ? 'onglet-actif' : ''; ?>">
                En attente <span class="onglet-compteur"><?php echo $compteurs['en_attente']; ?></span>
            </a>
            <a href="?statut=approuve" class="onglet <?php echo $filtreStatut === 'approuve' ? 'onglet-actif' : ''; ?>">
                Approuvées <span class="onglet-compteur"><?php echo $compteurs['approuve']; ?></span>
            </a>
            <a href="?statut=refuse" class="onglet <?php echo $filtreStatut === 'refuse' ? 'onglet-actif' : ''; ?>">
                Refusées <span class="onglet-compteur"><?php echo $compteurs['refuse']; ?></span>
            </a>
            <a href="?statut=tous" class="onglet <?php echo $filtreStatut === 'tous' ? 'onglet-actif' : ''; ?>">
                Toutes <span class="onglet-compteur"><?php echo $totalTous; ?></span>
            </a>
        </div>

        <!-- ===== Liste des candidatures ===== -->
        <section class="admin-section">
            <?php if (empty($candidatures)): ?>
                <p class="table-vide">Aucune candidature dans cette catégorie.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Candidat</th>
                            <th>Domaine</th>
                            <th>Profession</th>
                            <th>Soumis le</th>
                            <th>Statut</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidatures as $candidature): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($candidature['nom_complet']); ?></strong><br>
                                    <span class="texte-discret"><?php echo htmlspecialchars($candidature['email']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($candidature['domaine_activite']); ?></td>
                                <td><?php echo htmlspecialchars($candidature['profession']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($candidature['date_soumission'])); ?></td>
                                <td>
                                    <span class="statut-badge statut-<?php echo $candidature['statut'] === 'en_attente' ? 'actif' : ($candidature['statut'] === 'approuve' ? 'actif' : 'expire'); ?>">
                                        <?php echo htmlspecialchars(str_replace('_', ' ', $candidature['statut'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="candidature-detail.php?id=<?php echo (int) $candidature['id']; ?>" class="btn btn-outline btn-petit">
                                        Voir le dossier <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    </main>

</body>
</html>
