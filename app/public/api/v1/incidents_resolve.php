<?php
$id = (int) $segments[1];
$user = api_require_hero($currentUser);
$input = json_input();

$result = $input['result'] ?? '';
$rescued = (int) ($input['citizens_rescued'] ?? 0);

if (!in_array($result, ['reussi', 'echoue'], true)) {
    json_error('result doit être "reussi" ou "echoue".');
}

$stmt = $pdo->prepare('SELECT * FROM incidents WHERE id = ?');
$stmt->execute([$id]);
$incident = $stmt->fetch();
if (!$incident) json_error('Incident introuvable.', 404);
if ((int) $incident['hero_id'] !== (int) $user['id']) {
    json_error('Seul le super-héros intervenant peut clôturer cet incident.', 403);
}

$pdo->prepare('UPDATE incidents SET status = ?, citizens_rescued = ? WHERE id = ?')
    ->execute([$result, $rescued, $id]);

$citizenStmt = $pdo->prepare('SELECT email, first_name FROM users WHERE id = ?');
$citizenStmt->execute([$incident['citizen_id']]);
if ($citizen = $citizenStmt->fetch()) {
    send_app_mail($citizen['email'], 'Votre incident a été traité',
        '<p>Statut final : ' . status_label($result) . '. Vous pouvez désormais noter le super-héros intervenu.</p>'
    );
}

json_response(['message' => 'Incident clôturé.']);
