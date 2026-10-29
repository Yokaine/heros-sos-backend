<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND hero_status = 'verified'");
$stmt->execute([$id]);
$hero = $stmt->fetch();

if (!$hero) {
    flash('error', "Ce super-héros n'existe pas ou n'est pas vérifié.");
    redirect(APP_URL . '/heroes.php');
}

$pageTitle = $hero['pseudo'];

$ratings = $pdo->prepare(
    "SELECT r.*, u.first_name, u.last_name FROM ratings r
     JOIN users u ON u.id = r.citizen_id WHERE r.hero_id = ? ORDER BY r.created_at DESC LIMIT 10"
);
$ratings->execute([$id]);
$ratings = $ratings->fetchAll();

$avg = $pdo->prepare('SELECT ROUND(AVG(note),1) AS avg, COUNT(*) AS cnt FROM ratings WHERE hero_id = ?');
$avg->execute([$id]);
$avg = $avg->fetch();

$movies = $pdo->prepare("SELECT * FROM hero_movies WHERE hero_id = ? AND status = 'approved' ORDER BY movie_year DESC");
$movies->execute([$id]);
$movies = $movies->fetchAll();

$wins = $pdo->prepare("SELECT COUNT(*) AS c FROM incidents WHERE hero_id = ? AND status = 'reussi'");
$wins->execute([$id]);
$wins = (int) $wins->fetch()['c'];

require __DIR__ . '/../includes/header.php';
?>
<h1><?= e($hero['pseudo']) ?></h1>
<p class="subtitle"><?= e($hero['first_name'] . ' ' . $hero['last_name']) ?> &middot; Super-héros vérifié</p>

<div class="card-grid">
    <div class="stat-box">
        <div class="stat-value"><?= $wins ?></div>
        <div class="stat-label">Interventions réussies</div>
    </div>
    <div class="stat-box">
        <div class="stat-value"><?= $avg['avg'] ? $avg['avg'] . ' / 5' : '-' ?></div>
        <div class="stat-label">Note moyenne (<?= (int) $avg['cnt'] ?> avis)</div>
    </div>
    <div class="stat-box">
        <div class="stat-value"><?= count($movies) ?></div>
        <div class="stat-label">Apparitions au cinéma</div>
    </div>
</div>

<h2>Vidéothèque</h2>
<?php if (!$movies): ?>
    <p class="empty-state">Aucun film référencé pour ce super-héros.</p>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($movies as $m): ?>
            <div class="card">
                <strong><?= e($m['title']) ?></strong><br>
                <span class="muted"><?= e($m['movie_year'] ?? '') ?></span>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h2>Avis des citoyens</h2>
<?php if (!$ratings): ?>
    <p class="empty-state">Aucun avis pour le moment.</p>
<?php else: ?>
    <?php foreach ($ratings as $r): ?>
        <div class="card">
            <div class="rating-stars"><?= str_repeat('★', (int) $r['note']) . str_repeat('☆', 5 - (int) $r['note']) ?></div>
            <p><?= e($r['first_name']) ?> <?= e($r['last_name']) ?> &middot; <span class="muted"><?= format_date($r['created_at']) ?></span></p>
            <?php if ($r['comment']): ?><p><?= e($r['comment']) ?></p><?php endif; ?>
            <?php if ($r['hero_reply']): ?>
                <div class="alert alert-info"><strong><?= e($hero['pseudo']) ?> répond :</strong> <?= e($r['hero_reply']) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
