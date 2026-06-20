-- ============================================================
-- FICHIER : database-phase4.sql
-- RÔLE    : Ajouts nécessaires au système d'emails automatiques.
-- USAGE   : mysql -u root -p aurea_platform < database-phase4.sql
-- ============================================================

USE aurea_platform;

-- ------------------------------------------------------------
-- Ajout sur la table users : token de validation d'email
-- (cahier des charges 6.2 — "Validation Email")
-- ------------------------------------------------------------
ALTER TABLE users
    ADD COLUMN token_validation_email VARCHAR(64) DEFAULT NULL AFTER email_verifie,
    ADD COLUMN token_validation_expiration DATETIME DEFAULT NULL AFTER token_validation_email;

CREATE INDEX idx_users_token_validation ON users(token_validation_email);
