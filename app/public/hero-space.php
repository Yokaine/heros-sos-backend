<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_hero($currentUser);

$pageTitle = 'Espace professionnel';
$heroId = $currentUser['id'];

// 5.8.1 Statistiques globales
$global = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM incidents WHERE hero_id IS NOT NULL) AS total_interventions,
        (SELECT COUNT(*) FROM incidents WHERE status = 'reussi') AS total_rescued_interventions,
        (SELECT COALESCE(SUM(citizens_rescued),0) FROM incidents WHERE status = 'reussi') AS total_citizens_rescued,
        (SELECT COALESCE(SUM(citizens_rescued),0) FROM incidents WHERE status = 'reussi' AND incident_type IN ('Tentative de meurtre','Prise d''otages','Séquestration','Attentat','Meurtre')) AS total_danger_rescued,
        (SELECT COUNT(DISTINCT villain_id) FROM incidents WHERE villain_id IS NOT NULL) AS total_villains_identified,
        (SELECT COUNT(DISTINCT villain_id) FROM incidents WHERE villain_id IS NOT NULL AND status = 'reussi') AS total_villains_jailed"
)->fetch();

// 5.8.2 Statistiques personnelles
$personal = $pdo->prepare(
    "SELECT
        (SELECT COUNT(*) FROM incidents WHERE hero_id = ?) AS my_interventions,
        (SELECT COUNT(DISTINCT villain_id) FROM incidents WHERE hero_id = ? AND status = 'reussi' AND villain_id IS NOT NULL) AS my_villains_jailed,
        (SELECT ROUND(AVG(note),1) FROM ratings WHERE hero_id = ?) AS my_rating,
        (SELECT COUNT(*) FROM hero_movies WHERE hero_id = ? AND status = 'approved') AS my_movies,
        (SELECT COALESCE(SUM(citizens_rescued),0) FROM incidents WHERE hero_id = ? AND status = 'reussi') AS my_citizens_rescued"
);
$personal->execute([$heroId, $heroId, $heroId, $heroId, $heroId]);
$personal = $personal->fetch();

$worstEnemy = $pdo->prepare(
    "SELECT v.pseudo, COUNT(*) AS c FROM incidents i
     JOIN villains v ON v.id = i.villain_id
     WHERE i.hero_id = ? GROUP BY v.id ORDER BY c DESC LIMIT 1"
);
$worstEnemy->execute([$heroId]);
$worstEnemy = $worstEnemy->fetch();

$toHandle = $pdo->query(
    "SELECT i.*, v.pseudo AS v_pseudo FROM incidents i
     LEFT JOIN villains v ON v.id = i.villain_id
     WHERE i.status = 'signale'
     ORDER BY FIELD(i.priority,'haute','normale','basse'), i.created_at ASC LIMIT 20"
)->fetchAll();

$myOngoing = $pdo->prepare(
    "SELECT * FROM incidents WHERE hero_id = ? AND status = 'intervention' ORDER BY created_at DESC"
);
$myOngoing->execute([$heroId]);
$myOngoing = $myOngoing->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Espace professionnel de <?= e($currentUser['pseudo']) ?></h1>
<p class="subtitle">
    <a href="<?= APP_URL ?>/hero-profile-edit.php">Mon profil</a> &middot;
    <a href="<?= APP_URL ?>/hero-movies.php">Mes films</a> &middot;
    <a href="<?= APP_URL ?>/hero-reputation.php">Ma réputation</a>
</p>

<h2 class="mt-0">Statistiques globales</h2>
<div class="card-grid">
    <div class="stat-box"><div class="stat-value"><?= (int) $global['total_interventions'] ?></div><div class="stat-label">Interventions au total</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $global['total_citizens_rescued'] ?></div><div class="stat-label">Citoyens secourus</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $global['total_danger_rescued'] ?></div><div class="stat-label">Citoyens en danger secourus</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $global['total_villains_identified'] ?></div><div class="stat-label">Super-vilains identifiés</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $global['total_villains_jailed'] ?></div><div class="stat-label">Super-vilains mis en prison</div></div>
</div>

<h2>Mes statistiques personnelles</h2>
<div class="card-grid">
    <div class="stat-box"><div class="stat-value"><?= (int) $personal['my_interventions'] ?></div><div class="stat-label">Mes interventions</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $personal['my_villains_jailed'] ?></div><div class="stat-label">Super-vilains mis en prison</div></div>
    <div class="stat-box"><div class="stat-value"><?= $personal['my_rating'] ? $personal['my_rating'] . ' / 5' : '-' ?></div><div class="stat-label">Réputation</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $personal['my_movies'] ?></div><div class="stat-label">Films où j'apparais</div></div>
    <div class="stat-box"><div class="stat-value"><?= (int) $personal['my_citizens_rescued'] ?></div><div class="stat-label">Citoyens secourus</div></div>
    <div class="stat-box"><div class="stat-value"><?= $worstEnemy ? e($worstEnemy['pseudo']) : '-' ?></div><div class="stat-label">Pire ennemi</div></div>
</div>

<h2>Mes interventions en cours</h2>
<?php if (!$myOngoing): ?>
    <p class="empty-state">Aucune intervention en cours.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Titre</th><th>Lieu</th><th>Priorité</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($myOngoing as $i): ?>
            <tr>
                <td><?= e($i['title']) ?></td>
                <td><?= e($i['location']) ?></td>
                <td><span class="<?= priority_class($i['priority']) ?>"><?= priority_label($i['priority']) ?></span></td>
                <td><a class="btn btn-small btn-outline" href="<?= APP_URL ?>/incident.php?id=<?= (int) $i['id'] ?>">Gérer</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Incidents signalés à traiter</h2>
<?php if (!$toHandle): ?>
    <p class="empty-state">Aucun nouveau signalement pour le moment.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Titre</th><th>Lieu</th><th>Super-vilain</th><th>Priorité</th><th>Signalé le</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($toHandle as $i): ?>
            <tr>
                <td><?= e($i['title']) ?></td>
                <td><?= e($i['location']) ?></td>
                <td><?= $i['v_pseudo'] ? e($i['v_pseudo']) : '-' ?></td>
                <td><span class="<?= priority_class($i['priority']) ?>"><?= priority_label($i['priority']) ?></span></td>
                <td><?= format_date($i['created_at']) ?></td>
                <td><a class="btn btn-small btn-gold" href="<?= APP_URL ?>/incident.php?id=<?= (int) $i['id'] ?>">Voir</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
