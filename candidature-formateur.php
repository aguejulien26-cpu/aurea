<?php
/**
 * FICHIER : candidature-formateur.php
 * RÔLE    : Page de candidature pour devenir formateur.
 *           ACCESSIBLE UNIQUEMENT via un lien privé contenant un
 *           token valide, généré par le Super Admin.
 *           Aucun lien vers cette page n'existe dans le menu
 *           public (volontaire, demande explicite du client).
 *
 * ARCHITECTURE :
 *   1. Ce fichier PHP fait toute la vérification de sécurité
 *      AVANT d'afficher quoi que ce soit (pattern "garde en tête
 *      de fichier").
 *   2. Si le token est invalide/expiré/déjà utilisé -> on arrête
 *      tout et on affiche un message d'erreur, JAMAIS le formulaire.
 *   3. Le HTML du formulaire est inclus seulement si l'accès est
 *      validé. Le CSS et JS restent séparés dans leurs fichiers
 *      respectifs (assets/css/style.css + un JS dédié).
 *   4. Le traitement de la SOUMISSION du formulaire (upload des
 *      fichiers, écriture en base) sera géré par un fichier
 *      séparé : api/soumettre-candidature.php (prochaine étape).
 */

require_once __DIR__ . '/config/database.php';

// --- ÉTAPE 1 : Récupération et validation du token dans l'URL ---
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

$accesAutorise = false;
$messageErreur = '';
$invitation = null;

if ($token === '') {
    $messageErreur = "Aucun lien d'invitation fourni.";
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT id, statut, date_expiration, email_autorise
            FROM trainer_invitations
            WHERE token = :token
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $invitation = $stmt->fetch();

        if (!$invitation) {
            $messageErreur = "Ce lien d'invitation est introuvable ou invalide.";
        } elseif ($invitation['statut'] === 'utilise') {
            $messageErreur = "Ce lien a déjà été utilisé pour soumettre une candidature.";
        } elseif ($invitation['statut'] === 'revoque') {
            $messageErreur = "Ce lien a été révoqué par l'administration.";
        } elseif (strtotime($invitation['date_expiration']) < time()) {
            $messageErreur = "Ce lien d'invitation a expiré.";
        } else {
            // Token valide, actif, non expiré, non utilisé
            $accesAutorise = true;
        }
    } catch (PDOException $e) {
        error_log('Erreur vérification token candidature : ' . $e->getMessage());
        $messageErreur = "Une erreur est survenue. Veuillez réessayer plus tard.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Empêche l'indexation de cette page par les moteurs de recherche -->
    <meta name="robots" content="noindex, nofollow">
    <title>Candidature Formateur — Aurea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/candidature.css">
</head>
<body class="page-candidature">

    <header class="site-header">
        <div class="container header-inner">
            <span class="logo">AUREA</span>
            <span class="badge-prive">
                <i class="fa-solid fa-lock"></i> Accès privé par invitation
            </span>
        </div>
    </header>

    <main class="container candidature-wrapper">

        <?php if (!$accesAutorise): ?>

            <!-- ===== CAS 1 : ACCÈS REFUSÉ ===== -->
            <section class="acces-refuse">
                <i class="fa-solid fa-circle-exclamation"></i>
                <h1>Accès refusé</h1>
                <p><?php echo htmlspecialchars($messageErreur); ?></p>
                <p class="acces-refuse-aide">
                    Ce lien de candidature est strictement personnel et envoyé
                    individuellement par notre équipe. Si vous pensez qu'il
                    s'agit d'une erreur, contactez l'administration.
                </p>
            </section>

        <?php else: ?>

            <!-- ===== CAS 2 : ACCÈS AUTORISÉ -> FORMULAIRE ===== -->
            <section class="candidature-intro">
                <i class="fa-solid fa-user-plus"></i>
                <h1>Candidature Formateur Aurea</h1>
                <p>Ce lien vous a été transmis personnellement par notre équipe.</p>
                <span class="badge-expiration">
                    Lien valide jusqu'au
                    <?php echo date('d/m/Y', strtotime($invitation['date_expiration'])); ?>
                </span>
            </section>

            <!--
                Le formulaire envoie ses données vers un traitement séparé :
                api/soumettre-candidature.php (à créer à l'étape suivante).
                Le token est transmis en champ caché pour lier la soumission
                à l'invitation correspondante.
            -->
            <form class="formulaire-candidature"
                  action="api/soumettre-candidature.php"
                  method="POST"
                  enctype="multipart/form-data">

                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <h2 class="form-section-titre">Informations personnelles</h2>
                <div class="form-grid">
                    <div class="form-champ">
                        <label for="nom_complet">Nom complet</label>
                        <input type="text" id="nom_complet" name="nom_complet" required>
                    </div>
                    <div class="form-champ">
                        <label for="date_naissance">Date de naissance</label>
                        <input type="date" id="date_naissance" name="date_naissance" required>
                    </div>
                    <div class="form-champ">
                        <label for="nationalite">Nationalité</label>
                        <input type="text" id="nationalite" name="nationalite" required>
                    </div>
                    <div class="form-champ">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" required>
                    </div>
                    <div class="form-champ form-champ-large">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email"
                               value="<?php echo htmlspecialchars($invitation['email_autorise'] ?? ''); ?>"
                               required>
                    </div>
                </div>

                <h2 class="form-section-titre">Informations professionnelles</h2>
                <div class="form-grid">
                    <div class="form-champ">
                        <label for="profession">Profession actuelle</label>
                        <input type="text" id="profession" name="profession" required>
                    </div>
                    <div class="form-champ">
                        <label for="domaine_activite">Domaine d'activité</label>
                        <input type="text" id="domaine_activite" name="domaine_activite" required>
                    </div>
                    <div class="form-champ">
                        <label for="niveau_etude">Niveau d'étude</label>
                        <input type="text" id="niveau_etude" name="niveau_etude" required>
                    </div>
                    <div class="form-champ">
                        <label for="reseaux_sociaux">Site web / réseaux professionnels</label>
                        <input type="url" id="reseaux_sociaux" name="reseaux_sociaux">
                    </div>
                    <div class="form-champ form-champ-large">
                        <label for="resume_experience">Résumé de vos expériences et compétences pédagogiques</label>
                        <textarea id="resume_experience" name="resume_experience" rows="4" required></textarea>
                    </div>
                </div>

                <h2 class="form-section-titre">Vérification d'identité</h2>
                <div class="form-grid">
                    <div class="form-champ">
                        <label for="piece_identite">Pièce d'identité (recto)</label>
                        <input type="file" id="piece_identite" name="piece_identite" accept="image/*,.pdf" required>
                    </div>
                    <div class="form-champ">
                        <label for="photo">Photo d'identité récente</label>
                        <input type="file" id="photo" name="photo" accept="image/*" required>
                    </div>
                    <div class="form-champ form-champ-large">
                        <label for="selfie_verification">Selfie avec votre pièce d'identité en main</label>
                        <input type="file" id="selfie_verification" name="selfie_verification" accept="image/*" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-gold form-submit">
                    Envoyer ma candidature <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>

        <?php endif; ?>

    </main>

</body>
</html>
