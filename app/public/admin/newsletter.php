<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_admin($currentUser);

$pageTitle = 'Envoi de la newsletter';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $subject = trim($_POST['subject'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $target = $_POST['target'] ?? 'all';
    $singleEmail = trim($_POST['single_email'] ?? '');

    if ($subject === '' || $body === '') {
        flash('error', "L'objet et le contenu sont obligatoires.");
        redirect(APP_URL . '/admin/newsletter.php');
    }

    $htmlBody = nl2br(e($body));
    $sentCount = 0;

    if ($target === 'single' && $singleEmail !== '') {
        send_app_mail($singleEmail, $subject, $htmlBody);
        $sentCount = 1;
    } else {
        $subscribers = $pdo->query('SELECT email FROM newsletter_subscribers WHERE subscribed = 1')->fetchAll();
        foreach ($subscribers as $s) {
            send_app_mail($s['email'], $subject, $htmlBody);
            $sentCount++;
        }
    }

    flash('success', 'Newsletter envoyée à ' . $sentCount . ' destinataire(s). Consultez Mailhog pour le voir en environnement de développement.');
    redirect(APP_URL . '/admin/newsletter.php');
}

$subscriberCount = (int) $pdo->query("SELECT COUNT(*) AS c FROM newsletter_subscribers WHERE subscribed = 1")->fetch()['c'];

require __DIR__ . '/../../includes/header.php';
?>
<h1>Newsletter</h1>
<p><a href="<?= APP_URL ?>/admin/index.php">&larr; Retour au dashboard</a></p>
<p class="subtitle"><?= $subscriberCount ?> abonné(s) actuellement inscrits.</p>

<form class="card" method="post" action="<?= APP_URL ?>/admin/newsletter.php">
    <?= csrf_field() ?>
    <div class="field">
        <label for="target">Destinataires</label>
        <select id="target" name="target" onchange="document.getElementById('single-email-field').style.display = this.value === 'single' ? 'block' : 'none';">
            <option value="all">Tous les abonnés (<?= $subscriberCount ?>)</option>
            <option value="single">Un utilisateur en particulier</option>
        </select>
    </div>
    <div class="field" id="single-email-field" style="display:none;">
        <label for="single_email">Adresse email</label>
        <input type="email" id="single_email" name="single_email">
    </div>
    <div class="field">
        <label for="subject">Objet</label>
        <input type="text" id="subject" name="subject" required>
    </div>
    <div class="field">
        <label for="body">Contenu</label>
        <textarea id="body" name="body" rows="8" required></textarea>
    </div>
    <button type="submit" class="btn-gold">Envoyer la newsletter</button>
</form>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
