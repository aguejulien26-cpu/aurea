# Aurea — Plateforme Hybride Presse / Formation / Publicité

## Architecture du projet (Phase 1 — Fondations)

```
aurea/
├── index.html                      Page d'accueil — structure HTML pure
├── candidature-formateur.php       Page de candidature formateur (lien privé uniquement)
├── database.sql                    Schéma complet de la base de données
├── donnees-demo.sql                Données factices pour tester immédiatement
│
├── config/
│   ├── database.php                Connexion PDO unique à la BDD (à modifier selon hébergeur)
│   └── .htaccess                   Bloque l'accès direct à ce dossier
│
├── api/
│   ├── formations.php              Retourne les formations en JSON (utilisé par main.js)
│   └── soumettre-candidature.php   Traite l'envoi du formulaire de candidature
│
├── assets/
│   ├── css/
│   │   ├── style.css               Style principal du site (thème premium sombre/or)
│   │   └── candidature.css         Style spécifique à la page de candidature
│   └── js/
│       └── main.js                 Logique dynamique : charge et affiche les formations
│
└── uploads/
    └── identity_docs/              Stockage des pièces d'identité (candidatures formateurs)
```

## Comment ça fonctionne (logique générale)

1. **index.html** est une coquille HTML vide pour les formations — il ne contient
   aucune donnée codée en dur.
2. Au chargement, **main.js** appelle **api/formations.php** en AJAX (fetch).
3. **api/formations.php** interroge la base de données et renvoie en JSON la liste
   des formations publiées, avec le **domaine** et le **nom du formateur** de chacune.
4. **main.js** construit les cartes visuelles à partir de ces données et les insère
   dans la page. Un clic sur une carte redirige vers la page d'inscription de
   cette formation précise.
5. **candidature-formateur.php** est invisible du grand public : elle n'existe dans
   aucun menu. Elle exige un `?token=...` valide dans l'URL, généré uniquement
   par le Super Admin depuis son tableau de bord (à construire en Phase 2).

## Installation locale (test)

1. Installer un environnement PHP + MySQL (XAMPP, WAMP, ou MAMP).
2. Importer la base : `mysql -u root -p < database.sql`
3. Importer les données de test : `mysql -u root -p aurea_platform < donnees-demo.sql`
4. Adapter `config/database.php` si vos identifiants MySQL diffèrent.
5. Placer le dossier `aurea/` dans `htdocs/` (XAMPP) ou `www/` (WAMP).
6. Ouvrir `http://localhost/aurea/index.html` dans le navigateur.

## Phase 2 — Ajouts (authentification + admin + inscription)

```
aurea/
├── connexion.php                       Page de connexion (tous rôles)
├── deconnexion.php                     Destruction de session
├── inscription.php                     Page d'inscription à une formation précise
├── database-phase2.sql                 Nouvelle table : inscriptions
│
├── includes/
│   └── auth.php                        Gestion centrale des sessions et des rôles
│
├── admin/
│   └── tableau-de-bord.php             Dashboard Super Admin (protégé) + génération de lien formateur
│
├── api/
│   ├── connexion-traitement.php        Vérifie identifiants, ouvre la session
│   ├── generer-invitation.php          Crée un token d'invitation formateur (Super Admin uniquement)
│   └── inscription-traitement.php      Crée le compte étudiant + l'inscription à la formation
│
└── assets/
    ├── css/{auth,admin,inscription}.css
    └── js/admin.js                     Génération de lien en AJAX + copier-coller
```

### Comment générer un lien de candidature formateur (test)

1. Importer `database-phase2.sql` après les fichiers précédents.
2. Créer manuellement un compte Super Admin avec un mot de passe haché :
   ```php
   <?php echo password_hash('VotreMotDePasse123!', PASSWORD_DEFAULT);
   ```
   Mettre ce hash dans la colonne `mot_de_passe` de la table `users` (role = super_admin).
3. Se connecter via `connexion.php`.
4. Sur `admin/tableau-de-bord.php`, cliquer sur **Générer le lien** → un lien
   `candidature-formateur.php?token=...` apparaît, à copier et envoyer manuellement
   au candidat (par email, WhatsApp, etc.). C'est la SEULE façon d'accéder à
   cette page — elle n'apparaît dans aucun menu public.

## Phase 3 — Validation des candidatures formateur

```
aurea/
├── donnees-demo-phase3.sql             Candidature factice "en_attente" pour tester le flux
│
├── admin/
│   ├── candidatures.php                Liste des candidatures, filtrable par statut
│   └── candidature-detail.php          Dossier complet (infos + documents) + décision
│
└── api/
    └── traiter-candidature.php         Approuve (crée compte formateur + Maison de Formation
                                         automatiquement) ou refuse la candidature
```

### Comment ça fonctionne

1. Le Super Admin va sur `admin/tableau-de-bord.php` → clique sur la carte
   **"Candidatures en attente"** (ou directement `admin/candidatures.php`).
2. Il filtre par statut (en attente / approuvées / refusées / toutes) via les onglets.
3. Il clique sur **"Voir le dossier"** d'une candidature → arrive sur
   `admin/candidature-detail.php` qui affiche TOUT : infos personnelles,
   informations professionnelles, résumé d'expérience, et les 3 documents
   d'identité (pièce, photo, selfie) cliquables.
4. Deux choix :
   - **Approuver** → le système crée automatiquement :
     - un compte `users` avec `role = formateur` et un mot de passe temporaire
       sécurisé (généré aléatoirement, jamais en clair dans le code)
     - sa `maison_formation` associée, prête à recevoir des formations
   - **Refuser** → la candidature passe en statut `refuse`, aucun compte créé.
5. Tout se fait dans **une seule transaction SQL** : si une étape échoue
   (ex : email déjà utilisé), rien n'est créé à moitié.

### ⚠️ Point de sécurité à connaître avant mise en production

Dans `admin/candidature-detail.php`, les documents d'identité sont actuellement
liés directement vers `/uploads/identity_docs/...`. C'est correct pour le
développement, mais en production il faudra créer un script PHP intermédiaire
(`api/voir-document.php?fichier=...`) qui revérifie `exigerRole('super_admin')`
avant de livrer le fichier, pour empêcher quiconque de deviner une URL de
document sensible.

### Tester le flux maintenant

1. Importer `donnees-demo-phase3.sql` (après `database.sql` + `database-phase2.sql`).
2. Se connecter en Super Admin.
3. Aller sur `admin/candidatures.php` → voir la candidature "Fatou Sow" → l'approuver.
4. Vérifier en base : un nouveau `users` (role=formateur) et une nouvelle
   `maisons_formation` doivent apparaître automatiquement.

## Phase 4 — Emails automatiques

```
aurea/
├── database-phase4.sql                 Ajoute le token de validation email sur users
├── verifier-email.php                  Page "Vérifiez votre boîte mail" (+ message audio)
├── valider-email.php                   Traite le clic sur le lien reçu par email
│
├── vendor/phpmailer/                   Librairie PHPMailer (envoi SMTP fiable)
│
├── config/
│   └── mail.php                        Paramètres SMTP (hôte, port, identifiants)
│
├── includes/
│   └── mailer.php                      Fonction envoyerEmail() — point unique d'envoi
│
├── emails/
│   ├── template-base.php               Habillage HTML commun (logo, couleurs, footer)
│   ├── formateur-approuve.php          Email avec identifiants temporaires (formateur)
│   ├── candidature-refusee.php         Email de refus courtois
│   └── etudiant-bienvenue.php          Email de validation après inscription
│
└── assets/
    ├── js/audio-messages.js            Lecture des messages audio (cahier des charges)
    └── audio/LISEZ-MOI.txt             Instructions pour les fichiers audio du promoteur
```

### ⚠️ Avant que les emails fonctionnent réellement

Il faut renseigner de vrais identifiants SMTP dans `config/mail.php` :
```php
define('SMTP_HOTE', 'smtp.ton-fournisseur.com');
define('SMTP_UTILISATEUR', 'ton-adresse@domaine.com');
define('SMTP_MOT_DE_PASSE', 'ton-mot-de-passe-application');
```
Tant que ce n'est pas fait, les emails échoueront silencieusement (erreur
journalisée via `error_log`, mais **aucune action métier n'est bloquée** —
ex : si l'email échoue, le compte formateur est quand même créé).

### Flux complet maintenant conforme au cahier des charges

1. Étudiant s'inscrit → compte créé avec un token de validation
2. Redirection vers `verifier-email.php` → **message audio joué automatiquement**
   ("Veuillez vous diriger vers l'email que vous avez reçu")
3. Email de confirmation reçu → clic sur le lien → `valider-email.php` active le compte
4. Redirection automatique vers `paiement.php` (à construire en Phase 5)

Le même mécanisme `assets/js/audio-messages.js` sera réutilisé sur la future
page de confirmation de paiement pour le second message audio prévu au
cahier des charges ("Merci et félicitations pour le paiement...").

### Candidature formateur — emails déjà branchés

- **Approuvée** → email avec identifiants temporaires + lien de connexion
- **Refusée** → email de refus courtois, sans détail technique

## Phase 5 — Tableau de bord Formateur

```
aurea/
├── formateur/
│   ├── _navigation.php                 Header de nav commun (inclus partout, jamais appelé seul)
│   ├── tableau-de-bord.php             Statistiques + aperçu des formations récentes
│   ├── formations.php                  Liste complète, filtrable, avec actions rapides
│   ├── formation.php                   Formulaire unifié création/modification
│   └── etudiants.php                   Liste des étudiants inscrits à ses formations
│
├── api/
│   ├── formation-enregistrer.php       Crée OU modifie une formation (upload image inclus)
│   ├── formation-changer-statut.php    Publier / archiver / republier
│   └── formation-generer-lien.php      Lien d'inscription spécifique par formation (AJAX)
│
└── assets/
    ├── css/formateur.css
    └── js/formateur.js                 Génération de lien + copier-coller
```

### Comment ça fonctionne

1. Le formateur se connecte → `formateur/tableau-de-bord.php` (sa propre Maison
   de Formation, isolée des autres formateurs — toutes les requêtes filtrent
   sur `maison_id` lié à `formateur_id = idUtilisateur()`).
2. Il clique **"Nouvelle formation"** → `formateur/formation.php` → la formation
   est créée en statut **brouillon** (jamais visible publiquement par défaut).
3. Depuis `formateur/formations.php`, il clique **"Publier"** → la formation
   apparaît immédiatement sur la page d'accueil publique (`api/formations.php`
   ne lit que les formations `statut = 'publiee'`).
4. Il peut aussi générer un **lien d'inscription spécifique** à cette formation
   (cahier des charges 3.2/5) — un clic, le lien est créé une seule fois et
   reste stable pour ses campagnes marketing.
5. `formateur/etudiants.php` lui donne une vue d'ensemble de tous ses inscrits,
   toutes formations confondues.

### Sécurité multi-tenant (important)

Trois fichiers vérifient systématiquement la **propriété** avant toute lecture
ou écriture (`api/formation-enregistrer.php`, `api/formation-changer-statut.php`,
`api/formation-generer-lien.php`) : un formateur ne peut **jamais** voir ou
modifier la formation d'un autre, même en manipulant l'URL ou les requêtes
réseau directement (protection contre les attaques IDOR).

### Vérification effectuée

34 fichiers PHP du projet validés avec `php -l` (zéro erreur de syntaxe) avant livraison.

## Prochaines étapes (Phase 6 — à valider avec toi)

- Intégration réelle des paiements (FedaPay / Kkiapay / Stripe configurables par l'admin)
- Page `paiement.php` + message audio post-paiement + page "espace confort"
- Paramètres personnalisables de la Maison de Formation (logo, bannière, thème)
