-- ============================================================
-- FICHIER : database-phase2.sql
-- RÔLE    : Tables ajoutées en Phase 2.
--           À importer APRÈS database.sql + donnees-demo.sql
-- USAGE   : mysql -u root -p aurea_platform < database-phase2.sql
-- ============================================================

USE aurea_platform;

-- ------------------------------------------------------------
-- TABLE : inscriptions
-- RÔLE  : Lie un étudiant à une formation (ou à une Maison de
--         Formation entière via acces_global = 1).
--         Alimentée par api/inscription-traitement.php
-- ------------------------------------------------------------
CREATE TABLE inscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id INT NOT NULL,
    formation_id INT DEFAULT NULL,        -- NULL si accès global à la maison
    maison_id INT NOT NULL,
    acces_global TINYINT(1) NOT NULL DEFAULT 0,
    statut_paiement ENUM('en_attente', 'paye', 'echoue') NOT NULL DEFAULT 'en_attente',
    date_inscription DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (etudiant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (formation_id) REFERENCES formations(id) ON DELETE CASCADE,
    FOREIGN KEY (maison_id) REFERENCES maisons_formation(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_inscriptions_etudiant ON inscriptions(etudiant_id);
