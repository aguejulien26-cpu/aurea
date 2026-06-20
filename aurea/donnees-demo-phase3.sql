-- ============================================================
-- FICHIER : donnees-demo-phase3.sql
-- RÔLE    : Crée une invitation + une candidature factice en
--           statut "en_attente" pour tester immédiatement le
--           flux d'approbation/refus côté Super Admin.
-- USAGE   : mysql -u root -p aurea_platform < donnees-demo-phase3.sql
-- PRÉ-REQUIS : un compte super_admin doit déjà exister (id=1
--           normalement, celui créé dans database.sql)
-- ============================================================

USE aurea_platform;

-- Une invitation déjà "utilisée" (simulateur de lien envoyé puis rempli)
INSERT INTO trainer_invitations (token, created_by, email_autorise, statut, date_expiration, date_utilisation)
VALUES (
    'demo_token_phase3_test1234567890abcdef',
    1,
    'fatou.sow@example.com',
    'utilise',
    DATE_ADD(NOW(), INTERVAL 7 DAY),
    NOW()
);

-- La candidature associée, en attente de décision
INSERT INTO trainer_applications (
    invitation_id, nom_complet, date_naissance, nationalite, email, telephone,
    profession, domaine_activite, niveau_etude, resume_experience, reseaux_sociaux,
    piece_identite_path, photo_path, selfie_verification_path, statut
) VALUES (
    (SELECT id FROM trainer_invitations WHERE token = 'demo_token_phase3_test1234567890abcdef'),
    'Fatou Sow',
    '1990-04-12',
    'Sénégalaise',
    'fatou.sow@example.com',
    '+221 77 123 45 67',
    'Consultante RH',
    'Ressources humaines',
    'Master 2',
    'Plus de 8 ans d\'expérience en recrutement et formation professionnelle. J\'ai accompagné plus de 200 entreprises dans leur stratégie RH.',
    'https://linkedin.com/in/fatousow-demo',
    'uploads/identity_docs/exemple_piece_identite.jpg',
    'uploads/identity_docs/exemple_photo.jpg',
    'uploads/identity_docs/exemple_selfie.jpg',
    'en_attente'
);
