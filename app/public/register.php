<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if ($currentUser) {
    redirect(APP_URL . '/account.php');
}

$pageTitle = 'Inscription';
$errors = [];
$old = $_POST ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $civility = $_POST['civility'] ?? 'Autre';
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $birthdate = $_POST['birthdate'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $street = trim($_POST['address_street'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $wantsHero = isset($_POST['wants_hero']);
    $pseudo = trim($_POST['pseudo'] ?? '');

    if ($firstName === '' || $lastName === '') $errors[] = 'Le nom et le prénom sont obligatoires.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'L\'adresse email est invalide.';
    if (strlen($password) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if ($password !== $passwordConfirm) $errors[] = 'Les deux mots de passe ne correspondent pas.';
    if ($wantsHero && $pseudo === '') $errors[] = 'Un pseudo est nécessaire pour une demande de super-héros.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Un compte existe déjà avec cette adresse email.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(24));

        $stmt = $pdo->prepare(
            'INSERT INTO users (civility, first_name, last_name, pseudo, birthdate, email, password, phone,
                address_street, postal_code, city, country, hero_status, activation_token)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([
            $civility, $firstName, $lastName, $wantsHero ? $pseudo : null,
            $birthdate ?: null, $email, $hash, $phone ?: null,
            $street ?: null, $postalCode ?: null, $city ?: null, $country ?: null,
            $wantsHero ? 'pending' : 'none', $token,
        ]);
        $userId = (int) $pdo->lastInsertId();

        // 4.3.1 : email de confirmation d'inscription
        $activationLink = APP_URL . '/activate.php?token=' . $token;
        send_app_mail($email, 'Confirmez votre inscription à Heros-SOS',
            '<p>Bonjour ' . e($firstName) . ',</p>
             <p>Merci de votre inscription sur Heros-SOS. Cliquez sur le lien ci-dessous pour activer votre compte :</p>
             <p><a href="' . $activationLink . '">Activer mon compte</a></p>'
        );

        // Notification aux modérateurs
        $admins = $pdo->query('SELECT email FROM users WHERE is_admin = 1')->fetchAll();
        foreach ($admins as $admin) {
            $extra = $wantsHero ? '<p>Ce compte a également demandé le statut de super-héros.</p>' : '';
            send_app_mail($admin['email'], 'Nouvelle inscription sur Heros-SOS',
                '<p>Un nouvel utilisateur vient de s\'inscrire : ' . e($firstName . ' ' . $lastName) . ' (' . e($email) . ').</p>' . $extra
            );
        }

        flash('success', 'Votre compte a été créé. Vérifiez vos emails pour l\'activer.');
        redirect(APP_URL . '/login.php');
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Créer un compte</h1>
<p class="subtitle">Rejoignez la communauté Heros-SOS en tant que citoyen. Vous pourrez ensuite demander
    la vérification de votre statut de super-héros depuis votre compte.</p>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul class="mt-0" style="margin-bottom:0;padding-left:18px;">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="card" method="post" action="<?= APP_URL ?>/register.php">
    <?= csrf_field() ?>

    <h2 class="mt-0">Informations personnelles</h2>
    <div class="form-row-3">
        <div class="field">
            <label for="civility">Civilité</label>
            <select id="civility" name="civility">
                <option value="M" <?= ($old['civility'] ?? '') === 'M' ? 'selected' : '' ?>>M.</option>
                <option value="Mme" <?= ($old['civility'] ?? '') === 'Mme' ? 'selected' : '' ?>>Mme</option>
                <option value="Autre" <?= ($old['civility'] ?? 'Autre') === 'Autre' ? 'selected' : '' ?>>Autre</option>
            </select>
        </div>
        <div class="field">
            <label for="first_name">Prénom</label>
            <input type="text" id="first_name" name="first_name" value="<?= e($old['first_name'] ?? '') ?>" required>
        </div>
        <div class="field">
            <label for="last_name">Nom</label>
            <input type="text" id="last_name" name="last_name" value="<?= e($old['last_name'] ?? '') ?>" required>
        </div>
    </div>
    <div class="field">
        <label for="birthdate">Date de naissance</label>
        <input type="date" id="birthdate" name="birthdate" value="<?= e($old['birthdate'] ?? '') ?>">
    </div>

    <h2>Informations complémentaires</h2>
    <div class="form-row">
        <div class="field">
            <label for="phone">Numéro de portable</label>
            <input type="tel" id="phone" name="phone" value="<?= e($old['phone'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="address_street">Adresse (rue)</label>
            <input type="text" id="address_street" name="address_street" value="<?= e($old['address_street'] ?? '') ?>">
        </div>
    </div>
    <div class="form-row-3">
        <div class="field">
            <label for="postal_code">Code postal</label>
            <input type="text" id="postal_code" name="postal_code" value="<?= e($old['postal_code'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="city">Ville</label>
            <input type="text" id="city" name="city" value="<?= e($old['city'] ?? '') ?>">
        </div>
        <div class="field">
            <label for="country">Pays</label>
            <input type="text" id="country" name="country" value="<?= e($old['country'] ?? 'France') ?>">
        </div>
    </div>

    <h2>Sécurité</h2>
    <div class="form-row">
        <div class="field">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" required>
        </div>
        <div></div>
        <div class="field">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" minlength="8" required>
        </div>
        <div class="field">
            <label for="password_confirm">Confirmation du mot de passe</label>
            <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>
        </div>
    </div>

    <h2>Statut super-héros (optionnel)</h2>
    <div class="checkbox-row field">
        <input type="checkbox" id="wants_hero" name="wants_hero" value="1" <?= isset($old['wants_hero']) ? 'checked' : '' ?>>
        <label class="mt-0" for="wants_hero" style="margin-bottom:0;">Je souhaite demander le statut de super-héros</label>
    </div>
    <div class="field">
        <label for="pseudo">Pseudo de super-héros</label>
        <input type="text" id="pseudo" name="pseudo" value="<?= e($old['pseudo'] ?? '') ?>" placeholder="Obligatoire si vous cochez la case ci-dessus">
        <p class="muted" style="font-size:0.82rem;">Votre compte devra être vérifié par un modérateur avant d'accéder à l'espace professionnel.</p>
    </div>

    <button type="submit">Créer mon compte</button>
</form>

<p>Déjà inscrit ? <a href="<?= APP_URL ?>/login.php">Connectez-vous</a></p>

<?php require __DIR__ . '/../includes/footer.php'; ?>
