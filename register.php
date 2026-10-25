<?php
/**
 * 注册：创建账号并生成模拟校园邮箱验证码
 */
require __DIR__ . '/includes/init.php';

if (current_user()) {
    redirect('index.php');
}

$errors = [];
$form = [
    'name' => '',
    'email' => '',
    'student_id' => '',
    'major' => '',
    'year' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    foreach ($form as $key => $_) {
        $form[$key] = post_str($key);
    }
    $email = strtolower($form['email']);
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (mb_strlen($form['name']) < 2) {
        $errors[] = 'Please enter your full name.';
    }
    if (!is_school_email($email)) {
        $errors[] = 'Please use a valid @taylor.edu campus email.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password needs at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'The two passwords do not match.';
    }

    if (!$errors) {
        $check = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetchColumn()) {
            $errors[] = 'An account with that email already exists. Try signing in instead.';
        }
    }

    if (!$errors) {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = db()->prepare(
            'INSERT INTO users (name, email, student_id, major, year, password_hash, role, verified, verification_code, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?)'
        );
        $stmt->execute([
            $form['name'],
            $email,
            $form['student_id'] !== '' ? $form['student_id'] : null,
            $form['major'] !== '' ? $form['major'] : null,
            $form['year'] !== '' ? $form['year'] : null,
            password_hash($password, PASSWORD_DEFAULT),
            'student',
            $code,
            date('Y-m-d H:i:s'),
        ]);

        $_SESSION['pending_verify'] = (int) db()->lastInsertId();
        flash('info', 'Account created. Enter the 6-digit code from your campus inbox.');
        redirect('verify.php');
    }
}

$years = ['Freshman', 'Sophomore', 'Junior', 'Senior', 'Graduate', 'Staff'];
$page = 'register';
$pageTitle = 'Create your account — ' . APP_NAME;
require __DIR__ . '/includes/header.php';
?>

<section class="auth">
    <div class="container auth__grid">
        <div class="auth__aside">
            <span class="eyebrow eyebrow--gold">Join the campus</span>
            <h1>Your next<br><em>favorite memory</em><br>starts here.</h1>
            <p>Create a free account with your Taylor email. Reserve spots, host your own events, and never miss a thing.</p>

            <ul class="auth__perks">
                <li><?= icon('check-circle', 18) ?> Free for every Taylor student</li>
                <li><?= icon('check-circle', 18) ?> Verified campus email keeps events student-only</li>
                <li><?= icon('check-circle', 18) ?> Host your own events in two minutes</li>
            </ul>

            <div class="auth__quote">
                <p>"The midnight pancake run is now a tradition. All because someone posted it here."</p>
                <span>— Emma C., Junior</span>
            </div>
        </div>

        <div class="auth__card auth__card--wide">
            <h2>Create your account</h2>
            <p class="muted auth__sub">Use your @taylor.edu email so we know you are a real student.</p>

            <?php if ($errors): ?>
                <div class="alert alert--error" role="alert">
                    <?= icon('alert', 18) ?>
                    <div>
                        <strong>Almost there</strong>
                        <ul>
                            <?php foreach ($errors as $message): ?>
                                <li><?= e($message) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" class="auth-form">
                <?= csrf_field() ?>

                <label class="field">
                    <span class="field__label">Full name</span>
                    <input class="input" type="text" name="name" required maxlength="60"
                           placeholder="Emma Carter" value="<?= e($form['name']) ?>">
                </label>

                <label class="field">
                    <span class="field__label">Campus email</span>
                    <input class="input" type="email" name="email" required
                           placeholder="you@taylor.edu" value="<?= e($form['email']) ?>">
                </label>

                <div class="field-grid">
                    <label class="field">
                        <span class="field__label">Student ID <span class="field__optional">optional</span></span>
                        <input class="input" type="text" name="student_id" maxlength="20"
                               placeholder="S-104287" value="<?= e($form['student_id']) ?>">
                    </label>
                    <label class="field">
                        <span class="field__label">Year</span>
                        <select class="input input--select" name="year">
                            <option value="">Select year</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= e($year) ?>"<?= sel($year, $form['year']) ?>><?= e($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <label class="field">
                    <span class="field__label">Major <span class="field__optional">optional</span></span>
                    <input class="input" type="text" name="major" maxlength="80"
                           placeholder="Computer Science" value="<?= e($form['major']) ?>">
                </label>

                <div class="field-grid">
                    <label class="field">
                        <span class="field__label">Password</span>
                        <input class="input" type="password" name="password" required minlength="8" placeholder="At least 8 characters">
                    </label>
                    <label class="field">
                        <span class="field__label">Confirm password</span>
                        <input class="input" type="password" name="confirm_password" required minlength="8" placeholder="Repeat password">
                    </label>
                </div>

                <button class="btn btn--primary btn--full" type="submit">
                    Create account <?= icon('arrow-right', 17) ?>
                </button>
            </form>

            <p class="auth__alt">Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
