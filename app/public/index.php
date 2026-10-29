<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$pageTitle = 'Accueil';

// Derniers incidents resolus (5.1.1 : banniere/carrousel)
$recentResolved = $pdo->query(
    "SELECT i.*, h.pseudo AS hero_pseudo, h.first_name AS hero_first, h.last_name AS hero_last
     FROM incidents i LEFT JOIN users h ON h.id = i.hero_id
     WHERE i.status = 'reussi' ORDER BY i.updated_at DESC LIMIT 4"
)->fetchAll();

// Quelques super-heros pour rassurer les citoyens
$heroes = $pdo->query(
    "SELECT id, pseudo, first_name, last_name FROM users
     WHERE hero_status = 'verified' AND account_active = 1 ORDER BY RAND() LIMIT 4"
)->fetchAll();

$stats = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM incidents) AS total_incidents,
        (SELECT COUNT(*) FROM incidents WHERE status = 'reussi') AS total_resolved,
        (SELECT COUNT(*) FROM users WHERE hero_status = 'verified') AS total_heroes,
        (SELECT COUNT(*) FROM villains) AS total_villains"
)->fetch();

require __DIR__ . '/../includes/header.php';
?>

<section class="hero-banner">
    <h1>Citoyens et super-héros, unis face aux super-vilains</h1>
    <p>Heros-SOS vous permet de signaler un incident en quelques clics et de mobiliser
        les super-héros les plus proches. Ensemble, protégeons nos villes.</p>
    <?php if (!$currentUser): ?>
        <a class="btn btn-gold" href="<?= APP_URL ?>/register.php">Rejoindre la plateforme</a>
    <?php else: ?>
        <a class="btn btn-gold" href="<?= APP_URL ?>/incidents.php">Déclarer un incident</a>
    <?php endif; ?>
</section>

<div class="card-grid">
    <div class="stat-box">
        <div class="stat-value"><?= (int) $stats['total_incidents'] ?></div>
        <div class="stat-label">Incidents signalés</div>
    </div>
    <div class="stat-box">
        <div class="stat-value"><?= (int) $stats['total_resolved'] ?></div>
        <div class="stat-label">Interventions réussies</div>
    </div>
    <div class="stat-box">
        <div class="stat-value"><?= (int) $stats['total_heroes'] ?></div>
        <div class="stat-label">Super-héros actifs</div>
    </div>
    <div class="stat-box">
        <div class="stat-value"><?= (int) $stats['total_villains'] ?></div>
        <div class="stat-label">Super-vilains recensés</div>
    </div>
</div>

<h2>Dernières interventions réussies</h2>
<?php if (!$recentResolved): ?>
    <p class="empty-state">Aucune intervention terminée pour le moment.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Incident</th><th>Lieu</th><th>Super-héros</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($recentResolved as $i): ?>
            <tr>
                <td><?= e($i['title']) ?></td>
                <td><?= e($i['location']) ?></td>
                <td><?= $i['hero_pseudo'] ? e($i['hero_pseudo']) : e(trim($i['hero_first'].' '.$i['hero_last'])) ?></td>
                <td><?= format_date($i['updated_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Ils protègent déjà la ville</h2>
<?php if (!$heroes): ?>
    <p class="empty-state">Aucun super-héros vérifié pour le moment.</p>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($heroes as $h): ?>
            <div class="person-card">
                <div class="avatar"><?= e(strtoupper(substr(display_name($h), 0, 1))) ?></div>
                <div class="pseudo"><?= e(display_name($h)) ?></div>
                <div class="meta">Super-héros vérifié</div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
