-- ============================================================
-- FICHIER : donnees-demo-phase5.sql
-- RÔLE    : Définit un mot de passe CONNU pour le formateur de
--           démonstration "Awa Diallo" (créé dans donnees-demo.sql)
--           afin de tester la connexion et le tableau de bord
--           formateur immédiatement.
-- USAGE   : mysql -u root -p aurea_platform < donnees-demo-phase5.sql
--
-- Identifiants de test :
--   Email    : awa.diallo@example.com
--   Mot de passe : Formateur123!
-- ============================================================

USE aurea_platform;

-- Hash réel correspondant à "Formateur123!" (vérifié avec password_hash en PHP)
UPDATE users
SET mot_de_passe = '$2y$10$EeRKiHoolPSA38wJnTEVh.s2pPt0Am37pdbgNi2YE/f5O77oZE2q.'
WHERE email = 'awa.diallo@example.com';
