-- ============================================================
-- FICHIER : database.sql
-- RÔLE   : Schéma complet de la base de données du projet AUREA
-- USAGE  : À importer une seule fois via phpMyAdmin ou la commande :
--          mysql -u root -p < database.sql
-- NOTE   : Ce fichier sera complété au fur et à mesure des phases
--          (presse, publicité, affiliation, certificats...).
--          Phase actuelle : utilisateurs, formateurs, formations, accueil.
-- ============================================================

CREATE DATABASE IF NOT EXISTS aurea_platform
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE aurea_platform;

-- ------------------------------------------------------------
-- TABLE : users
-- RÔLE  : Tous les comptes (super_admin, formateur, etudiant)
--         centralisés dans une seule table avec un champ "role".
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('super_admin', 'formateur', 'etudiant') NOT NULL DEFAULT 'etudiant',
    nom_complet VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,        -- haché avec password_hash()
    telephone VARCHAR(30) DEFAULT NULL,
    statut ENUM('en_attente', 'actif', 'suspendu', 'refuse') NOT NULL DEFAULT 'actif',
    email_verifie TINYINT(1) NOT NULL DEFAULT 0,
    deux_facteurs_actif TINYINT(1) NOT NULL DEFAULT 0,
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    derniere_connexion DATETIME DEFAULT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE : trainer_invitations
-- RÔLE  : Liens privés de candidature formateur.
--         Seul le Super Admin peut en créer (created_by).
--         Chaque token est unique et à usage contrôlé.
-- ------------------------------------------------------------
CREATE TABLE trainer_invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_by INT NOT NULL,                   -- id du super_admin émetteur
    email_autorise VARCHAR(150) DEFAULT NULL,   -- si renseigné, restreint le lien à cet email
    statut ENUM('actif', 'utilise', 'expire', 'revoque') NOT NULL DEFAULT 'actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_expiration DATETIME NOT NULL,
    date_utilisation DATETIME DEFAULT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE : trainer_applications
-- RÔLE  : Dossiers de candidature soumis via le lien privé.
--         Reste "en_attente" jusqu'à validation du Super Admin.
-- ------------------------------------------------------------
CREATE TABLE trainer_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitation_id INT NOT NULL,
    nom_complet VARCHAR(150) NOT NULL,
    date_naissance DATE NOT NULL,
    nationalite VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telephone VARCHAR(30) NOT NULL,
    profession VARCHAR(150) NOT NULL,
    domaine_activite VARCHAR(150) NOT NULL,
    niveau_etude VARCHAR(100) NOT NULL,
    resume_experience TEXT NOT NULL,
    reseaux_sociaux VARCHAR(255) DEFAULT NULL,
    piece_identite_path VARCHAR(255) NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    selfie_verification_path VARCHAR(255) NOT NULL,
    statut ENUM('en_attente', 'approuve', 'refuse') NOT NULL DEFAULT 'en_attente',
    date_soumission DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_traitement DATETIME DEFAULT NULL,
    FOREIGN KEY (invitation_id) REFERENCES trainer_invitations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE : maisons_formation
-- RÔLE  : Espace personnalisé de chaque formateur validé.
-- ------------------------------------------------------------
CREATE TABLE maisons_formation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    formateur_id INT NOT NULL UNIQUE,
    nom VARCHAR(150) NOT NULL,
    logo_path VARCHAR(255) DEFAULT NULL,
    banniere_path VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    prix_acces_global DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    statut ENUM('actif', 'suspendu') NOT NULL DEFAULT 'actif',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (formateur_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- TABLE : formations
-- RÔLE  : Chaque cours créé par un formateur.
--         C'est CETTE table qui alimente la page d'accueil.
-- ------------------------------------------------------------
CREATE TABLE formations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maison_id INT NOT NULL,
    titre VARCHAR(200) NOT NULL,
    domaine VARCHAR(100) NOT NULL,              -- ex : "Marketing digital"
    description TEXT NOT NULL,
    image_couverture VARCHAR(255) DEFAULT NULL,
    prix DECIMAL(10,2) DEFAULT NULL,             -- NULL = incluse uniquement dans l'accès global
    lien_inscription_token VARCHAR(64) DEFAULT NULL UNIQUE, -- lien direct par formation
    statut ENUM('brouillon', 'publiee', 'archivee') NOT NULL DEFAULT 'brouillon',
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (maison_id) REFERENCES maisons_formation(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Index pour accélérer l'affichage de la page d'accueil
-- (tri par date, filtre par statut = publiee)
-- ------------------------------------------------------------
CREATE INDEX idx_formations_statut ON formations(statut, date_creation);
CREATE INDEX idx_formations_domaine ON formations(domaine);

-- ------------------------------------------------------------
-- Compte Super Admin par défaut (à changer immédiatement après import)
-- mot de passe par défaut : ChangeMoi123! (haché ci-dessous)
-- ------------------------------------------------------------
INSERT INTO users (role, nom_complet, email, mot_de_passe, statut, email_verifie)
VALUES (
  'super_admin',
  'Super Administrateur',
  'admin@aurea.local',
  '$2y$10$2Uy3Q1Z1y3oQpQDk2L6FQuVwQpQHN6t1Yh1Sj2Lz3K9aV3X2pQbS6', -- placeholder, à régénérer
  'actif',
  1
);
