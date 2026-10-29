<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin($currentUser);

$pageTitle = 'Gestion des super-vilains';
$errors = [];
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $pseudo = trim($_POST['pseudo'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $specialty = trim($_POST['specialty'] ?? '');
        $sector = trim($_POST['sector'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($pseudo === '') {
            $errors[] = 'Le pseudo est obligatoire.';
        } elseif ($id > 0) {
            $pdo->prepare('UPDATE villains SET pseudo=?, first_name=?, last_name=?, specialty=?, sector=?, description=? WHERE id=?')
                ->execute([$pseudo, $firstName ?: null, $lastName ?: null, $specialty ?: null, $sector ?: null, $description ?: null, $id]);
            flash('success', 'Super-vilain modifié.');
            redirect(APP_URL . '/admin/villains.php');
        } else {
            $pdo->prepare('INSERT INTO villains (pseudo, first_name, last_name, specialty, sector, description) VALUES (?,?,?,?,?,?)')
                ->execute([$pseudo, $firstName ?: null, $lastName ?: null, $specialty ?: null, $sector ?: null, $description ?: null]);
            flash('success', 'Super-vilain ajouté.');
            redirect(APP_URL . '/admin/villains.php');
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM villains WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
        flash('success', 'Super-vilain supprimé.');
        redirect(APP_URL . '/admin/villains.php');
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM villains WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $editing = $stmt->fetch();
}

$villains = $pdo->query('SELECT * FROM villains ORDER BY pseudo')->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<h1>Gestion des super-vilains</h1>
<p><a href="<?= APP_URL ?>/admin/index.php">&larr; Retour au dashboard</a></p>

<?php if ($errors): ?><div class="alert alert-error"><?= e($errors[0]) ?></div><?php endif; ?>

<form class="card" method="post" action="<?= APP_URL ?>/admin/villains.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">
    <h2 class="mt-0"><?= $editing ? 'Modifier' : 'Ajouter' ?> un super-vilain</h2>
    <div class="form-row">
        <div class="field">
            <label for="pseudo">Pseudo</label>
            <input type="text" id="pseudo" name="pseudo" value="<?= e($editing['pseudo'] ?? '') ?>" required>
        </div>
        <div class="field">
            <label for="specialty">Spécialité illicite</label>
            <input type="text" id="specialty" name="specialty" value="<?= e($editing['specialty'] ?? '') ?>" placeholder="Vol, Meurtre...">
        </div>
    </div>
    <div class="form-row">
        <div class="field">
            <label for="first_name">Prénom (si connu)</label>
            <input type="text" id="first_name" name="first_name" value="<?= e($editing['first_name'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="last_name">Nom (si connu)</label>
            <input type="text" id="last_name" name="last_name" value="<?= e($editing['last_name'] ?? '') ?>">
        </div>
    </div>
    <div class="field">
        <label for="sector">Secteur principal</label>
        <input type="text" id="sector" name="sector" value="<?= e($editing['sector'] ?? '') ?>">
    </div>
    <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="3"><?= e($editing['description'] ?? '') ?></textarea>
    </div>
    <button type="submit"><?= $editing ? 'Enregistrer' : 'Ajouter' ?></button>
    <?php if ($editing): ?><a href="<?= APP_URL ?>/admin/villains.php" class="btn btn-outline">Annuler</a><?php endif; ?>
</form>

<h2>Liste des super-vilains</h2>
<table>
    <thead><tr><th>Pseudo</th><th>Spécialité</th><th>Secteur</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($villains as $v): ?>
        <tr>
            <td><?= e($v['pseudo']) ?></td>
            <td><?= e($v['specialty'] ?? '-') ?></td>
            <td><?= e($v['sector'] ?? '-') ?></td>
            <td class="actions-row">
                <a class="btn btn-small btn-outline" href="<?= APP_URL ?>/admin/villains.php?edit=<?= (int) $v['id'] ?>">Modifier</a>
                <form method="post" action="<?= APP_URL ?>/admin/villains.php" onsubmit="return confirm('Supprimer ce super-vilain ?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int) $v['id'] ?>">
                    <button type="submit" class="btn-small btn-red">Supprimer</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
