<?php
/**
 * FICHIER : formateur/formations.php
 * RÔLE    : Liste COMPLÈTE des formations du formateur connecté,
 *           filtrable par statut (brouillon/publiée/archivée).
 *           Permet de modifier, changer le statut, ou copier le
 *           lien d'inscription spécifique de chaque formation.
 *
 * SÉCURITÉ : Toutes les requêtes sont filtrées par maison_id du
 *           formateur connecté — impossible de voir ou modifier
 *           les formations d'un autre formateur.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

exigerRole('formateur');

$formateurId = idUtilisateur();

$stmtMaison = $pdo->prepare("SELECT id, nom FROM maisons_formation WHERE formateur_id = :id LIMIT 1");
$stmtMaison->execute([':id' => $formateurId]);
$maison = $stmtMaison->fetch();

$filtreStatut = $_GET['statut'] ?? 'tous';
$statutsAutorises = ['brouillon', 'publiee', 'archivee', 'tous'];
if (!in_array($filtreStatut, $statutsAutorises, true)) {
    $filtreStatut = 'tous';
}

$sql = "SELECT * FROM formations WHERE maison_id = :maison_id";
$params = [':maison_id' => $maison['id']];

if ($filtreStatut !== 'tous') {
    $sql .= " AND statut = :statut";
    $params[':statut'] = $filtreStatut;
}
$sql .= " ORDER BY date_creation DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$formations = $stmt->fetchAll();

// Compteurs pour les onglets
$stmtCompteurs = $pdo->prepare("SELECT statut, COUNT(*) AS total FROM formations WHERE maison_id = :maison_id GROUP BY statut");
$stmtCompteurs->execute([':maison_id' => $maison['id']]);
$compteurs = ['brouillon' => 0, 'publiee' => 0, 'archivee' => 0];
foreach ($stmtCompteurs->fetchAll() as $ligne) {
    $compteurs[$ligne['statut']] = (int) $ligne['total'];
}
$totalTous = array_sum($compteurs);

$messageOk = $_GET['message'] ?? '';

// URL de base du site, utile pour construire les liens d'inscription spécifiques affichés
$protocole = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$urlBase = $protocole . '://' . $_SERVER['HTTP_HOST'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Mes formations — Aurea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/formateur.css">
</head>
<body>

    <?php require __DIR__ . '/_navigation.php'; ?>

    <main class="container admin-wrapper">

        <div class="admin-section-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 18px;">
            <div>
                <h1 class="admin-titre">Mes formations</h1>
                <p class="admin-soustitre"><?php echo htmlspecialchars($maison['nom']); ?></p>
            </div>
            <a href="formation.php" class="btn btn-gold">
                <i class="fa-solid fa-plus"></i> Nouvelle formation
            </a>
        </div>

        <?php if ($messageOk === 'cree'): ?>
            <div class="auth-alerte alerte-succes"><i class="fa-solid fa-circle-check"></i> Formation créée avec succès.</div>
        <?php elseif ($messageOk === 'modifie'): ?>
            <div class="auth-alerte alerte-succes"><i class="fa-solid fa-circle-check"></i> Formation mise à jour.</div>
        <?php elseif ($messageOk === 'statut'): ?>
            <div class="auth-alerte alerte-succes"><i class="fa-solid fa-circle-check"></i> Statut mis à jour.</div>
        <?php endif; ?>

        <!-- ===== Onglets de filtre ===== -->
        <div class="onglets-statut">
            <a href="?statut=tous" class="onglet <?php echo $filtreStatut === 'tous' ? 'onglet-actif' : ''; ?>">
                Toutes <span class="onglet-compteur"><?php echo $totalTous; ?></span>
            </a>
            <a href="?statut=publiee" class="onglet <?php echo $filtreStatut === 'publiee' ? 'onglet-actif' : ''; ?>">
                Publiées <span class="onglet-compteur"><?php echo $compteurs['publiee']; ?></span>
            </a>
            <a href="?statut=brouillon" class="onglet <?php echo $filtreStatut === 'brouillon' ? 'onglet-actif' : ''; ?>">
                Brouillons <span class="onglet-compteur"><?php echo $compteurs['brouillon']; ?></span>
            </a>
            <a href="?statut=archivee" class="onglet <?php echo $filtreStatut === 'archivee' ? 'onglet-actif' : ''; ?>">
                Archivées <span class="onglet-compteur"><?php echo $compteurs['archivee']; ?></span>
            </a>
        </div>

        <section class="admin-section">
            <?php if (empty($formations)): ?>
                <p class="table-vide">Aucune formation dans cette catégorie.</p>
            <?php else: ?>
                <div class="formations-cartes-grid">
                    <?php foreach ($formations as $formation): ?>
                        <div class="carte-gestion-formation">
                            <div class="carte-gestion-en-tete">
                                <span class="carte-domaine"><?php echo htmlspecialchars($formation['domaine']); ?></span>
                                <span class="statut-badge statut-<?php echo $formation['statut'] === 'publiee' ? 'actif' : ($formation['statut'] === 'archivee' ? 'expire' : 'utilise'); ?>">
                                    <?php echo htmlspecialchars($formation['statut']); ?>
                                </span>
                            </div>
                            <h3 class="carte-gestion-titre"><?php echo htmlspecialchars($formation['titre']); ?></h3>
                            <p class="carte-gestion-prix">
                                <?php echo $formation['prix'] ? number_format($formation['prix'], 0, ',', ' ') . ' FCFA' : 'Accès global uniquement'; ?>
                            </p>

                            <?php if ($formation['lien_inscription_token']): ?>
                                <div class="lien-formation-zone">
                                    <input type="text" readonly
                                           value="<?php echo htmlspecialchars($urlBase . '/inscription.php?lien=' . $formation['lien_inscription_token']); ?>"
                                           class="lien-formation-champ">
                                    <button type="button" class="btn-copier-lien-formation" title="Copier le lien">
                                        <i class="fa-solid fa-copy"></i>
                                    </button>
                                </div>
                            <?php endif; ?>

                            <div class="carte-gestion-actions">
                                <a href="formation.php?id=<?php echo (int) $formation['id']; ?>" class="btn btn-outline btn-petit">
                                    <i class="fa-solid fa-pen"></i> Modifier
                                </a>

                                <?php if (!$formation['lien_inscription_token']): ?>
                                    <button type="button" class="btn btn-outline btn-petit bouton-generer-lien-formation"
                                            data-formation-id="<?php echo (int) $formation['id']; ?>">
                                        <i class="fa-solid fa-link"></i> Générer un lien
                                    </button>
                                <?php endif; ?>

                                <form action="../api/formation-changer-statut.php" method="POST" class="form-changement-statut">
                                    <input type="hidden" name="formation_id" value="<?php echo (int) $formation['id']; ?>">
                                    <?php if ($formation['statut'] === 'brouillon'): ?>
                                        <button type="submit" name="nouveau_statut" value="publiee" class="btn btn-gold btn-petit">
                                            <i class="fa-solid fa-upload"></i> Publier
                                        </button>
                                    <?php elseif ($formation['statut'] === 'publiee'): ?>
                                        <button type="submit" name="nouveau_statut" value="archivee" class="btn btn-outline btn-petit">
                                            <i class="fa-solid fa-box-archive"></i> Archiver
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="nouveau_statut" value="publiee" class="btn btn-outline btn-petit">
                                            <i class="fa-solid fa-rotate-left"></i> Republier
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <script src="../assets/js/formateur.js"></script>
</body>
</html>
