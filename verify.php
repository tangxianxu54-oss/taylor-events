<?php
/**
 * 校园邮箱验证（模拟）
 */
require __DIR__ . '/includes/init.php';

$user = current_user();
if ($user && (int) $user['verified'] === 1) {
    redirect('index.php');
}

$pendingId = (int) ($_SESSION['pending_verify'] ?? ($user['id'] ?? 0));
if ($pendingId <= 0) {
    flash('info', 'Create an account first, then verify your campus email.');
    redirect('register.php');
}

$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$pendingId]);
$pending = $stmt->fetch();

if (!$pending) {
    unset($_SESSION['pending_verify']);
    flash('error', 'We could not find that account. Please register again.');
    redirect('register.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (post_str('action') === 'resend') {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $update = db()->prepare('UPDATE users SET verification_code = ? WHERE id = ?');
        $update->execute([$code, (int) $pending['id']]);
        flash('info', 'A new verification code was generated.');
        redirect('verify.php');
    }

    $code = preg_replace('/\D/', '', post_str('code'));
    if ($code === '') {
        $errors[] = 'Enter the 6-digit code from your email.';
    } elseif (!hash_equals((string) $pending['verification_code'], $code)) {
        $errors[] = 'That code does not match. Check it again or resend a new one.';
    } else {
        $update = db()->prepare('UPDATE users SET verified = 1, verification_code = NULL WHERE id = ?');
        $update->execute([(int) $pending['id']]);
        unset($_SESSION['pending_verify']);

        if (!$user) {
            login_user((int) $pending['id']);
        }
        flash('success', 'You are verified. Welcome to Taylor Events, ' . explode(' ', $pending['name'])[0] . '!');
        redirect('index.php');
    }
}

$page = 'verify';
$pageTitle = 'Verify your campus email — ' . APP_NAME;
require __DIR__ . '/includes/header.php';
?>

<section class="auth">
    <div class="container auth__grid">
        <div class="auth__aside">
            <span class="eyebrow eyebrow--gold">One quick check</span>
            <h1>You are almost<br><em>in.</em></h1>
            <p>We only want real Taylor students joining events. Verify your campus email and your account is ready to go.</p>

            <ul class="auth__perks">
                <li><?= icon('shield', 18) ?> Keeps campus events student-only</li>
                <li><?= icon('mail', 18) ?> Takes about ten seconds</li>
                <li><?= icon('check-circle', 18) ?> Then you can RSVP and host events</li>
            </ul>
        </div>

        <div class="auth__card">
            <div class="auth__icon"><?= icon('mail', 26) ?></div>
            <h2>Verify your campus email</h2>
            <p class="muted auth__sub">
                We sent a 6-digit code to <strong><?= e(mask_email($pending['email'])) ?></strong>.
            </p>

            <?php if ($errors): ?>
                <div class="alert alert--error" role="alert">
                    <?= icon('alert', 18) ?>
                    <div><?= e(implode(' ', $errors)) ?></div>
                </div>
            <?php endif; ?>

            <?php if (DEMO_MODE): ?>
                <div class="demo-box demo-box--code">
                    <div>
                        <strong>Demo mode · no real email is sent</strong>
                        <span>Your verification code is <b data-demo-code><?= e($pending['verification_code']) ?></b></span>
                    </div>
                    <button type="button" class="btn btn--ghost btn--xs" data-fill-code="<?= e($pending['verification_code']) ?>">Use this code</button>
                </div>
            <?php endif; ?>

            <form method="post" action="verify.php" class="auth-form" data-verify-form>
                <?= csrf_field() ?>
                <label class="field">
                    <span class="field__label">Verification code</span>
                    <input class="input input--code" type="text" name="code" inputmode="numeric" pattern="[0-9]*"
                           maxlength="6" autocomplete="one-time-code" placeholder="000000" required autofocus>
                </label>
                <button class="btn btn--primary btn--full" type="submit">
                    <?= icon('check', 17) ?> Verify and continue
                </button>
            </form>

            <form method="post" action="verify.php" class="auth__resend">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="resend">
                <span class="muted">Did not get a code?</span>
                <button type="submit" class="link-button">Send a new one</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
