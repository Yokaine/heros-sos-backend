<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin($currentUser);

$pageTitle = 'Gestion des utilisateurs';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $targetStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $targetStmt->execute([$userId]);
    $target = $targetStmt->fetch();

    if ($target) {
        if ($action === 'verify_hero') {
            $pdo->prepare('UPDATE users SET hero_status = "verified", hero_verified_at = NOW() WHERE id = ?')->execute([$userId]);
            send_app_mail($target['email'], 'Votre statut de super-héros est validé !',
                '<p>Félicitations ' . e($target['pseudo']) . ', votre compte a été vérifié. Vous avez maintenant accès à votre espace professionnel.</p>'
            );
            flash('success', 'Super-héros vérifié.');
        } elseif ($action === 'reject_hero') {
            $pdo->prepare('UPDATE users SET hero_status = "none" WHERE id = ?')->execute([$userId]);
            flash('success', 'Demande de super-héros refusée.');
        } elseif ($action === 'deactivate') {
            $pdo->prepare('UPDATE users SET account_active = 0 WHERE id = ?')->execute([$userId]);
            flash('success', 'Compte désactivé.');
        } elseif ($action === 'activate') {
            $pdo->prepare('UPDATE users SET account_active = 1 WHERE id = ?')->execute([$userId]);
            flash('success', 'Compte réactivé.');
        }
    }
    redirect(APP_URL . '/admin/users.php');
}

$pending = $pdo->query("SELECT * FROM users WHERE hero_status = 'pending' ORDER BY created_at")->fetchAll();
$all = $pdo->query("SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC")->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<h1>Gestion des utilisateurs</h1>
<p><a href="<?= APP_URL ?>/admin/index.php">&larr; Retour au dashboard</a></p>

<h2 class="mt-0">Demandes de statut super-héros</h2>
<?php if (!$pending): ?>
    <p class="empty-state">Aucune demande en attente.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Pseudo</th><th>Nom</th><th>Email</th><th>Demandé le</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pending as $u): ?>
            <tr>
                <td><strong><?= e($u['pseudo']) ?></strong></td>
                <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= format_date($u['created_at']) ?></td>
                <td class="actions-row">
                    <form method="post" action="<?= APP_URL ?>/admin/users.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <input type="hidden" name="action" value="verify_hero">
                        <button type="submit" class="btn-small btn-gold">Valider</button>
                    </form>
                    <form method="post" action="<?= APP_URL ?>/admin/users.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <input type="hidden" name="action" value="reject_hero">
                        <button type="submit" class="btn-small btn-red">Refuser</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Tous les utilisateurs</h2>
<table>
    <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($all as $u): ?>
        <tr>
            <td><?= e(display_name($u)) ?></td>
            <td><?= e($u['email']) ?></td>
            <td>
                <?= $u['hero_status'] === 'verified' ? '<span class="badge badge-blue">Super-héros</span>' : '<span class="badge badge-gray">Citoyen</span>' ?>
            </td>
            <td>
                <?= (int) $u['account_active'] === 1
                    ? '<span class="badge badge-green">Actif</span>'
                    : '<span class="badge badge-red">Désactivé</span>' ?>
            </td>
            <td>
                <form method="post" action="<?= APP_URL ?>/admin/users.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                    <input type="hidden" name="action" value="<?= (int) $u['account_active'] === 1 ? 'deactivate' : 'activate' ?>">
                    <button type="submit" class="btn-small <?= (int) $u['account_active'] === 1 ? 'btn-red' : 'btn-outline' ?>">
                        <?= (int) $u['account_active'] === 1 ? 'Désactiver' : 'Réactiver' ?>
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
