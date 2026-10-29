<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($currentUser) {
    redirect(APP_URL . '/account.php');
}

$pageTitle = 'Connexion';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE (email = ? OR pseudo = ?) AND account_active = 1');
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        $error = 'Identifiants incorrects.';
    } elseif (!$user['email_verified_at']) {
        $error = 'Votre compte n\'est pas encore activé. Vérifiez vos emails.';
    } else {
        $_SESSION['user_id'] = $user['id'];
        flash('success', 'Bienvenue, ' . display_name($user) . ' !');
        redirect(APP_URL . '/account.php');
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Connexion</h1>
<p class="subtitle">Connectez-vous avec votre adresse email, ou votre pseudo si vous êtes super-héros.</p>

<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

<form class="card" style="max-width:420px;" method="post" action="<?= APP_URL ?>/login.php">
    <?= csrf_field() ?>
    <div class="field">
        <label for="identifier">Adresse email / Pseudo</label>
        <input type="text" id="identifier" name="identifier" required autofocus>
    </div>
    <div class="field">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required>
    </div>
    <button type="submit">Se connecter</button>
    <p style="margin-top:14px;">
        <a href="<?= APP_URL ?>/forgot-password.php">Mot de passe oublié ?</a>
    </p>
</form>

<p>Pas encore de compte ? <a href="<?= APP_URL ?>/register.php">Inscrivez-vous</a></p>

<?php require __DIR__ . '/../includes/footer.php'; ?>
