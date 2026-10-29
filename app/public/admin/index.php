<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin($currentUser);

$pageTitle = 'Dashboard administrateur';

$stats = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM users WHERE is_admin = 0) AS total_users,
        (SELECT COUNT(*) FROM users WHERE hero_status = 'verified') AS total_heroes,
        (SELECT COUNT(*) FROM users WHERE hero_status = 'pending') AS pending_heroes,
        (SELECT COUNT(*) FROM incidents WHERE status IN ('signale','intervention')) AS active_incidents,
        (SELECT COUNT(*) FROM hero_movies WHERE status = 'pending') AS pending_movies,
        (SELECT COUNT(*) FROM newsletter_subscribers WHERE subscribed = 1) AS newsletter_subs"
)->fetch();

require __DIR__ . '/../../includes/header.php';
?>
<h1>Dashboard administrateur</h1>
<p class="subtitle">Supervision des citoyens, des super-héros et des incidents de la plateforme.</p>

<div class="card-grid">
    <div class="stat-box"><div class="stat-value"><?= (int) $stats['total_users'] ?></div><div class="stat-label">Utilisateurs</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $stats['total_heroes'] ?></div><div class="stat-label">Super-héros vérifiés</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $stats['pending_heroes'] ?></div><div class="stat-label">Demandes en attente</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $stats['active_incidents'] ?></div><div class="stat-label">Incidents actifs</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $stats['pending_movies'] ?></div><div class="stat-label">Films à valider</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $stats['newsletter_subs'] ?></div><div class="stat-label">Abonnés newsletter</div></div>
</div>

<h2>Outils de modération</h2>
<div class="card-grid">
    <a class="card" href="<?= APP_URL ?>/admin/users.php">
        <h2 class="mt-0" style="margin-top:0;">Utilisateurs</h2>
        <p class="muted">Vérifier les demandes de super-héros, désactiver des comptes.</p>
    </a>
    <a class="card" href="<?= APP_URL ?>/admin/incidents.php">
        <h2 class="mt-0" style="margin-top:0;">Incidents</h2>
        <p class="muted">Prioriser, modifier ou supprimer les incidents signalés.</p>
    </a>
    <a class="card" href="<?= APP_URL ?>/admin/villains.php">
        <h2 class="mt-0" style="margin-top:0;">Super-vilains</h2>
        <p class="muted">Gérer la liste des super-vilains recensés.</p>
    </a>
    <a class="card" href="<?= APP_URL ?>/admin/movies.php">
        <h2 class="mt-0" style="margin-top:0;">Vidéothèques</h2>
        <p class="muted">Valider les films ajoutés par les super-héros.</p>
    </a>
    <a class="card" href="<?= APP_URL ?>/admin/newsletter.php">
        <h2 class="mt-0" style="margin-top:0;">Newsletter</h2>
        <p class="muted">Envoyer la newsletter aux utilisateurs inscrits.</p>
    </a>
    <a class="card" href="<?= APP_URL ?>/admin/articles.php">
        <h2 class="mt-0" style="margin-top:0;">Articles du blog</h2>
        <p class="muted">Créer, modifier ou supprimer des articles.</p>
    </a>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
