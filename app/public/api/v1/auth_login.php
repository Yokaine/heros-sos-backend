<?php
$input = json_input();
$identifier = trim($input['identifier'] ?? '');
$password = $input['password'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM users WHERE (email = ? OR pseudo = ?) AND account_active = 1');
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    json_error('Identifiants incorrects.', 401);
}
if (!$user['email_verified_at']) {
    json_error('Compte non activé. Vérifiez vos emails.', 403);
}

$token = jwt_encode(['sub' => $user['id']], JWT_SECRET, 60 * 60 * 24 * 7); // 7 jours

json_response([
    'token' => $token,
    'user' => public_user($user),
]);
