<?php
/**
 * FICHIER : formateur/_navigation.php
 * RÔLE    : En-tête de navigation COMMUN à toutes les pages de
 *           l'espace formateur (tableau de bord, formations,
 *           étudiants...). Le préfixe "_" indique que ce fichier
 *           n'est jamais appelé directement, uniquement inclus.
 *
 * ARCHITECTURE : Centraliser ce header évite de dupliquer le menu
 *           sur chaque page et permet d'ajouter un lien à un seul
 *           endroit pour qu'il apparaisse partout.
 *           Suppose que includes/auth.php a déjà été chargé par
 *           la page appelante (utilise $_SESSION directement).
 */

$pageActuelle = basename($_SERVER['PHP_SELF']);
?>
<header class="site-header">
    <div class="container header-inner">
        <a href="../index.html" class="logo">AUREA</a>

        <nav class="main-nav">
            <a href="tableau-de-bord.php" class="<?php echo $pageActuelle === 'tableau-de-bord.php' ? 'nav-actif' : ''; ?>">Tableau de bord</a>
            <a href="formations.php" class="<?php echo $pageActuelle === 'formations.php' ? 'nav-actif' : ''; ?>">Mes formations</a>
            <a href="etudiants.php" class="<?php echo $pageActuelle === 'etudiants.php' ? 'nav-actif' : ''; ?>">Étudiants</a>
        </nav>

        <div class="header-actions">
            <span class="badge-role"><i class="fa-solid fa-chalkboard-user"></i> Formateur</span>
            <a href="../deconnexion.php" class="btn btn-outline">Déconnexion</a>
        </div>
    </div>
</header>
