<?php
$id = (int) $segments[1];
$user = api_require_hero($currentUser);

$stmt = $pdo->prepare('SELECT * FROM incidents WHERE id = ?');
$stmt->execute([$id]);
$incident = $stmt->fetch();
if (!$incident) json_error('Incident introuvable.', 404);

if ($incident['status'] !== 'signale') {
    json_error('Cet incident est déjà pris en charge.', 409);
}

$pdo->prepare('UPDATE incidents SET status = "intervention", hero_id = ? WHERE id = ?')
    ->execute([$user['id'], $id]);

json_response(['message' => 'Intervention démarrée.']);
