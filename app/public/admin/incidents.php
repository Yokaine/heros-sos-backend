<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin($currentUser);

$pageTitle = 'Gestion des incidents';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $incidentId = (int) ($_POST['incident_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'set_priority') {
        $priority = $_POST['priority'] ?? 'normale';
        if (in_array($priority, ['basse', 'normale', 'haute'], true)) {
            $pdo->prepare('UPDATE incidents SET priority = ? WHERE id = ?')->execute([$priority, $incidentId]);
            flash('success', 'Priorité mise à jour.');
        }
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM incidents WHERE id = ?')->execute([$incidentId]);
        flash('success', 'Incident supprimé.');
    }
    redirect(APP_URL . '/admin/incidents.php');
}

$incidents = $pdo->query(
    "SELECT i.*, c.first_name, c.last_name, h.pseudo AS h_pseudo FROM incidents i
     JOIN users c ON c.id = i.citizen_id
     LEFT JOIN users h ON h.id = i.hero_id
     ORDER BY FIELD(i.status,'signale','intervention','reussi','echoue'), i.created_at DESC"
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<h1>Gestion des incidents</h1>
<p><a href="<?= APP_URL ?>/admin/index.php">&larr; Retour au dashboard</a></p>

<?php if (!$incidents): ?>
    <p class="empty-state">Aucun incident enregistré.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Titre</th><th>Citoyen</th><th>Statut</th><th>Super-héros</th><th>Priorité</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($incidents as $i): ?>
            <tr>
                <td><a href="<?= APP_URL ?>/incident.php?id=<?= (int) $i['id'] ?>"><?= e($i['title']) ?></a></td>
                <td><?= e($i['first_name'] . ' ' . $i['last_name']) ?></td>
                <td><span class="<?= status_class($i['status']) ?>"><?= status_label($i['status']) ?></span></td>
                <td><?= $i['h_pseudo'] ? e($i['h_pseudo']) : '-' ?></td>
                <td>
                    <form method="post" action="<?= APP_URL ?>/admin/incidents.php" style="display:inline-flex;gap:6px;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="incident_id" value="<?= (int) $i['id'] ?>">
                        <input type="hidden" name="action" value="set_priority">
                        <select name="priority" onchange="this.form.submit()">
                            <option value="basse" <?= $i['priority'] === 'basse' ? 'selected' : '' ?>>Basse</option>
                            <option value="normale" <?= $i['priority'] === 'normale' ? 'selected' : '' ?>>Normale</option>
                            <option value="haute" <?= $i['priority'] === 'haute' ? 'selected' : '' ?>>Haute</option>
                        </select>
                    </form>
                </td>
                <td>
                    <form method="post" action="<?= APP_URL ?>/admin/incidents.php"
                          onsubmit="return confirm('Supprimer cet incident ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="incident_id" value="<?= (int) $i['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn-small btn-red">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
