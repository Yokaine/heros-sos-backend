<?php
require_once __DIR__ . '/config.php';

// Quelques tentatives de connexion : au tout premier "docker compose up",
// le conteneur MySQL peut mettre quelques secondes de plus à être pret
// que le conteneur web.
$pdo = null;
$attempts = 0;
$lastError = null;

while ($attempts < 10 && !$pdo) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        $lastError = $e;
        $attempts++;
        sleep(2);
    }
}

if (!$pdo) {
    http_response_code(500);
    die('Connexion à la base de données impossible après plusieurs tentatives. Vérifiez que le conteneur "db" est démarré.');
}
