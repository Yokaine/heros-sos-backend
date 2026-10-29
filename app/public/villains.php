<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$pageTitle = 'Super-vilains';

$villains = $pdo->query(
    "SELECT v.*,
        (SELECT COUNT(*) FROM incidents i WHERE i.villain_id = v.id) AS incident_count,
        (SELECT COUNT(*) FROM incidents i WHERE i.villain_id = v.id AND i.status = 'reussi') AS captured
     FROM villains v ORDER BY v.pseudo"
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Liste des super-vilains</h1>
<p class="subtitle">Recensement des super-vilains identifiés sur le territoire.</p>

<?php if (!$villains): ?>
    <p class="empty-state">Aucun super-vilain recensé pour le moment.</p>
<?php else: ?>
    <table>
        <thead>
            <tr><th>Pseudo</th><th>Nom - Prénom</th><th>Spécialité</th><th>Secteur</th><th>Statut</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($villains as $v): ?>
            <tr>
                <td><strong><?= e($v['pseudo']) ?></strong></td>
                <td><?= e(trim(($v['first_name'] ?? '') . ' ' . ($v['last_name'] ?? ''))) ?: '-' ?></td>
                <td><?= e($v['specialty'] ?? '-') ?></td>
                <td><?= e($v['sector'] ?? '-') ?></td>
                <td>
                    <?= $v['captured'] > 0
                        ? '<span class="badge badge-green">Appréhendé</span>'
                        : '<span class="badge badge-red">En cavale</span>' ?>
                </td>
                <td><a class="btn btn-small btn-outline" href="<?= APP_URL ?>/villain-profile.php?id=<?= (int) $v['id'] ?>">Voir la fiche</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
