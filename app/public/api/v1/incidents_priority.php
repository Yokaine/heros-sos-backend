<?php
$id = (int) $segments[1];
$user = api_current_user($pdo);
if (!$user || (!is_admin($user) && !is_hero($user))) {
    json_error('Accès réservé aux super-héros et aux modérateurs.', 403);
}

$input = json_input();
$priority = $input['priority'] ?? '';
if (!in_array($priority, ['basse', 'normale', 'haute'], true)) {
    json_error('priority doit être basse, normale ou haute.');
}

$stmt = $pdo->prepare('UPDATE incidents SET priority = ? WHERE id = ?');
$stmt->execute([$priority, $id]);

json_response(['message' => 'Priorité mise à jour.']);
