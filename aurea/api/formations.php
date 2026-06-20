<?php
/**
 * FICHIER : api/formations.php
 * RÔLE    : Point d'API qui retourne en JSON toutes les formations
 *           publiées par les formateurs (titre, domaine, nom du
 *           formateur, prix, image). C'est ce fichier que le
 *           JavaScript de la page d'accueil (assets/js/main.js)
 *           appelle via fetch() pour afficher dynamiquement les
 *           cartes de formation.
 *
 * MÉTHODE : GET uniquement
 * SORTIE  : JSON — tableau d'objets formation
 *
 * ARCHITECTURE : Ce fichier ne génère AUCUN HTML. Il ne fait que
 *           lire la base de données et renvoyer du JSON pur.
 *           Le rendu visuel est entièrement géré côté JS dans
 *           assets/js/main.js (séparation stricte demandée).
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// On n'autorise que les requêtes GET sur cette API
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée']);
    exit;
}

// Filtre optionnel par domaine, ex : formations.php?domaine=Marketing
$domaineFiltre = isset($_GET['domaine']) ? trim($_GET['domaine']) : null;

try {
    $sql = "
        SELECT
            f.id,
            f.titre,
            f.domaine,
            f.description,
            f.image_couverture,
            f.prix,
            f.lien_inscription_token,
            mf.nom AS nom_maison,
            u.nom_complet AS nom_formateur
        FROM formations f
        INNER JOIN maisons_formation mf ON f.maison_id = mf.id
        INNER JOIN users u ON mf.formateur_id = u.id
        WHERE f.statut = 'publiee'
    ";

    $params = [];

    if ($domaineFiltre !== null && $domaineFiltre !== '') {
        $sql .= " AND f.domaine = :domaine";
        $params[':domaine'] = $domaineFiltre;
    }

    $sql .= " ORDER BY f.date_creation DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $formations = $stmt->fetchAll();

    echo json_encode([
        'succes'     => true,
        'total'      => count($formations),
        'formations' => $formations,
    ]);

} catch (PDOException $e) {
    error_log('Erreur API formations : ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['succes' => false, 'erreur' => 'Impossible de charger les formations']);
}
