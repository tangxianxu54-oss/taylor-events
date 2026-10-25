<?php
/**
 * 登录
 */
require __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect('index.php');
}

$errors = [];
$email = '';
$next = safe_next($_GET['next'] ?? $_POST['next'] ?? null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = strtolower(post_str('email'));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errors[] = 'Enter both your email and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $found = $stmt->fetch();

        if ($found && password_verify($password, $found['password_hash'])) {
            if ((int) $found['verified'] !== 1) {
                $_SESSION['pending_verify'] = (int) $found['id'];
                flash('info', 'One more step — verify your campus email to finish signing in.');
                redirect('verify.php');
            }
            login_user((int) $found['id']);
            flash('success', 'Welcome back, ' . explode(' ', $found['name'])[0] . '.');
            redirect($next);
        }

        $errors[] = 'That email and password combination does not match our records.';
    }
}

$page = 'login';
$pageTitle = 'Sign in — ' . APP_NAME;
require __DIR__ . '/includes/header.php';
?>

<section class="auth">
    <div class="container auth__grid">
        <div class="auth__aside">
            <span class="eyebrow eyebrow--gold">Welcome back</span>
            <h1>Campus is<br><em>happening.</em></h1>
            <p>Sign in to reserve your spot, keep track of your plans, and host events of your own.</p>

            <ul class="auth__perks">
                <li><?= icon('check-circle', 18) ?> One-click RSVP to any campus event</li>
                <li><?= icon('check-circle', 18) ?> Manage every event you host in one place</li>
                <li><?= icon('check-circle', 18) ?> Your spot is saved across devices</li>
            </ul>

            <div class="auth__quote">
                <p>"I found my small group, my intramural team and my Tuesday lunch plans here."</p>
                <span>— Ava T., Freshman</span>
            </div>
        </div>

        <div class="auth__card">
            <h2>Sign in</h2>
            <p class="muted auth__sub">Use your Taylor email and password.</p>

            <?php if ($errors): ?>
                <div class="alert alert--error" role="alert">
                    <?= icon('alert', 18) ?>
                    <div><?= e(implode(' ', $errors)) ?></div>
                </div>
            <?php endif; ?>

            <form method="post" action="login.php" class="auth-form">
                <?= csrf_field() ?>
                <input type="hidden" name="next" value="<?= e($next) ?>">

                <label class="field">
                    <span class="field__label">Campus email</span>
                    <input class="input" type="email" name="email" required autofocus
                           placeholder="you@taylor.edu" value="<?= e($email) ?>">
                </label>

                <label class="field">
                    <span class="field__label">Password</span>
                    <input class="input" type="password" name="password" required placeholder="Your password">
                </label>

                <button class="btn btn--primary btn--full" type="submit">
                    Sign in <?= icon('arrow-right', 17) ?>
                </button>
            </form>

            <div class="demo-box">
                <div>
                    <strong>Demo account</strong>
                    <span>emma.carter@taylor.edu · password123</span>
                </div>
                <button type="button" class="btn btn--ghost btn--xs" data-fill-demo>Fill it in</button>
            </div>

            <p class="auth__alt">New to Taylor Events? <a href="register.php">Create an account</a></p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
