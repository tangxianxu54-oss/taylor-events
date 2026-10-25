<?php
/**
 * 个人资料管理
 */
require __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$going = user_going_events((int) $user['id']);
$hosting = user_hosted_events((int) $user['id']);
$totalSignups = array_sum(array_map(static fn(array $event): int => (int) $event['going_count'], $hosting));
$years = ['Freshman', 'Sophomore', 'Junior', 'Senior', 'Graduate', 'Staff'];

$page = 'profile';
$pageTitle = 'My profile — ' . APP_NAME;
$scripts = ['assets/js/profile.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-head page-head--compact">
    <div class="container page-head__inner">
        <div>
            <span class="eyebrow"><?= icon('user', 15) ?> Account</span>
            <h1>My profile</h1>
            <p class="page-head__lede">Keep your details current so hosts know who is coming.</p>
        </div>
        <a class="btn btn--ghost btn--sm" href="my-events.php">My events <?= icon('arrow-right', 15) ?></a>
    </div>
</section>

<div class="container profile-layout">
    <aside class="profile-card">
        <div class="profile-card__avatar">
            <span class="avatar avatar--xl" data-avatar-preview>
                <?php if (avatar_url($user['avatar'])): ?>
                    <img src="<?= e(avatar_url($user['avatar'])) ?>" alt="">
                <?php else: ?>
                    <?= e(initials($user['name'])) ?>
                <?php endif; ?>
            </span>
            <label class="avatar-edit" for="avatar-input" title="Change photo">
                <?= icon('upload', 15) ?>
            </label>
        </div>

        <h2 data-profile-name><?= e($user['name']) ?></h2>
        <p class="profile-card__role" data-profile-role>
            <?= e(trim(($user['major'] ?? '') . ' · ' . ($user['year'] ?? ''), ' ·') ?: 'Taylor student') ?>
        </p>

        <?php if ((int) $user['verified'] === 1): ?>
            <span class="pill pill--verified"><?= icon('check-circle', 13) ?> Verified student</span>
        <?php else: ?>
            <a class="pill pill--warn" href="verify.php"><?= icon('alert', 13) ?> Verify your email</a>
        <?php endif; ?>

        <p class="profile-card__email"><?= icon('mail', 15) ?> <?= e($user['email']) ?></p>
        <p class="profile-card__joined">Member since <?= e(date('F Y', strtotime($user['created_at']))) ?></p>

        <div class="profile-stats">
            <div>
                <strong><?= count($going) ?></strong>
                <span>Going</span>
            </div>
            <div>
                <strong><?= count($hosting) ?></strong>
                <span>Hosted</span>
            </div>
            <div>
                <strong><?= (int) $totalSignups ?></strong>
                <span>Sign-ups</span>
            </div>
        </div>

        <p class="profile-card__hint"><?= icon('sparkles', 14) ?> A clear photo helps people recognize you at events.</p>
    </aside>

    <div class="profile-main">
        <form class="form-card" data-profile-form enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="profile">
            <input type="file" name="avatar" id="avatar-input" accept="image/jpeg,image/png,image/webp" hidden data-avatar-input>

            <section class="form-section">
                <div class="form-section__head">
                    <h2>Public profile</h2>
                    <p>This is what other students see next to your name.</p>
                </div>

                <label class="field">
                    <span class="field__label">Full name</span>
                    <input class="input" type="text" name="name" maxlength="60" required value="<?= e($user['name']) ?>">
                </label>

                <div class="field-grid">
                    <label class="field">
                        <span class="field__label">Student ID</span>
                        <input class="input" type="text" name="student_id" maxlength="20"
                               value="<?= e($user['student_id'] ?? '') ?>" placeholder="S-104287">
                    </label>
                    <label class="field">
                        <span class="field__label">Year</span>
                        <select class="input input--select" name="year">
                            <option value="">Select year</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?= e($year) ?>"<?= sel($year, $user['year'] ?? '') ?>><?= e($year) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <label class="field">
                    <span class="field__label">Major / program</span>
                    <input class="input" type="text" name="major" maxlength="80"
                           value="<?= e($user['major'] ?? '') ?>" placeholder="Computer Science">
                </label>

                <label class="field">
                    <span class="field__label">Bio</span>
                    <textarea class="input textarea" name="bio" rows="4" maxlength="500"
                              placeholder="A sentence or two about you — what you study, what you love."><?= e($user['bio'] ?? '') ?></textarea>
                    <span class="field__hint">Max 500 characters.</span>
                </label>

                <label class="field">
                    <span class="field__label">Interests</span>
                    <input class="input" type="text" name="interests" maxlength="200"
                           value="<?= e($user['interests'] ?? '') ?>" placeholder="Soccer, film, coffee">
                    <span class="field__hint">Comma separated. These help hosts get to know you.</span>
                </label>
            </section>

            <div class="form-actions">
                <a class="btn btn--ghost" href="my-events.php">Cancel</a>
                <button class="btn btn--primary" type="submit"><?= icon('check', 17) ?> Save profile</button>
            </div>
        </form>

        <form class="form-card" data-password-form novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">

            <section class="form-section">
                <div class="form-section__head">
                    <h2>Password &amp; security</h2>
                    <p>Use at least 8 characters. You will need your current password.</p>
                </div>

                <label class="field">
                    <span class="field__label">Current password</span>
                    <input class="input" type="password" name="current_password" required autocomplete="current-password">
                </label>

                <div class="field-grid">
                    <label class="field">
                        <span class="field__label">New password</span>
                        <input class="input" type="password" name="new_password" required minlength="8" autocomplete="new-password">
                    </label>
                    <label class="field">
                        <span class="field__label">Confirm new password</span>
                        <input class="input" type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
                    </label>
                </div>
            </section>

            <div class="form-actions">
                <button class="btn btn--ghost" type="submit"><?= icon('lock', 16) ?> Update password</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
