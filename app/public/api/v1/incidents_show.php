<?php
$id = (int) $segments[1];
$user = api_require_login($currentUser);

$stmt = $pdo->prepare(
    "SELECT i.*, c.id AS citizen_id, c.first_name AS citizen_first, c.last_name AS citizen_last,
            h.pseudo AS hero_pseudo, v.pseudo AS villain_pseudo
     FROM incidents i
     JOIN users c ON c.id = i.citizen_id
     LEFT JOIN users h ON h.id = i.hero_id
     LEFT JOIN villains v ON v.id = i.villain_id
     WHERE i.id = ?"
);
$stmt->execute([$id]);
$incident = $stmt->fetch();

if (!$incident) json_error('Incident introuvable.', 404);

$isOwner = (int) $incident['citizen_id'] === (int) $user['id'];
if (!$isOwner && !is_admin($user) && !is_hero($user)) {
    json_error('Accès refusé.', 403);
}

json_response(['data' => $incident]);
