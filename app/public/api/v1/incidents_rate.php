<?php
$id = (int) $segments[1];
$user = api_require_login($currentUser);
$input = json_input();

$note = (int) ($input['note'] ?? 0);
$comment = trim($input['comment'] ?? '');

if ($note < 1 || $note > 5) json_error('note doit être comprise entre 1 et 5.');

$stmt = $pdo->prepare('SELECT * FROM incidents WHERE id = ?');
$stmt->execute([$id]);
$incident = $stmt->fetch();

if (!$incident || (int) $incident['citizen_id'] !== (int) $user['id']) {
    json_error('Vous ne pouvez noter que vos propres incidents.', 403);
}
if (!in_array($incident['status'], ['reussi', 'echoue'], true) || !$incident['hero_id']) {
    json_error('Cet incident ne peut pas encore être noté.', 409);
}

$already = $pdo->prepare('SELECT id FROM ratings WHERE incident_id = ?');
$already->execute([$id]);
if ($already->fetch()) json_error('Cet incident a déjà été noté.', 409);

$pdo->prepare('INSERT INTO ratings (incident_id, citizen_id, hero_id, note, comment) VALUES (?,?,?,?,?)')
    ->execute([$id, $user['id'], $incident['hero_id'], $note, $comment ?: null]);

json_response(['message' => 'Avis enregistré.'], 201);
