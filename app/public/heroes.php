<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Super-héros';

$heroes = $pdo->query(
    "SELECT u.*,
        (SELECT ROUND(AVG(r.note),1) FROM ratings r WHERE r.hero_id = u.id) AS avg_rating,
        (SELECT COUNT(*) FROM incidents i WHERE i.hero_id = u.id AND i.status = 'reussi') AS wins
     FROM users u
     WHERE u.hero_status = 'verified' AND u.account_active = 1
     ORDER BY u.pseudo"
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Liste des super-héros</h1>
<p class="subtitle">Tous les super-héros vérifiés par l'administration Heros-SOS.</p>

<?php if (!$heroes): ?>
    <p class="empty-state">Aucun super-héros vérifié pour le moment.</p>
<?php else: ?>
    <table>
        <thead>
            <tr><th>Pseudo</th><th>Nom - Prénom</th><th>Email</th><th>Téléphone</th><th>Note</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($heroes as $h): ?>
            <tr>
                <td><strong><?= e($h['pseudo']) ?></strong></td>
                <td><?= e($h['first_name'] . ' ' . $h['last_name']) ?></td>
                <td><?= e($h['email']) ?></td>
                <td><?= e($h['phone'] ?? '-') ?></td>
                <td><?= $h['avg_rating'] ? e($h['avg_rating']) . ' / 5' : '-' ?></td>
                <td><a class="btn btn-small btn-outline" href="<?= APP_URL ?>/hero-profile.php?id=<?= (int) $h['id'] ?>">Voir le profil</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
