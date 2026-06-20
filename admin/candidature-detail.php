<?php
/**
 * FICHIER : admin/candidature-detail.php
 * RÔLE    : Affiche le dossier COMPLET d'une candidature formateur
 *           (infos personnelles, professionnelles, documents
 *           d'identité) et propose les actions Approuver / Refuser.
 *
 * ARCHITECTURE : L'affichage des documents (pièce d'identité, photo,
 *           selfie) se fait via des balises <img>/<a> pointant vers
 *           /uploads/identity_docs/. En production, ces fichiers
 *           sensibles devraient être servis par un script PHP qui
 *           vérifie la session Super Admin avant de les livrer
 *           (au lieu d'un accès direct au dossier uploads) — TODO
 *           sécurité à appliquer avant mise en ligne réelle.
 *
 *           La décision (approuver/refuser) est soumise à
 *           api/traiter-candidature.php, qui fait tout le travail
 *           métier (création du compte formateur + Maison de
 *           Formation en cas d'approbation).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

exigerRole('super_admin');

$candidatureId = isset($_GET['id']) && ctype_digit($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$candidatureId) {
    header('Location: candidatures.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT ta.*, ti.email_autorise
    FROM trainer_applications ta
    INNER JOIN trainer_invitations ti ON ta.invitation_id = ti.id
    WHERE ta.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $candidatureId]);
$candidature = $stmt->fetch();

if (!$candidature) {
    header('Location: candidatures.php');
    exit;
}

// Message de retour après une action (succès/erreur) transmis par l'API de traitement
$message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Dossier — <?php echo htmlspecialchars($candidature['nom_complet']); ?> — Aurea</title>
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

        <a href="candidatures.php" class="lien-retour"><i class="fa-solid fa-arrow-left"></i> Toutes les candidatures</a>

        <?php if ($message === 'approuve'): ?>
            <div class="auth-alerte alerte-succes">
                <i class="fa-solid fa-circle-check"></i> Candidature approuvée. Le compte formateur a été créé.
            </div>
        <?php elseif ($message === 'refuse'): ?>
            <div class="auth-alerte">
                <i class="fa-solid fa-circle-check"></i> Candidature refusée.
            </div>
        <?php endif; ?>

        <div class="dossier-header">
            <h1 class="admin-titre"><?php echo htmlspecialchars($candidature['nom_complet']); ?></h1>
            <span class="statut-badge statut-<?php echo $candidature['statut'] === 'en_attente' ? 'actif' : ($candidature['statut'] === 'approuve' ? 'actif' : 'expire'); ?>">
                <?php echo htmlspecialchars(str_replace('_', ' ', $candidature['statut'])); ?>
            </span>
        </div>

        <div class="dossier-grid">

            <!-- ===== Colonne gauche : informations ===== -->
            <div class="dossier-infos">

                <section class="admin-section">
                    <div class="admin-section-header"><h2><i class="fa-solid fa-id-card"></i> Informations personnelles</h2></div>
                    <dl class="dossier-liste">
                        <dt>Nom complet</dt><dd><?php echo htmlspecialchars($candidature['nom_complet']); ?></dd>
                        <dt>Date de naissance</dt><dd><?php echo date('d/m/Y', strtotime($candidature['date_naissance'])); ?></dd>
                        <dt>Nationalité</dt><dd><?php echo htmlspecialchars($candidature['nationalite']); ?></dd>
                        <dt>Email</dt><dd><?php echo htmlspecialchars($candidature['email']); ?></dd>
                        <dt>Téléphone</dt><dd><?php echo htmlspecialchars($candidature['telephone']); ?></dd>
                    </dl>
                </section>

                <section class="admin-section">
                    <div class="admin-section-header"><h2><i class="fa-solid fa-briefcase"></i> Informations professionnelles</h2></div>
                    <dl class="dossier-liste">
                        <dt>Profession</dt><dd><?php echo htmlspecialchars($candidature['profession']); ?></dd>
                        <dt>Domaine d'activité</dt><dd><?php echo htmlspecialchars($candidature['domaine_activite']); ?></dd>
                        <dt>Niveau d'étude</dt><dd><?php echo htmlspecialchars($candidature['niveau_etude']); ?></dd>
                        <?php if ($candidature['reseaux_sociaux']): ?>
                            <dt>Site / réseaux</dt>
                            <dd><a href="<?php echo htmlspecialchars($candidature['reseaux_sociaux']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($candidature['reseaux_sociaux']); ?></a></dd>
                        <?php endif; ?>
                    </dl>
                    <p class="dossier-resume-titre">Résumé des expériences et compétences</p>
                    <p class="dossier-resume"><?php echo nl2br(htmlspecialchars($candidature['resume_experience'])); ?></p>
                </section>

                <section class="admin-section">
                    <div class="admin-section-header"><h2><i class="fa-solid fa-shield-halved"></i> Vérification d'identité</h2></div>
                    <div class="dossier-documents">
                        <a href="../<?php echo htmlspecialchars($candidature['piece_identite_path']); ?>" target="_blank" class="document-vignette">
                            <i class="fa-solid fa-id-badge"></i>
                            <span>Pièce d'identité</span>
                        </a>
                        <a href="../<?php echo htmlspecialchars($candidature['photo_path']); ?>" target="_blank" class="document-vignette">
                            <i class="fa-solid fa-image"></i>
                            <span>Photo d'identité</span>
                        </a>
                        <a href="../<?php echo htmlspecialchars($candidature['selfie_verification_path']); ?>" target="_blank" class="document-vignette">
                            <i class="fa-solid fa-camera-retro"></i>
                            <span>Selfie de vérification</span>
                        </a>
                    </div>
                </section>

            </div>

            <!-- ===== Colonne droite : décision ===== -->
            <aside class="dossier-decision">
                <?php if ($candidature['statut'] === 'en_attente'): ?>
                    <div class="admin-section">
                        <div class="admin-section-header">
                            <h2><i class="fa-solid fa-gavel"></i> Décision</h2>
                            <p>Vérifiez bien les documents avant de valider.</p>
                        </div>

                        <form action="../api/traiter-candidature.php" method="POST" class="form-decision">
                            <input type="hidden" name="candidature_id" value="<?php echo (int) $candidature['id']; ?>">

                            <div class="form-champ">
                                <label for="nom_maison">Nom de la Maison de Formation (si approuvé)</label>
                                <input type="text" id="nom_maison" name="nom_maison"
                                       value="<?php echo htmlspecialchars($candidature['nom_complet']) . ' Academy'; ?>">
                            </div>

                            <button type="submit" name="decision" value="approuve" class="btn btn-gold form-submit">
                                <i class="fa-solid fa-circle-check"></i> Approuver le formateur
                            </button>
                            <button type="submit" name="decision" value="refuse" class="btn btn-outline form-submit btn-refuser">
                                <i class="fa-solid fa-circle-xmark"></i> Refuser la candidature
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="admin-section">
                        <p class="texte-discret">
                            Ce dossier a déjà été traité
                            (<?php echo htmlspecialchars(str_replace('_', ' ', $candidature['statut'])); ?>)
                            le <?php echo date('d/m/Y à H:i', strtotime($candidature['date_traitement'])); ?>.
                        </p>
                    </div>
                <?php endif; ?>
            </aside>

        </div>

    </main>

</body>
</html>
