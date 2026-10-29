<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Activation du compte';

$token = $_GET['token'] ?? '';
if ($token) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE activation_token = ? AND email_verified_at IS NULL');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if ($user) {
        $pdo->prepare('UPDATE users SET email_verified_at = NOW(), activation_token = NULL WHERE id = ?')
            ->execute([$user['id']]);
        flash('success', 'Votre compte est activé. Vous pouvez maintenant vous connecter.');
        redirect(APP_URL . '/login.php');
    }
}

flash('error', 'Ce lien d\'activation est invalide ou a déjà été utilisé.');
redirect(APP_URL . '/login.php');
