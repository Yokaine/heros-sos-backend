<?php
$id = (int) $segments[1];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND hero_status = 'verified'");
$stmt->execute([$id]);
$hero = $stmt->fetch();

if (!$hero) json_error('Super-héros introuvable.', 404);

$ratings = $pdo->prepare('SELECT note, comment, hero_reply, created_at FROM ratings WHERE hero_id = ? ORDER BY created_at DESC');
$ratings->execute([$id]);

$movies = $pdo->prepare("SELECT title, movie_year FROM hero_movies WHERE hero_id = ? AND status = 'approved'");
$movies->execute([$id]);

json_response([
    'data' => public_user($hero),
    'ratings' => $ratings->fetchAll(),
    'movies' => $movies->fetchAll(),
]);
