<?php
$input = json_input();

$firstName = trim($input['first_name'] ?? '');
$lastName = trim($input['last_name'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$wantsHero = !empty($input['wants_hero']);
$pseudo = trim($input['pseudo'] ?? '');

if ($firstName === '' || $lastName === '') json_error('Le nom et le prénom sont obligatoires.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_error('Adresse email invalide.');
if (strlen($password) < 8) json_error('Le mot de passe doit contenir au moins 8 caractères.');
if ($wantsHero && $pseudo === '') json_error('Un pseudo est nécessaire pour une demande de super-héros.');

$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_error('Un compte existe déjà avec cette adresse email.', 409);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(24));

$stmt = $pdo->prepare(
    'INSERT INTO users (civility, first_name, last_name, pseudo, email, password, hero_status, activation_token)
     VALUES (?,?,?,?,?,?,?,?)'
);
$stmt->execute([
    $input['civility'] ?? 'Autre', $firstName, $lastName, $wantsHero ? $pseudo : null,
    $email, $hash, $wantsHero ? 'pending' : 'none', $token,
]);
$userId = (int) $pdo->lastInsertId();

send_app_mail($email, 'Confirmez votre inscription à Heros-SOS',
    '<p>Bonjour ' . e($firstName) . ', activez votre compte : '
    . '<a href="' . APP_URL . '/activate.php?token=' . $token . '">cliquez ici</a>.</p>'
);

json_response(['message' => 'Compte créé. Vérifiez vos emails pour l\'activer.', 'id' => $userId], 201);
