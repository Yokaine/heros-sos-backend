<?php
// Point d'entree commun a toutes les pages publiques :
// session, connexion BDD, fonctions utilitaires, creation du compte admin
// par defaut si la base vient d'etre initialisee.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mail.php';

// Cree le compte administrateur par defaut au tout premier lancement
$stmt = $pdo->query('SELECT COUNT(*) AS c FROM users WHERE is_admin = 1');
if ((int) $stmt->fetch()['c'] === 0) {
    $hash = password_hash('Admin1234!', PASSWORD_DEFAULT);
    $pdo->prepare('INSERT INTO users (civility, first_name, last_name, email, password, is_admin, email_verified_at, account_active)
                    VALUES ("Autre", "Admin", "Web4Heroes", "admin@heros-sos.fr", ?, 1, NOW(), 1)')
        ->execute([$hash]);
}

$currentUser = current_user($pdo);
