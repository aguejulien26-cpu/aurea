-- ============================================================
-- FICHIER : donnees-demo.sql
-- RÔLE    : Données FACTICES pour tester immédiatement le site
--           (formateurs + maisons de formation + formations).
--           À NE PAS utiliser en production — uniquement pour
--           vérifier que la page d'accueil affiche bien les
--           cartes de formation avec domaine + nom du formateur.
-- USAGE   : mysql -u root -p aurea_platform < donnees-demo.sql
-- ============================================================

USE aurea_platform;

-- Deux formateurs de démonstration
INSERT INTO users (role, nom_complet, email, mot_de_passe, statut, email_verifie) VALUES
('formateur', 'Awa Diallo', 'awa.diallo@example.com', '$2y$10$placeholder', 'actif', 1),
('formateur', 'Jean Kouassi', 'jean.kouassi@example.com', '$2y$10$placeholder', 'actif', 1);

-- Leurs maisons de formation respectives
INSERT INTO maisons_formation (formateur_id, nom, description, prix_acces_global) VALUES
((SELECT id FROM users WHERE email = 'awa.diallo@example.com'), 'Awa Academy', 'Spécialiste en marketing digital et stratégie de marque.', 25000.00),
((SELECT id FROM users WHERE email = 'jean.kouassi@example.com'), 'CodeAfrika', 'Maison de formation en développement web et logiciel.', 35000.00);

-- Formations publiées (visibles sur la page d'accueil)
INSERT INTO formations (maison_id, titre, domaine, description, prix, statut) VALUES
((SELECT id FROM maisons_formation WHERE nom = 'Awa Academy'), 'Maîtriser le Marketing Digital', 'Marketing digital', 'Apprenez les fondamentaux du marketing en ligne, SEO et réseaux sociaux.', 15000.00, 'publiee'),
((SELECT id FROM maisons_formation WHERE nom = 'Awa Academy'), 'Stratégie de Marque Avancée', 'Marketing digital', 'Construisez une identité de marque forte et mémorable.', 18000.00, 'publiee'),
((SELECT id FROM maisons_formation WHERE nom = 'CodeAfrika'), 'Développement Web avec PHP', 'Développement web', 'De zéro à la création de sites web dynamiques et sécurisés.', 22000.00, 'publiee'),
((SELECT id FROM maisons_formation WHERE nom = 'CodeAfrika'), 'JavaScript Moderne', 'Développement web', 'ES6+, manipulation du DOM, et programmation asynchrone.', NULL, 'publiee');
