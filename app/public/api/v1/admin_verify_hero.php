<?php
$targetId = (int) $segments[2];
api_require_admin($currentUser);

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$targetId]);
$target = $stmt->fetch();
if (!$target) json_error('Utilisateur introuvable.', 404);

$pdo->prepare('UPDATE users SET hero_status = "verified", hero_verified_at = NOW() WHERE id = ?')
    ->execute([$targetId]);

send_app_mail($target['email'], 'Votre statut de super-héros est validé !',
    '<p>Félicitations ' . e($target['pseudo']) . ', votre compte est vérifié.</p>'
);

json_response(['message' => 'Super-héros vérifié.']);
