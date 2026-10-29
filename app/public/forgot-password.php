<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// 5.3 : page accessible uniquement si l'utilisateur n'est pas connecté
if ($currentUser) {
    redirect(APP_URL . (is_hero($currentUser) ? '/hero-space.php' : '/account.php'));
}

$pageTitle = 'Mot de passe oublié';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND account_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)')
            ->execute([$user['id'], $token, $expires]);

        $link = APP_URL . '/reset-password.php?token=' . $token;
        send_app_mail($user['email'], 'Réinitialisation de votre mot de passe',
            '<p>Bonjour ' . e(display_name($user)) . ',</p>
             <p>Vous avez demandé la réinitialisation de votre mot de passe. Ce lien est valable 1 heure :</p>
             <p><a href="' . $link . '">Réinitialiser mon mot de passe</a></p>
             <p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet email.</p>'
        );
    }
    // Message volontairement identique, que l'email existe ou non (confidentialité)
    $sent = true;
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Mot de passe oublié</h1>
<p class="subtitle">Indiquez votre adresse email, nous vous enverrons un lien de réinitialisation si elle est connue.</p>

<?php if ($sent): ?>
    <div class="alert alert-success">Si cette adresse existe dans nos services, un email vient de vous être envoyé.</div>
<?php endif; ?>

<form class="card" style="max-width:420px;" method="post" action="<?= APP_URL ?>/forgot-password.php">
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email" required autofocus>
    </div>
    <button type="submit">Envoyer le lien</button>
</form>

<?php require __DIR__ . '/../includes/footer.php'; ?>
