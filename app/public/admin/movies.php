<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin($currentUser);

$pageTitle = 'Validation des films';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $movieId = (int) ($_POST['movie_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'approve') {
        $pdo->prepare('UPDATE hero_movies SET status = "approved" WHERE id = ?')->execute([$movieId]);
        flash('success', 'Film validé.');
    } elseif ($action === 'reject') {
        $pdo->prepare('DELETE FROM hero_movies WHERE id = ?')->execute([$movieId]);
        flash('success', 'Film refusé et supprimé.');
    }
    redirect(APP_URL . '/admin/movies.php');
}

$pending = $pdo->query(
    "SELECT m.*, u.pseudo FROM hero_movies m JOIN users u ON u.id = m.hero_id
     WHERE m.status = 'pending' ORDER BY m.created_at"
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<h1>Validation des vidéothèques</h1>
<p><a href="<?= APP_URL ?>/admin/index.php">&larr; Retour au dashboard</a></p>

<?php if (!$pending): ?>
    <p class="empty-state">Aucun film en attente de validation.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Super-héros</th><th>Film</th><th>Année</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pending as $m): ?>
            <tr>
                <td><?= e($m['pseudo']) ?></td>
                <td><?= e($m['title']) ?></td>
                <td><?= e($m['movie_year'] ?? '-') ?></td>
                <td class="actions-row">
                    <form method="post" action="<?= APP_URL ?>/admin/movies.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="movie_id" value="<?= (int) $m['id'] ?>">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn-small btn-gold">Valider</button>
                    </form>
                    <form method="post" action="<?= APP_URL ?>/admin/movies.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="movie_id" value="<?= (int) $m['id'] ?>">
                        <input type="hidden" name="action" value="reject">
                        <button type="submit" class="btn-small btn-red">Refuser</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
