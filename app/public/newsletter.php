<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(APP_URL . '/index.php');
}
check_csrf();

$email = trim($_POST['email'] ?? ($currentUser['email'] ?? ''));
$referer = $_SERVER['HTTP_REFERER'] ?? (APP_URL . '/index.php');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    flash('error', 'Adresse email invalide.');
    redirect($referer);
}

$stmt = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE email = ?');
$stmt->execute([$email]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare('UPDATE newsletter_subscribers SET subscribed = 1 WHERE id = ?')->execute([$existing['id']]);
} else {
    $pdo->prepare('INSERT INTO newsletter_subscribers (email, user_id, subscribed) VALUES (?,?,1)')
        ->execute([$email, $currentUser['id'] ?? null]);
}

if ($currentUser && strcasecmp($currentUser['email'], $email) === 0) {
    $pdo->prepare('UPDATE users SET newsletter_subscribed = 1 WHERE id = ?')->execute([$currentUser['id']]);
}

// L'inscription anonyme est "mémorisée" par un cookie, comme demandé dans le cahier des charges (4.2)
if (!$currentUser) {
    setcookie('hs_newsletter', $email, time() + 60 * 60 * 24 * 365, '/');
}

flash('success', 'Merci, votre inscription à la newsletter est confirmée.');
redirect($referer);
