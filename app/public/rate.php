<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login($currentUser);

$incidentId = (int) ($_GET['incident_id'] ?? $_POST['incident_id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT i.*, h.pseudo AS h_pseudo FROM incidents i
     LEFT JOIN users h ON h.id = i.hero_id WHERE i.id = ?"
);
$stmt->execute([$incidentId]);
$incident = $stmt->fetch();

if (!$incident || (int) $incident['citizen_id'] !== (int) $currentUser['id']) {
    flash('error', "Vous ne pouvez noter que vos propres incidents.");
    redirect(APP_URL . '/incidents.php');
}
if (!in_array($incident['status'], ['reussi', 'echoue'], true) || !$incident['hero_id']) {
    flash('error', 'Cet incident ne peut pas encore être noté.');
    redirect(APP_URL . '/incident.php?id=' . $incidentId);
}

$already = $pdo->prepare('SELECT * FROM ratings WHERE incident_id = ?');
$already->execute([$incidentId]);
$already = $already->fetch();

$pageTitle = 'Noter un super-héros';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$already) {
    check_csrf();
    $note = (int) ($_POST['note'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($note < 1 || $note > 5) {
        $errors[] = 'La note doit être comprise entre 1 et 5.';
    } else {
        $pdo->prepare('INSERT INTO ratings (incident_id, citizen_id, hero_id, note, comment) VALUES (?,?,?,?,?)')
            ->execute([$incidentId, $currentUser['id'], $incident['hero_id'], $note, $comment ?: null]);
        flash('success', 'Merci pour votre avis !');
        redirect(APP_URL . '/incident.php?id=' . $incidentId);
    }
}

require __DIR__ . '/../includes/header.php';
?>
<h1>Noter <?= e($incident['h_pseudo']) ?></h1>
<p class="subtitle">Votre avis aide les autres citoyens et contribue à la réputation du super-héros.</p>

<?php if ($already): ?>
    <div class="alert alert-info">Vous avez déjà noté cette intervention.</div>
<?php else: ?>
    <?php if ($errors): ?>
        <div class="alert alert-error"><?= e($errors[0]) ?></div>
    <?php endif; ?>
    <form class="card" style="max-width:420px;" method="post" action="<?= APP_URL ?>/rate.php">
        <?= csrf_field() ?>
        <input type="hidden" name="incident_id" value="<?= $incidentId ?>">
        <div class="field">
            <label for="note">Note</label>
            <select id="note" name="note" required>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Très bien</option>
                <option value="3">3 - Correct</option>
                <option value="2">2 - Décevant</option>
                <option value="1">1 - Très décevant</option>
            </select>
        </div>
        <div class="field">
            <label for="comment">Commentaire (facultatif)</label>
            <textarea id="comment" name="comment" rows="3"></textarea>
        </div>
        <button type="submit">Envoyer mon avis</button>
    </form>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
