<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login($currentUser);

$pageTitle = 'Mon compte';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $phone = trim($_POST['phone'] ?? '');
        $street = trim($_POST['address_street'] ?? '');
        $complement = trim($_POST['address_complement'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');

        $pdo->prepare('UPDATE users SET phone=?, address_street=?, address_complement=?, postal_code=?, city=?, country=? WHERE id=?')
            ->execute([$phone ?: null, $street ?: null, $complement ?: null, $postalCode ?: null, $city ?: null, $country ?: null, $currentUser['id']]);

        flash('success', 'Votre profil a été mis à jour.');
        redirect(APP_URL . '/account.php');

    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['new_password_confirm'] ?? '';

        if (!password_verify($current, $currentUser['password'])) {
            $errors[] = 'Le mot de passe actuel est incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        } elseif ($new !== $confirm) {
            $errors[] = 'Les deux mots de passe ne correspondent pas.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([$hash, $currentUser['id']]);
            flash('success', 'Votre mot de passe a été modifié.');
            redirect(APP_URL . '/account.php');
        }

    } elseif ($action === 'toggle_newsletter') {
        $newValue = (int) $currentUser['newsletter_subscribed'] === 1 ? 0 : 1;
        $pdo->prepare('UPDATE users SET newsletter_subscribed = ? WHERE id = ?')->execute([$newValue, $currentUser['id']]);
        $stmt = $pdo->prepare('SELECT id FROM newsletter_subscribers WHERE email = ?');
        $stmt->execute([$currentUser['email']]);
        if ($row = $stmt->fetch()) {
            $pdo->prepare('UPDATE newsletter_subscribers SET subscribed = ? WHERE id = ?')->execute([$newValue, $row['id']]);
        } elseif ($newValue === 1) {
            $pdo->prepare('INSERT INTO newsletter_subscribers (email, user_id, subscribed) VALUES (?,?,1)')
                ->execute([$currentUser['email'], $currentUser['id']]);
        }
        flash('success', $newValue ? 'Vous êtes inscrit à la newsletter.' : 'Vous êtes désinscrit de la newsletter.');
        redirect(APP_URL . '/account.php');

    } elseif ($action === 'apply_hero') {
        $pseudo = trim($_POST['pseudo'] ?? '');
        if ($pseudo === '') {
            $errors[] = 'Un pseudo est nécessaire pour faire une demande de super-héros.';
        } else {
            $pdo->prepare('UPDATE users SET hero_status = "pending", pseudo = ? WHERE id = ?')
                ->execute([$pseudo, $currentUser['id']]);

            $admins = $pdo->query('SELECT email FROM users WHERE is_admin = 1')->fetchAll();
            foreach ($admins as $admin) {
                send_app_mail($admin['email'], 'Nouvelle demande de statut super-héros',
                    '<p>' . e(display_name($currentUser)) . ' (' . e($currentUser['email']) . ') demande à devenir super-héros sous le pseudo "' . e($pseudo) . '".</p>'
                );
            }
            flash('success', 'Votre demande a été envoyée à l\'administration.');
            redirect(APP_URL . '/account.php');
        }
    }
}

// Rafraichit les donnees apres une eventuelle mise a jour
$currentUser = current_user($pdo);

require __DIR__ . '/../includes/header.php';
?>
<h1>Mon compte</h1>
<p class="subtitle">Rôle : <strong>
    <?= is_admin($currentUser) ? 'Administrateur' : (is_hero($currentUser) ? 'Super-héros' : 'Citoyen') ?>
</strong></p>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul class="mt-0" style="margin-bottom:0;padding-left:18px;">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="two-col">
    <div>
        <div class="card">
            <h2 class="mt-0">Mes informations</h2>
            <p><strong>Nom - Prénom :</strong> <?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></p>
            <?php if ($currentUser['pseudo']): ?><p><strong>Pseudo :</strong> <?= e($currentUser['pseudo']) ?></p><?php endif; ?>
            <p><strong>Date de naissance :</strong> <?= $currentUser['birthdate'] ? (new DateTime($currentUser['birthdate']))->format('d/m/Y') : '-' ?></p>
            <p><strong>Adresse email :</strong> <?= e($currentUser['email']) ?></p>
            <p><strong>Membre depuis le :</strong> <?= format_date($currentUser['created_at']) ?></p>
            <p><strong>Compte vérifié le :</strong> <?= format_date($currentUser['email_verified_at']) ?></p>
        </div>

        <form class="card" method="post" action="<?= APP_URL ?>/account.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_profile">
            <h2 class="mt-0">Modifier mes coordonnées</h2>
            <div class="form-row">
                <div class="field">
                    <label for="phone">Numéro de portable</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($currentUser['phone']) ?>">
                </div>
                <div class="field">
                    <label for="address_street">Rue</label>
                    <input type="text" id="address_street" name="address_street" value="<?= e($currentUser['address_street']) ?>">
                </div>
            </div>
            <div class="field">
                <label for="address_complement">Complément d'adresse</label>
                <input type="text" id="address_complement" name="address_complement" value="<?= e($currentUser['address_complement']) ?>">
            </div>
            <div class="form-row-3">
                <div class="field">
                    <label for="postal_code">Code postal</label>
                    <input type="text" id="postal_code" name="postal_code" value="<?= e($currentUser['postal_code']) ?>">
                </div>
                <div class="field">
                    <label for="city">Ville</label>
                    <input type="text" id="city" name="city" value="<?= e($currentUser['city']) ?>">
                </div>
                <div class="field">
                    <label for="country">Pays</label>
                    <input type="text" id="country" name="country" value="<?= e($currentUser['country']) ?>">
                </div>
            </div>
            <button type="submit">Enregistrer</button>
        </form>

        <form class="card" method="post" action="<?= APP_URL ?>/account.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <h2 class="mt-0">Changer mon mot de passe</h2>
            <div class="field">
                <label for="current_password">Mot de passe actuel</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-row">
                <div class="field">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" minlength="8" required>
                </div>
                <div class="field">
                    <label for="new_password_confirm">Confirmation</label>
                    <input type="password" id="new_password_confirm" name="new_password_confirm" minlength="8" required>
                </div>
            </div>
            <button type="submit">Modifier le mot de passe</button>
        </form>
    </div>

    <div>
        <div class="card">
            <h2 class="mt-0">Newsletter</h2>
            <p>Statut actuel :
                <?= (int) $currentUser['newsletter_subscribed'] === 1
                    ? '<span class="badge badge-green">Inscrit</span>'
                    : '<span class="badge badge-gray">Non inscrit</span>' ?>
            </p>
            <form method="post" action="<?= APP_URL ?>/account.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle_newsletter">
                <button type="submit" class="btn-outline">
                    <?= (int) $currentUser['newsletter_subscribed'] === 1 ? 'Se désinscrire' : 'S\'inscrire à la newsletter' ?>
                </button>
            </form>
        </div>

        <?php if ($currentUser['hero_status'] === 'none' && !is_admin($currentUser)): ?>
            <form class="card" method="post" action="<?= APP_URL ?>/account.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="apply_hero">
                <h2 class="mt-0">Devenir super-héros</h2>
                <p class="muted">Votre demande sera examinée par un modérateur.</p>
                <div class="field">
                    <label for="pseudo">Pseudo</label>
                    <input type="text" id="pseudo" name="pseudo" required>
                </div>
                <button type="submit" class="btn-gold">Envoyer ma demande</button>
            </form>
        <?php elseif ($currentUser['hero_status'] === 'pending'): ?>
            <div class="card">
                <h2 class="mt-0">Statut super-héros</h2>
                <p><span class="badge badge-blue">Demande en attente de vérification</span></p>
            </div>
        <?php elseif ($currentUser['hero_status'] === 'verified'): ?>
            <div class="card">
                <h2 class="mt-0">Statut super-héros</h2>
                <p><span class="badge badge-green">Vérifié</span></p>
                <a class="btn btn-gold" href="<?= APP_URL ?>/hero-space.php">Accéder à mon espace professionnel</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
