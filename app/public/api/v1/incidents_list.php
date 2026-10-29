<?php
$user = api_require_login($currentUser);

if (is_admin($user) || is_hero($user)) {
    $incidents = $pdo->query(
        "SELECT i.*, c.first_name AS citizen_first, c.last_name AS citizen_last, h.pseudo AS hero_pseudo
         FROM incidents i
         JOIN users c ON c.id = i.citizen_id
         LEFT JOIN users h ON h.id = i.hero_id
         ORDER BY FIELD(i.status,'signale','intervention','reussi','echoue'), i.created_at DESC"
    )->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT * FROM incidents WHERE citizen_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
    $incidents = $stmt->fetchAll();
}

json_response(['data' => $incidents]);
