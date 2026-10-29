# Heros-SOS

Plateforme web permettant aux citoyens de signaler des incidents commis par des
super-vilains et aux super-héros d'intervenir, conformément au cahier des
charges fonctionnelles V0.1.

## Stack technique

- PHP 8.4 (procédural, PDO, sans framework)
- MySQL 8.0
- Apache (image officielle `php:8.4-apache`)
- phpMyAdmin (environnement de développement uniquement)
- Mailhog (capture les emails envoyés par l'application en développement)
- Docker / Docker Compose

## Démarrage

```bash
docker compose up -d --build
```

Au premier démarrage, MySQL exécute automatiquement `sql/schema.sql` (tables +
quelques super-vilains de départ). Le compte administrateur est créé
automatiquement par l'application au premier chargement d'une page.

## Accès

| Service          | URL                              | Identifiants                          |
|------------------|-----------------------------------|----------------------------------------|
| Application      | http://localhost:8080             | -                                       |
| Compte admin     | -                                  | `admin@heros-sos.fr` / `Admin1234!`     |
| phpMyAdmin (dev) | http://localhost:8081             | `herosos` / `herosos`                   |
| Mailhog (emails) | http://localhost:8025             | -                                       |
| MySQL            | localhost:3307                    | `herosos` / `herosos` (db: `herosos`)   |

Tous les emails envoyés par l'application (confirmation d'inscription, mot de
passe oublié, notifications d'incident, newsletter...) sont interceptés par
Mailhog : rien n'est réellement envoyé, ce qui permet de tester sans boîte
mail réelle. Consultez-les sur http://localhost:8025.

## Arrêt / réinitialisation

```bash
docker compose down        # arrête les conteneurs
docker compose down -v     # arrête les conteneurs et supprime la base de données
```

## Structure du projet

```
heros-sos/
├── docker-compose.yml
├── docker/
│   ├── Dockerfile
│   └── apache.conf
├── sql/
│   └── schema.sql          # structure de la base + données de départ
└── app/
    ├── includes/           # connexion BDD, fonctions, templates communs
    └── public/              # racine web (DocumentRoot)
        ├── index.php, login.php, register.php, account.php, ...
        ├── incidents.php / incident.php   (déclaration et workflow)
        ├── heroes.php / villains.php      (annuaires publics)
        ├── hero-space.php, hero-*.php     (espace professionnel super-héros)
        └── admin/                         (dashboard administrateur)
```

## API JSON (pour un frontend séparé)

En plus des pages HTML server-rendered, le backend expose une API JSON sous
`/api/*`, authentifiée par JWT et avec CORS activé — voir **[API.md](API.md)**
pour la liste complète des routes et des exemples. C'est cette API qu'un
frontend hébergé dans un autre dépôt (React, Vue, mobile...) doit consommer,
au lieu d'appeler les pages `.php` qui renvoient du HTML.

## Notes de conformité au cahier des charges

- **Comptes & rôles** : tout le monde s'inscrit comme citoyen ; le statut de
  super-héros (`hero_status`) doit être validé par un administrateur avant
  l'accès à l'espace professionnel (3.1.2).
- **Workflow incident** : signalé → intervention → réussi/échoué, avec
  priorisation réservée aux super-héros et modérateurs (4.1).
- **Emails** : inscription, mot de passe oublié, notifications d'incident et
  newsletter (4.3) sont tous envoyés via `mail()`, routé vers Mailhog en
  développement par `msmtp` (voir `docker/Dockerfile`).
- **phpMyAdmin** n'est démarré que par ce `docker-compose.yml` de
  développement ; en production, ce service ne doit pas être déployé (2.4).
- **API IMDb** : l'ajout de films (5.8.4) est implémenté avec une saisie
  manuelle (titre, année, identifiant IMDb) validée par un modérateur ; le
  point d'intégration vers une vraie clé API OMDb/IMDb peut être branché dans
  `hero-movies.php` sans changer le reste du schéma.
