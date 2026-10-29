</main>
<footer class="site-footer">
    <div class="wrap footer-inner">
        <div>
            <strong>Heros-SOS</strong>
            <p>Une plateforme Web 4 Heroes pour rapprocher citoyens et super-héros.</p>
        </div>
        <form class="newsletter-form" action="<?= APP_URL ?>/newsletter.php" method="post">
            <?= csrf_field() ?>
            <label for="footer-newsletter-email">Recevoir la newsletter</label>
            <div class="newsletter-row">
                <input id="footer-newsletter-email" type="email" name="email" placeholder="Votre adresse email" required>
                <button type="submit">S'inscrire</button>
            </div>
        </form>
    </div>
    <p class="copyright">&copy; <?= date('Y') ?> Web 4 Heroes. Tous droits réservés.</p>
</footer>
</body>
</html>
