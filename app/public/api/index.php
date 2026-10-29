<?php
// Front controller de l'API Heros-SOS.
// Toutes les requetes vers /api/... arrivent ici (voir .htaccess) et sont
// dispatchees vers la bonne fonction selon la methode HTTP et le chemin.

require_once __DIR__ . '/../../includes/api_bootstrap.php';

$path = trim($_GET['path'] ?? '', '/');
$segments = $path === '' ? [] : explode('/', $path);
$method = $_SERVER['REQUEST_METHOD'];

$currentUser = api_current_user($pdo);

// --- Routage ------------------------------------------------------------
// /api/auth/register            POST
// /api/auth/login               POST
// /api/me                       GET
// /api/heroes                   GET
// /api/heroes/{id}               GET
// /api/villains                 GET
// /api/incidents                GET, POST
// /api/incidents/{id}            GET
// /api/incidents/{id}/priority   PATCH   (hero/admin)
// /api/incidents/{id}/intervene  PATCH   (hero)
// /api/incidents/{id}/resolve    PATCH   (hero)
// /api/incidents/{id}/rate       POST    (citizen)
// /api/admin/users/{id}/verify-hero   PATCH   (admin)

try {
    match (true) {
        $segments === ['auth', 'register'] && $method === 'POST'
            => require __DIR__ . '/v1/auth_register.php',

        $segments === ['auth', 'login'] && $method === 'POST'
            => require __DIR__ . '/v1/auth_login.php',

        $segments === ['me'] && $method === 'GET'
            => json_response(['user' => public_user(api_require_login($currentUser))]),

        $segments === ['heroes'] && $method === 'GET'
            => require __DIR__ . '/v1/heroes_list.php',

        count($segments) === 2 && $segments[0] === 'heroes' && $method === 'GET'
            => require __DIR__ . '/v1/heroes_show.php',

        $segments === ['villains'] && $method === 'GET'
            => require __DIR__ . '/v1/villains_list.php',

        $segments === ['incidents'] && $method === 'GET'
            => require __DIR__ . '/v1/incidents_list.php',

        $segments === ['incidents'] && $method === 'POST'
            => require __DIR__ . '/v1/incidents_create.php',

        count($segments) === 2 && $segments[0] === 'incidents' && $method === 'GET'
            => require __DIR__ . '/v1/incidents_show.php',

        count($segments) === 3 && $segments[0] === 'incidents' && $segments[2] === 'priority' && $method === 'PATCH'
            => require __DIR__ . '/v1/incidents_priority.php',

        count($segments) === 3 && $segments[0] === 'incidents' && $segments[2] === 'intervene' && $method === 'PATCH'
            => require __DIR__ . '/v1/incidents_intervene.php',

        count($segments) === 3 && $segments[0] === 'incidents' && $segments[2] === 'resolve' && $method === 'PATCH'
            => require __DIR__ . '/v1/incidents_resolve.php',

        count($segments) === 3 && $segments[0] === 'incidents' && $segments[2] === 'rate' && $method === 'POST'
            => require __DIR__ . '/v1/incidents_rate.php',

        count($segments) === 4 && $segments[0] === 'admin' && $segments[1] === 'users' && $segments[3] === 'verify-hero' && $method === 'PATCH'
            => require __DIR__ . '/v1/admin_verify_hero.php',

        default => json_error('Route introuvable.', 404),
    };
} catch (Throwable $e) {
    error_log($e->getMessage());
    json_error('Erreur interne du serveur.', 500);
}
