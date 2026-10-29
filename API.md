# Heros-SOS API (v1)

Base URL (dev) : `http://localhost:8080/api`

Authentification par **JWT Bearer token** (pas de cookie / session — c'est
ce qui permet à un frontend hébergé sur un autre domaine/port de consommer
cette API). Récupérez un token via `POST /api/auth/login`, puis envoyez-le
dans chaque requête protégée :

```
Authorization: Bearer <token>
```

## Auth

| Méthode | Route              | Auth | Description                          |
|---------|---------------------|------|---------------------------------------|
| POST    | `/auth/register`    | non  | Crée un compte citoyen                |
| POST    | `/auth/login`       | non  | Renvoie `{ token, user }`             |
| GET     | `/me`                | oui  | Utilisateur courant                   |

## Super-héros / super-vilains (lecture publique)

| Méthode | Route            | Auth | Description               |
|---------|-------------------|------|----------------------------|
| GET     | `/heroes`         | non  | Liste des héros vérifiés   |
| GET     | `/heroes/{id}`    | non  | Détail + avis + films      |
| GET     | `/villains`       | non  | Liste des super-vilains    |

## Incidents

| Méthode | Route                         | Auth              | Description                                   |
|---------|-------------------------------|--------------------|------------------------------------------------|
| GET     | `/incidents`                  | oui                | Les miens (citoyen) ou tous (héros/admin)       |
| POST    | `/incidents`                  | oui                | Déclarer un incident                            |
| GET     | `/incidents/{id}`              | oui                | Détail                                          |
| PATCH   | `/incidents/{id}/priority`     | héros/admin        | `{ "priority": "haute" }`                       |
| PATCH   | `/incidents/{id}/intervene`    | héros              | Prend l'incident en charge                      |
| PATCH   | `/incidents/{id}/resolve`      | héros (assigné)    | `{ "result": "reussi", "citizens_rescued": 3 }` |
| POST    | `/incidents/{id}/rate`         | citoyen (auteur)   | `{ "note": 5, "comment": "..." }`               |

## Administration

| Méthode | Route                                   | Auth  | Description                 |
|---------|------------------------------------------|-------|-------------------------------|
| PATCH   | `/admin/users/{id}/verify-hero`          | admin | Valide une demande de héros   |

## Exemple

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"identifier":"admin@heros-sos.fr","password":"Admin1234!"}'

curl http://localhost:8080/api/incidents \
  -H "Authorization: Bearer <token>"
```

## Étendre l'API

Chaque route est un fichier simple dans `app/public/api/v1/`, déclaré dans
le routeur `app/public/api/index.php`. Pour ajouter une route, créez un
fichier `v1/ma_route.php` (il a accès à `$pdo`, `$currentUser`, `$segments`,
`json_response()`, `json_error()`, `json_input()`) et ajoutez une ligne au
`match()` du routeur.
