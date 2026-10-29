<?php
// Fonctions utilitaires communes a toute l'application

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function check_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Jeton de sécurité invalide. Merci de recharger la page et réessayer.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return $flashes;
}

function current_user(PDO $pdo): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND account_active = 1');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $user;
}

function require_login(?array $user): void
{
    if (!$user) {
        flash('error', 'Vous devez être connecté pour accéder à cette page.');
        redirect(APP_URL . '/login.php');
    }
}

function require_hero(?array $user): void
{
    require_login($user);
    if ($user['hero_status'] !== 'verified') {
        flash('error', "Cet espace est réservé aux super-héros vérifiés par l'administration.");
        redirect(APP_URL . '/account.php');
    }
}

function require_admin(?array $user): void
{
    require_login($user);
    if ((int) $user['is_admin'] !== 1) {
        flash('error', 'Accès réservé aux modérateurs.');
        redirect(APP_URL . '/index.php');
    }
}

function is_hero(?array $user): bool
{
    return $user && $user['hero_status'] === 'verified';
}

function is_admin(?array $user): bool
{
    return $user && (int) $user['is_admin'] === 1;
}

function priority_label(string $priority): string
{
    return match ($priority) {
        'haute' => 'Haute',
        'basse' => 'Basse',
        default => 'Normale',
    };
}

function status_label(string $status): string
{
    return match ($status) {
        'signale' => 'Signalé',
        'intervention' => 'Intervention en cours',
        'reussi' => 'Intervention réussie',
        'echoue' => 'Intervention échouée',
        default => $status,
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'signale' => 'badge badge-gray',
        'intervention' => 'badge badge-blue',
        'reussi' => 'badge badge-green',
        'echoue' => 'badge badge-red',
        default => 'badge',
    };
}

function priority_class(string $priority): string
{
    return match ($priority) {
        'haute' => 'badge badge-red',
        'basse' => 'badge badge-gray',
        default => 'badge badge-blue',
    };
}

function format_date(?string $datetime): string
{
    if (!$datetime) {
        return '-';
    }
    $date = new DateTime($datetime);
    return $date->format('d/m/Y à H:i');
}

function display_name(array $user): string
{
    if (!empty($user['pseudo'])) {
        return $user['pseudo'];
    }
    return trim($user['first_name'] . ' ' . $user['last_name']);
}
