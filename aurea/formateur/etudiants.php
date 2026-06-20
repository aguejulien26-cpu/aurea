<?php
/**
 * FICHIER : formateur/etudiants.php
 * RÔLE    : Liste tous les étudiants inscrits à au moins une
 *           formation de la Maison de Formation du formateur connecté.
 *           Cahier des charges section 8 : "vue d'ensemble de tous
 *           les étudiants, historique des paiements, formations suivies".
 *
 * ARCHITECTURE : Une requête groupée (GROUP_CONCAT) rassemble les
 *           titres de toutes les formations suivies par un même
 *           étudiant, pour éviter d'afficher une ligne par inscription
 *           (un étudiant peut suivre plusieurs formations du même
 *           formateur).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

exigerRole('formateur');

$formateurId = idUtilisateur();

$stmtMaison = $pdo->prepare("SELECT id, nom FROM maisons_formation WHERE formateur_id = :id LIMIT 1");
$stmtMaison->execute([':id' => $formateurId]);
$maison = $stmtMaison->fetch();

$stmt = $pdo->prepare("
    SELECT
        u.id, u.nom_complet, u.email, u.telephone, u.date_creation AS date_inscription_compte,
        GROUP_CONCAT(DISTINCT f.titre SEPARATOR ', ') AS formations_suivies,
        COUNT(DISTINCT i.id) AS nb_inscriptions,
        SUM(CASE WHEN i.statut_paiement = 'paye' THEN 1 ELSE 0 END) AS nb_paiements_valides
    FROM inscriptions i
    INNER JOIN users u ON i.etudiant_id = u.id
    LEFT JOIN formations f ON i.formation_id = f.id
    WHERE i.maison_id = :maison_id
    GROUP BY u.id
    ORDER BY MAX(i.date_inscription) DESC
");
$stmt->execute([':maison_id' => $maison['id']]);
$etudiants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Mes étudiants — Aurea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/formateur.css">
</head>
<body>

    <?php require __DIR__ . '/_navigation.php'; ?>

    <main class="container admin-wrapper">

        <h1 class="admin-titre">Mes étudiants</h1>
        <p class="admin-soustitre"><?php echo htmlspecialchars($maison['nom']); ?> — <?php echo count($etudiants); ?> étudiant(s)</p>

        <section class="admin-section">
            <?php if (empty($etudiants)): ?>
                <p class="table-vide">Aucun étudiant inscrit pour le moment.</p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Formations suivies</th>
                            <th>Paiements validés</th>
                            <th>Inscrit depuis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($etudiants as $etudiant): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($etudiant['nom_complet']); ?></strong><br>
                                    <span class="texte-discret"><?php echo htmlspecialchars($etudiant['email']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($etudiant['formations_suivies'] ?: '—'); ?></td>
                                <td>
                                    <span class="statut-badge <?php echo $etudiant['nb_paiements_valides'] > 0 ? 'statut-actif' : 'statut-utilise'; ?>">
                                        <?php echo (int) $etudiant['nb_paiements_valides']; ?> / <?php echo (int) $etudiant['nb_inscriptions']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($etudiant['date_inscription_compte'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    </main>

</body>
</html>
