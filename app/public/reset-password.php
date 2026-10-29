<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($currentUser) {
    redirect(APP_URL . (is_hero($currentUser) ? '/hero-space.php' : '/account.php'));
}

$pageTitle = 'Réinitialisation du mot de passe';
$token = $_GET['token'] ?? $_POST['token'] ?? '';

$stmt = $pdo->prepare('SELECT pr.*, u.email FROM password_resets pr
                        JOIN users u ON u.id = pr.user_id
                        WHERE pr.token = ? AND pr.expires_at > NOW()');
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    flash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
    redirect(APP_URL . '/forgot-password.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if (strlen($password) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if ($password !== $passwordConfirm) $errors[] = 'Les deux mots de passe ne correspondent pas.';

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $reset['user_id']]);
        $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$reset['user_id']]);
        flash('success', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');
        redirect(APP_URL . '/login.php');
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Nouveau mot de passe</h1>
<p class="subtitle">Choisissez un nouveau mot de passe pour le compte <?= e($reset['email']) ?>.</p>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul class="mt-0" style="margin-bottom:0;padding-left:18px;">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="card" style="max-width:420px;" method="post" action="<?= APP_URL ?>/reset-password.php">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="field">
        <label for="password">Nouveau mot de passe</label>
        <input type="password" id="password" name="password" minlength="8" required>
    </div>
    <div class="field">
        <label for="password_confirm">Confirmation</label>
        <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>
    </div>
    <button type="submit">Valider</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
