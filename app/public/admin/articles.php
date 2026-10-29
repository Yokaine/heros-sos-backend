<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin($currentUser);

$pageTitle = 'Articles du blog';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ($title !== '' && $content !== '') {
            if ($id > 0) {
                $pdo->prepare('UPDATE articles SET title=?, content=? WHERE id=?')->execute([$title, $content, $id]);
                flash('success', 'Article modifié.');
            } else {
                $pdo->prepare('INSERT INTO articles (title, content, author_id) VALUES (?,?,?)')
                    ->execute([$title, $content, $currentUser['id']]);
                flash('success', 'Article publié.');
            }
        } else {
            flash('error', 'Le titre et le contenu sont obligatoires.');
        }
        redirect(APP_URL . '/admin/articles.php');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM articles WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash('success', 'Article supprimé.');
        redirect(APP_URL . '/admin/articles.php');
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}

$articles = $pdo->query('SELECT * FROM articles ORDER BY created_at DESC')->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<h1>Articles du blog</h1>
<p><a href="<?= APP_URL ?>/admin/index.php">&larr; Retour au dashboard</a></p>
<p class="subtitle">Un éditeur de texte enrichi (type TinyMCE) pourra être branché ici ultérieurement ; le champ ci-dessous accepte déjà du contenu libre.</p>

<form class="card" method="post" action="<?= APP_URL ?>/admin/articles.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <h2 class="mt-0"><?= $editing ? 'Modifier' : 'Nouvel' ?> article</h2>
    <div class="field">
        <label for="title">Titre</label>
        <input type="text" id="title" name="title" value="<?= e($editing['title'] ?? '') ?>" required>
    </div>
    <div class="field">
        <label for="content">Contenu</label>
        <textarea id="content" name="content" rows="8" required><?= e($editing['content'] ?? '') ?></textarea>
    </div>
    <button type="submit"><?= $editing ? 'Enregistrer' : 'Publier' ?></button>
    <?php if ($editing): ?><a href="<?= APP_URL ?>/admin/articles.php" class="btn btn-outline">Annuler</a><?php endif; ?>
</form>

<h2>Articles publiés</h2>
<?php if (!$articles): ?>
    <p class="empty-state">Aucun article pour le moment.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Titre</th><th>Publié le</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($articles as $a): ?>
            <tr>
                <td><?= e($a['title']) ?></td>
                <td><?= format_date($a['created_at']) ?></td>
                <td class="actions-row">
                    <a class="btn btn-small btn-outline" href="<?= APP_URL ?>/admin/articles.php?edit=<?= (int) $a['id'] ?>">Modifier</a>
                    <form method="post" action="<?= APP_URL ?>/admin/articles.php" onsubmit="return confirm('Supprimer cet article ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                        <button type="submit" class="btn-small btn-red">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
