<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_login($currentUser);

$id = (int) ($_GET['id'] ?? 0);

function load_incident(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        "SELECT i.*, c.first_name AS c_first, c.last_name AS c_last, c.id AS c_id,
                h.pseudo AS h_pseudo, h.first_name AS h_first, h.last_name AS h_last,
                v.pseudo AS v_pseudo
         FROM incidents i
         JOIN users c ON c.id = i.citizen_id
         LEFT JOIN users h ON h.id = i.hero_id
         LEFT JOIN villains v ON v.id = i.villain_id
         WHERE i.id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

$incident = load_incident($pdo, $id);
if (!$incident) {
    flash('error', "Cet incident n'existe pas.");
    redirect(APP_URL . '/incidents.php');
}

$isOwner = $incident['c_id'] === $currentUser['id'];
$canModerate = is_admin($currentUser) || is_hero($currentUser);

if (!$isOwner && !$canModerate) {
    flash('error', "Vous n'avez pas accès à cet incident.");
    redirect(APP_URL . '/incidents.php');
}

$pageTitle = $incident['title'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    // Priorisation : moderateurs et super-heros uniquement (4.1.1)
    if ($action === 'set_priority' && $canModerate) {
        $priority = $_POST['priority'] ?? 'normale';
        if (in_array($priority, ['basse', 'normale', 'haute'], true)) {
            $pdo->prepare('UPDATE incidents SET priority = ? WHERE id = ?')->execute([$priority, $id]);
            flash('success', 'Priorité mise à jour.');
        }
    }

    // Intervention : un super-heros prend en charge l'incident (1 a la fois)
    elseif ($action === 'intervene' && is_hero($currentUser)) {
        if ($incident['status'] === 'signale') {
            $pdo->prepare('UPDATE incidents SET status = "intervention", hero_id = ? WHERE id = ?')
                ->execute([$currentUser['id'], $id]);

            $admins = $pdo->query('SELECT email FROM users WHERE is_admin = 1')->fetchAll();
            foreach ($admins as $a) {
                send_app_mail($a['email'], 'Intervention en cours sur un incident',
                    '<p>' . e($currentUser['pseudo']) . ' intervient désormais sur l\'incident "' . e($incident['title']) . '".</p>'
                );
            }
            flash('success', 'Vous intervenez maintenant sur cet incident.');
        } else {
            flash('error', 'Cet incident est déjà pris en charge.');
        }
    }

    // Fin d'intervention : reussi / echoue
    elseif ($action === 'resolve' && is_hero($currentUser) && (int) $incident['hero_id'] === (int) $currentUser['id']) {
        $result = $_POST['result'] ?? '';
        $rescued = (int) ($_POST['citizens_rescued'] ?? 0);
        if (in_array($result, ['reussi', 'echoue'], true)) {
            $pdo->prepare('UPDATE incidents SET status = ?, citizens_rescued = ? WHERE id = ?')
                ->execute([$result, $rescued, $id]);

            $admins = $pdo->query('SELECT email FROM users WHERE is_admin = 1')->fetchAll();
            foreach ($admins as $a) {
                send_app_mail($a['email'], 'Incident clôturé',
                    '<p>L\'incident "' . e($incident['title']) . '" est désormais marqué comme "' . status_label($result) . '".</p>'
                );
            }

            // Le citoyen est invite a noter le super-heros
            $citizenStmt = $pdo->prepare('SELECT email, first_name FROM users WHERE id = ?');
            $citizenStmt->execute([$incident['c_id']]);
            $citizen = $citizenStmt->fetch();
            if ($citizen) {
                send_app_mail($citizen['email'], 'Votre incident a été traité',
                    '<p>Bonjour ' . e($citizen['first_name']) . ',</p>
                     <p>L\'intervention sur votre signalement "' . e($incident['title']) . '" est terminée (' . status_label($result) . ').</p>
                     <p><a href="' . APP_URL . '/rate.php?incident_id=' . $id . '">Donnez votre avis sur le super-héros intervenu</a></p>'
                );
            }

            flash('success', 'Incident clôturé.');
        }
    }

    // Moderation : modification/suppression par l'administration
    elseif ($action === 'delete' && is_admin($currentUser)) {
        $pdo->prepare('DELETE FROM incidents WHERE id = ?')->execute([$id]);

        $citizenStmt = $pdo->prepare('SELECT email, first_name FROM users WHERE id = ?');
        $citizenStmt->execute([$incident['c_id']]);
        $citizen = $citizenStmt->fetch();
        if ($citizen) {
            send_app_mail($citizen['email'], 'Votre signalement a été supprimé',
                '<p>Bonjour ' . e($citizen['first_name']) . ',</p>
                 <p>Votre signalement "' . e($incident['title']) . '" a été supprimé par un modérateur (doublon ou information erronée).</p>'
            );
        }

        flash('success', 'Incident supprimé.');
        redirect(APP_URL . '/incidents.php');
    }

    redirect(APP_URL . '/incident.php?id=' . $id);
}

$incident = load_incident($pdo, $id);
$villains = $pdo->query('SELECT id, pseudo FROM villains ORDER BY pseudo')->fetchAll();

$rating = $pdo->prepare('SELECT * FROM ratings WHERE incident_id = ?');
$rating->execute([$id]);
$rating = $rating->fetch();

require __DIR__ . '/../includes/header.php';
?>
<h1><?= e($incident['title']) ?></h1>
<p class="subtitle">
    <span class="<?= status_class($incident['status']) ?>"><?= status_label($incident['status']) ?></span>
    <span class="<?= priority_class($incident['priority']) ?>"><?= priority_label($incident['priority']) ?></span>
</p>

<div class="two-col">
    <div>
        <div class="card">
            <p><strong>Type :</strong> <?= e($incident['incident_type']) ?></p>
            <p><strong>Lieu :</strong> <?= e($incident['location']) ?></p>
            <p><strong>Super-vilain :</strong> <?= $incident['v_pseudo'] ? e($incident['v_pseudo']) : 'Non identifié' ?></p>
            <p><strong>Description :</strong><br><?= nl2br(e($incident['description'])) ?></p>
            <p><strong>Signalé par :</strong> <?= e($incident['c_first'] . ' ' . $incident['c_last']) ?> le <?= format_date($incident['created_at']) ?></p>
            <?php if ($incident['h_pseudo']): ?>
                <p><strong>Super-héros intervenant :</strong> <?= e($incident['h_pseudo']) ?></p>
            <?php endif; ?>
            <?php if (in_array($incident['status'], ['reussi','echoue'], true)): ?>
                <p><strong>Citoyens secourus :</strong> <?= (int) $incident['citizens_rescued'] ?></p>
            <?php endif; ?>
        </div>

        <?php if ($isOwner && in_array($incident['status'], ['reussi','echoue'], true) && !$rating): ?>
            <div class="alert alert-info">
                Cet incident est clôturé. <a href="<?= APP_URL ?>/rate.php?incident_id=<?= $id ?>">Notez le super-héros intervenu</a>.
            </div>
        <?php endif; ?>
    </div>

    <div>
        <?php if ($canModerate): ?>
            <form class="card" method="post" action="<?= APP_URL ?>/incident.php?id=<?= $id ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="set_priority">
                <h2 class="mt-0">Priorité</h2>
                <div class="field">
                    <select name="priority">
                        <option value="basse" <?= $incident['priority'] === 'basse' ? 'selected' : '' ?>>Basse</option>
                        <option value="normale" <?= $incident['priority'] === 'normale' ? 'selected' : '' ?>>Normale</option>
                        <option value="haute" <?= $incident['priority'] === 'haute' ? 'selected' : '' ?>>Haute</option>
                    </select>
                </div>
                <button type="submit" class="btn-small">Mettre à jour</button>
            </form>
        <?php endif; ?>

        <?php if (is_hero($currentUser)): ?>
            <div class="card">
                <h2 class="mt-0">Intervention</h2>
                <?php if ($incident['status'] === 'signale'): ?>
                    <form method="post" action="<?= APP_URL ?>/incident.php?id=<?= $id ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="intervene">
                        <button type="submit" class="btn-gold">J'interviens sur cet incident</button>
                    </form>
                <?php elseif ((int) $incident['hero_id'] === (int) $currentUser['id'] && $incident['status'] === 'intervention'): ?>
                    <form method="post" action="<?= APP_URL ?>/incident.php?id=<?= $id ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="resolve">
                        <div class="field">
                            <label for="citizens_rescued">Citoyens secourus</label>
                            <input type="number" id="citizens_rescued" name="citizens_rescued" min="0" value="0">
                        </div>
                        <div class="actions-row">
                            <button type="submit" name="result" value="reussi">Intervention réussie</button>
                            <button type="submit" name="result" value="echoue" class="btn-red">Intervention échouée</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="muted">Aucune action possible : cet incident est déjà pris en charge par un autre super-héros ou clôturé.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (is_admin($currentUser)): ?>
            <div class="card">
                <h2 class="mt-0">Modération</h2>
                <form method="post" action="<?= APP_URL ?>/incident.php?id=<?= $id ?>"
                      onsubmit="return confirm('Supprimer définitivement cet incident (doublon) ?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn-red">Supprimer (doublon)</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
