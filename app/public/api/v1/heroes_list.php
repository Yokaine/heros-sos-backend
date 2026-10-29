<?php
$heroes = $pdo->query(
    "SELECT id, pseudo, first_name, last_name, email, phone,
        (SELECT ROUND(AVG(note),1) FROM ratings r WHERE r.hero_id = users.id) AS avg_rating
     FROM users WHERE hero_status = 'verified' AND account_active = 1 ORDER BY pseudo"
)->fetchAll();

json_response(['data' => $heroes]);
