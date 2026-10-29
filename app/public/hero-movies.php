<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_hero($currentUser);

$pageTitle = 'Mes films';
$errors = [];
$heroId = $currentUser['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add_movie') {
        $title = trim($_POST['title'] ?? '');
        $year = trim($_POST['movie_year'] ?? '');
        $imdbId = trim($_POST['imdb_id'] ?? '');

        if ($title === '') {
            $errors[] = 'Le titre du film est obligatoire.';
        } else {
            $pdo->prepare('INSERT INTO hero_movies (hero_id, imdb_id, title, movie_year, status) VALUES (?,?,?,?,"pending")')
                ->execute([$heroId, $imdbId ?: null, $title, $year ?: null]);

            $admins = $pdo->query('SELECT email FROM users WHERE is_admin = 1')->fetchAll();
            foreach ($admins as $a) {
                send_app_mail($a['email'], 'Nouveau film à valider',
                    '<p>' . e($currentUser['pseudo']) . ' souhaite ajouter le film "' . e($title) . '" à sa vidéothèque.</p>'
                );
            }
            flash('success', 'Film ajouté, en attente de validation par un modérateur.');
            redirect(APP_URL . '/hero-movies.php');
        }
    } elseif ($action === 'delete_movie') {
        $movieId = (int) ($_POST['movie_id'] ?? 0);
        $pdo->prepare('DELETE FROM hero_movies WHERE id = ? AND hero_id = ?')->execute([$movieId, $heroId]);
        flash('success', 'Film retiré de votre vidéothèque.');
        redirect(APP_URL . '/hero-movies.php');
    }
}

$movies = $pdo->prepare('SELECT * FROM hero_movies WHERE hero_id = ? ORDER BY created_at DESC');
$movies->execute([$heroId]);
$movies = $movies->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Ma vidéothèque</h1>
<p class="subtitle">Référencez les films Marvel dans lesquels vous apparaissez (intégration API IMDb).
    Chaque ajout est vérifié par un modérateur avant publication.</p>

<?php if ($errors): ?><div class="alert alert-error"><?= e($errors[0]) ?></div><?php endif; ?>

<form class="card" method="post" action="<?= APP_URL ?>/hero-movies.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add_movie">
    <h2 class="mt-0">Ajouter un film</h2>
    <div class="form-row-3">
        <div class="field">
            <label for="title">Titre</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div class="field">
            <label for="movie_year">Année</label>
            <input type="text" id="movie_year" name="movie_year" placeholder="2024">
        </div>
        <div class="field">
            <label for="imdb_id">Identifiant IMDb (facultatif)</label>
            <input type="text" id="imdb_id" name="imdb_id" placeholder="tt0000000">
        </div>
    </div>
    <button type="submit">Ajouter</button>
</form>

<h2>Mes films</h2>
<?php if (!$movies): ?>
    <p class="empty-state">Aucun film référencé pour le moment.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Titre</th><th>Année</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($movies as $m): ?>
            <tr>
                <td><?= e($m['title']) ?></td>
                <td><?= e($m['movie_year'] ?? '-') ?></td>
                <td><?= $m['status'] === 'approved'
                    ? '<span class="badge badge-green">Validé</span>'
                    : '<span class="badge badge-gray">En attente</span>' ?></td>
                <td>
                    <form method="post" action="<?= APP_URL ?>/hero-movies.php" style="display:inline;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_movie">
                        <input type="hidden" name="movie_id" value="<?= (int) $m['id'] ?>">
                        <button type="submit" class="btn-small btn-red">Retirer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
