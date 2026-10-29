<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login($currentUser);

$pageTitle = 'Mes incidents';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $title = trim($_POST['title'] ?? '');
    $incidentType = trim($_POST['incident_type'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $villainId = $_POST['villain_id'] !== '' ? (int) $_POST['villain_id'] : null;

    if ($title === '') $errors[] = 'Le titre est obligatoire.';
    if ($incidentType === '') $errors[] = 'Le type d\'incident est obligatoire.';
    if ($location === '') $errors[] = 'Le lieu est obligatoire.';
    if ($description === '') $errors[] = 'La description est obligatoire.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO incidents (citizen_id, title, incident_type, location, description, villain_id, status, priority)
             VALUES (?,?,?,?,?,?,"signale","normale")'
        );
        $stmt->execute([$currentUser['id'], $title, $incidentType, $location, $description, $villainId]);
        $incidentId = (int) $pdo->lastInsertId();

        // 4.3.3 : notification aux moderateurs et aux super-heros
        $recipients = $pdo->query("SELECT email FROM users WHERE is_admin = 1 OR hero_status = 'verified'")->fetchAll();
        foreach ($recipients as $r) {
            send_app_mail($r['email'], 'Nouvel incident signalé : ' . $title,
                '<p>Un nouvel incident vient d\'être signalé par un citoyen.</p>
                 <p><strong>Lieu :</strong> ' . e($location) . '<br><strong>Type :</strong> ' . e($incidentType) . '</p>
                 <p><a href="' . APP_URL . '/incident.php?id=' . $incidentId . '">Voir les détails</a></p>'
            );
        }

        flash('success', 'Votre incident a été signalé. Les super-héros ont été prévenus.');
        redirect(APP_URL . '/incidents.php');
    }
}

$myIncidents = $pdo->prepare('SELECT * FROM incidents WHERE citizen_id = ? ORDER BY created_at DESC');
$myIncidents->execute([$currentUser['id']]);
$myIncidents = $myIncidents->fetchAll();

$ongoing = $pdo->query(
    "SELECT i.*, c.first_name, c.last_name FROM incidents i
     JOIN users c ON c.id = i.citizen_id
     WHERE i.status IN ('signale','intervention') ORDER BY i.created_at DESC LIMIT 15"
)->fetchAll();

$villains = $pdo->query('SELECT id, pseudo FROM villains ORDER BY pseudo')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<h1>Mes incidents</h1>
<p class="subtitle">Témoin ou victime d'un acte commis par un super-vilain ? Déclarez-le ci-dessous.</p>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <ul class="mt-0" style="margin-bottom:0;padding-left:18px;">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="card" method="post" action="<?= APP_URL ?>/incidents.php">
    <?= csrf_field() ?>
    <h2 class="mt-0">Déclarer un incident</h2>
    <div class="form-row">
        <div class="field">
            <label for="title">Titre</label>
            <input type="text" id="title" name="title" required>
        </div>
        <div class="field">
            <label for="incident_type">Type d'incident</label>
            <select id="incident_type" name="incident_type" required>
                <option value="Vol">Vol</option>
                <option value="Cambriolage">Cambriolage</option>
                <option value="Meurtre">Meurtre</option>
                <option value="Tentative de meurtre">Tentative de meurtre</option>
                <option value="Attentat">Attentat</option>
                <option value="Prise d'otages">Prise d'otages</option>
                <option value="Séquestration">Séquestration</option>
                <option value="Autre">Autre</option>
            </select>
        </div>
    </div>
    <div class="form-row">
        <div class="field">
            <label for="location">Lieu de l'incident</label>
            <input type="text" id="location" name="location" placeholder="Adresse ou quartier" required>
        </div>
        <div class="field">
            <label for="villain_id">Super-vilain identifié (si connu)</label>
            <select id="villain_id" name="villain_id">
                <option value="">Inconnu</option>
                <?php foreach ($villains as $v): ?>
                    <option value="<?= (int) $v['id'] ?>"><?= e($v['pseudo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4" required></textarea>
    </div>
    <button type="submit" class="btn-red">Signaler l'incident</button>
</form>

<h2>Historique de mes signalements</h2>
<?php if (!$myIncidents): ?>
    <p class="empty-state">Vous n'avez signalé aucun incident pour le moment.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Titre</th><th>Priorité</th><th>Statut</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($myIncidents as $i): ?>
            <tr>
                <td><?= e($i['title']) ?></td>
                <td><span class="<?= priority_class($i['priority']) ?>"><?= priority_label($i['priority']) ?></span></td>
                <td><span class="<?= status_class($i['status']) ?>"><?= status_label($i['status']) ?></span></td>
                <td><?= format_date($i['created_at']) ?></td>
                <td><a class="btn btn-small btn-outline" href="<?= APP_URL ?>/incident.php?id=<?= (int) $i['id'] ?>">Détails</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>Incidents en cours à proximité</h2>
<p class="muted">Vérifiez qu'un incident n'a pas déjà été déclaré au même endroit avant de signaler le vôtre.</p>
<?php if (!$ongoing): ?>
    <p class="empty-state">Aucun incident en cours actuellement.</p>
<?php else: ?>
    <table>
        <thead><tr><th>Lieu</th><th>Type</th><th>Statut</th><th>Signalé le</th></tr></thead>
        <tbody>
        <?php foreach ($ongoing as $i): ?>
            <tr>
                <td><?= e($i['location']) ?></td>
                <td><?= e($i['incident_type']) ?></td>
                <td><span class="<?= status_class($i['status']) ?>"><?= status_label($i['status']) ?></span></td>
                <td><?= format_date($i['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
