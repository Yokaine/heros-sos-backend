<?php
// $pageTitle doit être défini par la page avant l'inclusion de ce fichier
$pageTitle = $pageTitle ?? 'Heros-SOS';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - Heros-SOS</title>
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="logo" href="<?= APP_URL ?>/index.php">Heros<span>-SOS</span></a>
        <nav class="main-nav">
            <a href="<?= APP_URL ?>/heroes.php">Super-héros</a>
            <a href="<?= APP_URL ?>/villains.php">Super-vilains</a>
            <?php if ($currentUser): ?>
                <a href="<?= APP_URL ?>/incidents.php">Mes incidents</a>
                <?php if (is_hero($currentUser)): ?>
                    <a href="<?= APP_URL ?>/hero-space.php">Espace pro</a>
                <?php endif; ?>
                <?php if (is_admin($currentUser)): ?>
                    <a href="<?= APP_URL ?>/admin/index.php">Dashboard admin</a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/account.php">Mon compte</a>
                <a href="<?= APP_URL ?>/logout.php" class="nav-logout">Déconnexion</a>
            <?php else: ?>
                <a href="<?= APP_URL ?>/login.php">Connexion</a>
                <a href="<?= APP_URL ?>/register.php" class="nav-cta">Inscription</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="wrap main-content">
    <?php foreach (get_flashes() as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
