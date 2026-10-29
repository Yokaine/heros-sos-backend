<?php
// Configuration de l'application - lit les variables d'environnement
// definies dans docker-compose.yml

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'herosos');
define('DB_USER', getenv('DB_USER') ?: 'herosos');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'herosos');

define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080');

define('MAIL_FROM', 'no-reply@heros-sos.fr');
define('MAIL_FROM_NAME', 'Heros-SOS');

if (APP_ENV === 'dev') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
