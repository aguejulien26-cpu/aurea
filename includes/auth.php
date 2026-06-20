<?php
/**
 * FICHIER : includes/auth.php
 * RÔLE    : Module CENTRAL de gestion de session et de permissions.
 *           Toute page qui nécessite de savoir "qui est connecté"
 *           ou de protéger un accès par rôle doit inclure ce
 *           fichier en tout premier, AVANT toute sortie HTML.
 *
 * USAGE   : require_once __DIR__ . '/includes/auth.php';
 *           exigerRole('super_admin'); // bloque si pas le bon rôle
 *
 * ARCHITECTURE : Centraliser cette logique ici évite de dupliquer
 *           le code de vérification de session dans chaque page
 *           protégée (tableau de bord admin, formateur, etc.)
 */

// Démarre la session seulement si elle ne l'est pas déjà
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retourne true si un utilisateur est connecté
 */
function estConnecte(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Retourne le rôle de l'utilisateur connecté, ou null
 */
function roleUtilisateur(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Retourne l'id de l'utilisateur connecté, ou null
 */
function idUtilisateur(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Bloque l'accès à la page si l'utilisateur n'a pas le rôle requis.
 * Redirige vers la page de connexion si non connecté du tout,
 * ou affiche une erreur 403 si connecté mais mauvais rôle.
 *
 * @param string $roleRequis ex: 'super_admin', 'formateur', 'etudiant'
 */
function exigerRole(string $roleRequis): void {
    if (!estConnecte()) {
        header('Location: /connexion.php?retour=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    if (roleUtilisateur() !== $roleRequis) {
        http_response_code(403);
        die('Accès refusé : vous n\'avez pas les droits nécessaires pour cette page.');
    }
}

/**
 * Ouvre la session après une connexion réussie.
 * Régénère l'id de session pour éviter la fixation de session (sécurité).
 *
 * @param array $utilisateur ligne de la table users
 */
function ouvrirSession(array $utilisateur): void {
    session_regenerate_id(true);
    $_SESSION['user_id']      = $utilisateur['id'];
    $_SESSION['user_role']    = $utilisateur['role'];
    $_SESSION['user_nom']     = $utilisateur['nom_complet'];
    $_SESSION['user_email']   = $utilisateur['email'];
}

/**
 * Ferme complètement la session (déconnexion)
 */
function fermerSession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
