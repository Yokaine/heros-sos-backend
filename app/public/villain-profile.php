<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM villains WHERE id = ?');
$stmt->execute([$id]);
$villain = $stmt->fetch();

if (!$villain) {
    flash('error', "Ce super-vilain n'existe pas.");
    redirect(APP_URL . '/villains.php');
}

$pageTitle = $villain['pseudo'];

$incidents = $pdo->prepare("SELECT * FROM incidents WHERE villain_id = ? ORDER BY created_at DESC LIMIT 10");
$incidents->execute([$id]);
$incidents = $incidents->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1><?= e($villain['pseudo']) ?></h1>
<p class="subtitle"><?= e(trim(($villain['first_name'] ?? '') . ' ' . ($villain['last_name'] ?? ''))) ?: 'Identité réelle inconnue' ?></p>

<div class="card">
    <p><strong>Spécialité :</strong> <?= e($villain['specialty'] ?? '-') ?></p>
    <p><strong>Secteur principal :</strong> <?= e($villain['sector'] ?? '-') ?></p>
    <?php if ($villain['description']): ?><p><?= e($villain['description']) ?></p><?php endif; ?>
</div>

<h2>Incidents liés</h2>
<?php if (!$incidents): ?>
    <p class="empty-state">Aucun incident enregistré pour ce super-vilain.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Incident</th><th>Lieu</th><th>Statut</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($incidents as $i): ?>
            <tr>
                <td><?= e($i['title']) ?></td>
                <td><?= e($i['location']) ?></td>
                <td><span class="<?= status_class($i['status']) ?>"><?= status_label($i['status']) ?></span></td>
                <td><?= format_date($i['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
