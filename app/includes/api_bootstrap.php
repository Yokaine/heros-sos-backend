<?php
// Point d'entree commun a TOUTES les routes de l'API JSON (/api/*).
// Contrairement a includes/bootstrap.php (utilise par les pages HTML), ici :
//  - pas de session PHP : l'authentification se fait par token JWT (Authorization: Bearer ...)
//  - toute reponse est du JSON
//  - les en-tetes CORS sont ajoutes pour autoriser un frontend separe a appeler l'API

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/jwt.php';

define('JWT_SECRET', getenv('JWT_SECRET') ?: 'change-me-in-production');

// --- CORS -------------------------------------------------------------
// CORS_ALLOWED_ORIGIN peut contenir une liste d'origines separees par des
// virgules, ex: "http://localhost:3000,https://heros-sos-front.vercel.app"
$allowedOrigins = array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGIN') ?: 'http://localhost:3000'));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Le navigateur envoie une requete OPTIONS "preflight" avant toute requete
// "non simple" (avec un header Authorization, un body JSON, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function json_input(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $message, int $status = 400): never
{
    json_response(['error' => $message], $status);
}

/**
 * Lit le token Bearer, le verifie, et renvoie l'utilisateur correspondant
 * (ou null si aucun token valide n'est fourni).
 */
function api_current_user(PDO $pdo): ?array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        return null;
    }

    $payload = jwt_decode($matches[1], JWT_SECRET);
    if (!$payload || empty($payload['sub'])) {
        return null;
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND account_active = 1');
    $stmt->execute([$payload['sub']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function api_require_login(?array $user): array
{
    if (!$user) {
        json_error('Authentification requise.', 401);
    }
    return $user;
}

function api_require_hero(?array $user): array
{
    $user = api_require_login($user);
    if ($user['hero_status'] !== 'verified') {
        json_error('Accès réservé aux super-héros vérifiés.', 403);
    }
    return $user;
}

function api_require_admin(?array $user): array
{
    $user = api_require_login($user);
    if ((int) $user['is_admin'] !== 1) {
        json_error('Accès réservé aux administrateurs.', 403);
    }
    return $user;
}

/** Retire les champs sensibles avant de renvoyer un utilisateur en JSON. */
function public_user(array $user): array
{
    unset($user['password'], $user['activation_token']);
    return $user;
}
