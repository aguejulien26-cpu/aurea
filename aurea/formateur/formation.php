<?php
/**
 * FICHIER : formateur/formation.php
 * RÔLE    : Formulaire UNIFIÉ pour créer OU modifier une formation.
 *             - formation.php          -> mode CRÉATION (formulaire vide)
 *             - formation.php?id=12    -> mode MODIFICATION (pré-rempli)
 *           Le même formulaire sert aux deux cas pour éviter la
 *           duplication de code HTML entre création et édition.
 *
 * SÉCURITÉ : En mode modification, on vérifie que la formation
 *           appartient bien à la maison_formation du formateur
 *           connecté avant d'afficher quoi que ce soit (isolation
 *           multi-tenant stricte).
 *
 * ARCHITECTURE : Le traitement (validation, upload image, écriture
 *           en base) est délégué à api/formation-enregistrer.php
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

exigerRole('formateur');

$formateurId = idUtilisateur();

$stmtMaison = $pdo->prepare("SELECT id FROM maisons_formation WHERE formateur_id = :id LIMIT 1");
$stmtMaison->execute([':id' => $formateurId]);
$maison = $stmtMaison->fetch();

$modeModification = isset($_GET['id']) && ctype_digit($_GET['id']);
$formation = [
    'id' => '', 'titre' => '', 'domaine' => '', 'description' => '',
    'prix' => '', 'image_couverture' => '',
];

if ($modeModification) {
    $stmtFormation = $pdo->prepare("SELECT * FROM formations WHERE id = :id AND maison_id = :maison_id LIMIT 1");
    $stmtFormation->execute([':id' => (int) $_GET['id'], ':maison_id' => $maison['id']]);
    $resultat = $stmtFormation->fetch();

    if (!$resultat) {
        // La formation n'existe pas OU n'appartient pas à ce formateur
        header('Location: formations.php');
        exit;
    }
    $formation = $resultat;
}

// Liste de domaines suggérés (le formateur peut aussi taper un domaine libre)
$domainesSuggeres = [
    'Marketing digital', 'Développement web', 'Comptabilité', 'Ressources humaines',
    'Langues', 'Design graphique', 'Entrepreneuriat', 'Santé', 'Agriculture', 'Autre',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $modeModification ? 'Modifier' : 'Nouvelle formation'; ?> — Aurea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/formateur.css">
</head>
<body>

    <?php require __DIR__ . '/_navigation.php'; ?>

    <main class="container admin-wrapper">

        <a href="formations.php" class="lien-retour"><i class="fa-solid fa-arrow-left"></i> Mes formations</a>
        <h1 class="admin-titre"><?php echo $modeModification ? 'Modifier la formation' : 'Créer une nouvelle formation'; ?></h1>

        <?php
        $erreur = $_GET['erreur'] ?? '';
        $messagesErreur = [
            'champs'       => 'Merci de remplir tous les champs obligatoires.',
            'image_format' => "Format d'image non autorisé (jpg, jpeg, png, webp uniquement).",
            'image_taille' => "L'image dépasse la taille maximale autorisée (4 Mo).",
        ];
        ?>
        <?php if (isset($messagesErreur[$erreur])): ?>
            <div class="auth-alerte"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($messagesErreur[$erreur]); ?></div>
        <?php endif; ?>

        <section class="admin-section" style="max-width:640px;">
            <form action="../api/formation-enregistrer.php" method="POST" enctype="multipart/form-data" class="form-decision">

                <input type="hidden" name="formation_id" value="<?php echo htmlspecialchars((string) $formation['id']); ?>">

                <div class="form-champ">
                    <label for="titre">Titre de la formation</label>
                    <input type="text" id="titre" name="titre" required
                           value="<?php echo htmlspecialchars($formation['titre']); ?>">
                </div>

                <div class="form-champ">
                    <label for="domaine">Domaine</label>
                    <input type="text" id="domaine" name="domaine" required list="liste-domaines"
                           value="<?php echo htmlspecialchars($formation['domaine']); ?>"
                           placeholder="Ex : Marketing digital">
                    <datalist id="liste-domaines">
                        <?php foreach ($domainesSuggeres as $domaine): ?>
                            <option value="<?php echo htmlspecialchars($domaine); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <div class="form-champ">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($formation['description']); ?></textarea>
                </div>

                <div class="form-champ">
                    <label for="prix">Prix de la formation (FCFA)</label>
                    <input type="number" id="prix" name="prix" min="0" step="500"
                           value="<?php echo htmlspecialchars((string) $formation['prix']); ?>"
                           placeholder="Laisser vide si incluse uniquement dans l'accès global">
                </div>

                <div class="form-champ">
                    <label for="image_couverture">Image de couverture</label>
                    <input type="file" id="image_couverture" name="image_couverture" accept="image/*">
                    <?php if (!empty($formation['image_couverture'])): ?>
                        <p class="texte-discret" style="margin-top:6px;">
                            Image actuelle : <?php echo htmlspecialchars(basename($formation['image_couverture'])); ?>
                            (laisser vide pour la conserver)
                        </p>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-gold form-submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <?php echo $modeModification ? 'Enregistrer les modifications' : 'Créer la formation (en brouillon)'; ?>
                </button>

                <p class="texte-discret" style="margin-top:10px;">
                    <i class="fa-solid fa-circle-info"></i>
                    La formation est créée en <strong>brouillon</strong> et n'apparaît pas sur le site
                    tant que vous ne cliquez pas sur "Publier" depuis la liste de vos formations.
                </p>
            </form>
        </section>

    </main>

</body>
</html>
