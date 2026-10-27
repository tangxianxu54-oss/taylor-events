<?php
/**
 * 全站页脚：链接、Toast 容器、确认弹窗、脚本
 */
$user = current_user();
?>
</main>

<footer class="site-footer">
    <div class="container footer__grid">
        <div class="footer__brand">
            <a class="brand brand--light" href="index.php">
                <img class="brand__logo brand__logo--footer" src="assets/img/logo.png" alt="Taylor's College">
            </a>
            <p>One place for everything happening on campus — from intramural finals to late-night pancakes.</p>
            <span class="footer__note">A student project · <?= e(CAMPUS_NAME) ?></span>
        </div>

        <div class="footer__col">
            <h4>Explore</h4>
            <a href="events.php">All events</a>
            <a href="events.php?when=upcoming">Happening soon</a>
            <a href="events.php?category=sports">Sports</a>
            <a href="events.php?category=arts">Arts &amp; Culture</a>
        </div>

        <div class="footer__col">
            <h4>Get involved</h4>
            <a href="create-event.php">Host an event</a>
            <a href="my-events.php">My events</a>
            <a href="events.php?category=service">Service opportunities</a>
            <a href="events.php?category=wellness">Wellness programs</a>
        </div>

        <div class="footer__col">
            <h4>Account</h4>
            <?php if ($user): ?>
                <a href="profile.php">My profile</a>
                <a href="my-events.php">My registrations</a>
                <a href="create-event.php">Create an event</a>
                <form method="post" action="logout.php" class="footer__logout">
                    <?= csrf_field() ?>
                    <button type="submit">Sign out</button>
                </form>
            <?php else: ?>
                <a href="login.php">Sign in</a>
                <a href="register.php">Create account</a>
                <a href="verify.php">Verify campus email</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container footer__bottom">
        <span>© <?= date('Y') ?> <?= e(APP_NAME) ?>. Built for the Taylor community.</span>
        <span class="footer__tagline"><?= icon('sparkles', 14) ?> Find your people. Show up.</span>
    </div>
</footer>

<div class="toast-host" id="toast-host" role="status" aria-live="polite"></div>

<div class="modal" id="confirm-modal" hidden>
    <div class="modal__backdrop" data-confirm-cancel></div>
    <div class="modal__card" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <h3 id="confirm-title" data-confirm-title>Are you sure?</h3>
        <p data-confirm-message></p>
        <div class="modal__actions">
            <button type="button" class="btn btn--ghost" data-confirm-cancel>Never mind</button>
            <button type="button" class="btn btn--danger" data-confirm-ok>Confirm</button>
        </div>
    </div>
</div>

<script>
window.__FLASH__ = <?= json_encode($flashes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.__APP__ = { userId: <?= (int) ($user['id'] ?? 0) ?> };
</script>
<script src="assets/js/app.js" defer></script>
<?php foreach ($scripts as $script): ?>
<script src="<?= e($script) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
