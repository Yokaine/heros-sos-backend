<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_hero($currentUser);

$pageTitle = 'Ma réputation';
$heroId = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $ratingId = (int) ($_POST['rating_id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');
    if ($reply !== '') {
        $pdo->prepare('UPDATE ratings SET hero_reply = ? WHERE id = ? AND hero_id = ?')
            ->execute([$reply, $ratingId, $heroId]);
        flash('success', 'Votre réponse a été publiée.');
    }
    redirect(APP_URL . '/hero-reputation.php');
}

$avg = $pdo->prepare('SELECT ROUND(AVG(note),1) AS avg, COUNT(*) AS cnt FROM ratings WHERE hero_id = ?');
$avg->execute([$heroId]);
$avg = $avg->fetch();

$ratings = $pdo->prepare(
    "SELECT r.*, u.first_name, u.last_name FROM ratings r
     JOIN users u ON u.id = r.citizen_id WHERE r.hero_id = ? ORDER BY r.created_at DESC"
);
$ratings->execute([$heroId]);
$ratings = $ratings->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Ma réputation</h1>
<p class="subtitle">Note moyenne : <strong><?= $avg['avg'] ? $avg['avg'] . ' / 5' : 'Aucune note' ?></strong>
    (<?= (int) $avg['cnt'] ?> avis)</p>

<?php if (!$ratings): ?>
    <p class="empty-state">Vous n'avez reçu aucun avis pour le moment.</p>
<?php else: ?>
    <?php foreach ($ratings as $r): ?>
        <div class="card">
            <div class="rating-stars"><?= str_repeat('★', (int) $r['note']) . str_repeat('☆', 5 - (int) $r['note']) ?></div>
            <p><?= e($r['first_name']) ?> <?= e($r['last_name']) ?> &middot; <span class="muted"><?= format_date($r['created_at']) ?></span></p>
            <?php if ($r['comment']): ?><p><?= e($r['comment']) ?></p><?php endif; ?>

            <?php if ($r['hero_reply']): ?>
                <div class="alert alert-info"><strong>Votre réponse :</strong> <?= e($r['hero_reply']) ?></div>
            <?php else: ?>
                <form method="post" action="<?= APP_URL ?>/hero-reputation.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="rating_id" value="<?= (int) $r['id'] ?>">
                    <div class="field">
                        <textarea name="reply" rows="2" placeholder="Répondre à ce commentaire..."></textarea>
                    </div>
                    <button type="submit" class="btn-small btn-outline">Répondre</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
