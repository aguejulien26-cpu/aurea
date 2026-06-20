<?php
/**
 * FICHIER : verifier-email.php
 * RÔLE    : Page affichée juste après l'inscription, invitant
 *           l'étudiant à aller consulter sa boîte mail pour valider
 *           son compte. Conforme au cahier des charges 6.2 :
 *           "Message Audio Post-Inscription ... diffusé, indiquant :
 *           Veuillez vous diriger vers l'email que vous avez reçu."
 *
 * ARCHITECTURE : Le HTML/PHP reste minimal ici — le déclenchement
 *           du message audio est géré par assets/js/audio-messages.js
 *           (fichier séparé, réutilisable aussi pour le message
 *           post-paiement plus tard).
 */

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérifiez votre email — Aurea</title>
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
            <i class="fa-solid fa-envelope-circle-check auth-icon"></i>
            <h1>Vérifiez votre boîte mail</h1>
            <p class="auth-soustitre">
                Un email de confirmation a été envoyé à<br>
                <strong style="color:#EDE8DA;"><?php echo htmlspecialchars($email); ?></strong>
            </p>
            <p style="font-size:13px; color: var(--couleur-texte-attenue);">
                Cliquez sur le lien reçu pour activer votre compte et accéder au paiement de votre formation.
            </p>
        </div>
    </main>

    <!--
        Déclenche le message audio pré-enregistré, conforme au
        cahier des charges (étape "Message Audio Post-Inscription").
        Le fichier audio réel devra être fourni par le promoteur et
        placé dans assets/audio/post-inscription.mp3
    -->
    <script src="assets/js/audio-messages.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            jouerMessageAudio('post-inscription');
        });
    </script>

</body>
</html>
