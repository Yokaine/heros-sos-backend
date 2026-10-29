<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_hero($currentUser);

$pageTitle = 'Mon profil';
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
        flash('success', 'Profil mis à jour.');
        redirect(APP_URL . '/hero-profile-edit.php');

    } elseif ($action === 'request_email_change') {
        $newEmail = trim($_POST['new_email'] ?? '');
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        } else {
            $admins = $pdo->query('SELECT email FROM users WHERE is_admin = 1')->fetchAll();
            foreach ($admins as $a) {
                send_app_mail($a['email'], 'Demande de changement d\'email',
                    '<p>' . e($currentUser['pseudo']) . ' souhaite remplacer son adresse email ' . e($currentUser['email']) . ' par ' . e($newEmail) . '.</p>
                     <p>Cette modification doit être validée manuellement depuis le dashboard administrateur.</p>'
                );
            }
            flash('success', 'Votre demande de changement d\'email a été transmise à la modération pour confirmation.');
            redirect(APP_URL . '/hero-profile-edit.php');
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Mon profil de super-héros</h1>
<p class="subtitle">Seules les informations suivantes sont modifiables : téléphone, adresse, photo et email (avec confirmation).</p>

<?php if ($errors): ?><div class="alert alert-error"><?= e($errors[0]) ?></div><?php endif; ?>

<div class="two-col">
    <form class="card" method="post" action="<?= APP_URL ?>/hero-profile-edit.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_profile">
        <h2 class="mt-0">Coordonnées</h2>
        <div class="form-row">
            <div class="field">
                <label for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone" value="<?= e($currentUser['phone']) ?>">
            </div>
            <div class="field">
                <label for="address_street">Rue</label>
                <input type="text" id="address_street" name="address_street" value="<?= e($currentUser['address_street']) ?>">
            </div>
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

    <form class="card" method="post" action="<?= APP_URL ?>/hero-profile-edit.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="request_email_change">
        <h2 class="mt-0">Changer d'adresse email</h2>
        <p class="muted">Email actuel : <?= e($currentUser['email']) ?></p>
        <div class="field">
            <label for="new_email">Nouvelle adresse</label>
            <input type="email" id="new_email" name="new_email" required>
        </div>
        <button type="submit" class="btn-outline">Envoyer la demande</button>
    </form>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
