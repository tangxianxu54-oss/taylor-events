<?php
/**
 * 全站页头：导航、字体、CSS
 */

$user = current_user();
$flashes = take_flashes();
$page = $page ?? '';
$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? 'Discover, host and join events across Taylor University campus.';
$bodyClass = $bodyClass ?? '';
$scripts = $scripts ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta name="theme-color" content="#4B2E83">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 64 64'%3E%3Crect width='64' height='64' rx='16' fill='%234B2E83'/%3E%3Ctext x='32' y='44' font-family='Georgia,serif' font-size='36' font-weight='bold' text-anchor='middle' fill='%23F2A900'%3ET%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;0,9..144,700;1,9..144,500&family=DM+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="<?= e(trim('page-' . $page . ' ' . $bodyClass)) ?>">
<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header" id="site-header">
    <div class="container header__inner">
        <a class="brand" href="index.php" aria-label="<?= e(APP_NAME) ?> home">
            <span class="brand__mark" aria-hidden="true">T</span>
            <span class="brand__text">
                <strong>Taylor Events</strong>
                <small>Student Activities</small>
            </span>
        </a>

        <nav class="nav" id="primary-nav" aria-label="Main navigation">
            <a class="nav__link<?= is_active('home', $page) ?>" href="index.php"<?= $page === 'home' ? ' aria-current="page"' : '' ?>>Home</a>
            <a class="nav__link<?= is_active('events', $page) ?>" href="events.php"<?= $page === 'events' ? ' aria-current="page"' : '' ?>>Browse events</a>
            <?php if ($user): ?>
                <a class="nav__link<?= is_active('create', $page) ?>" href="create-event.php">Host an event</a>
                <a class="nav__link<?= is_active('my-events', $page) ?>" href="my-events.php">My events</a>
                <a class="nav__link<?= is_active('profile', $page) ?>" href="profile.php">Profile</a>
            <?php else: ?>
                <a class="nav__link nav__link--mobile" href="login.php">Sign in</a>
                <a class="nav__link nav__link--mobile" href="register.php">Create account</a>
            <?php endif; ?>
        </nav>

        <div class="header__actions">
            <?php if ($user): ?>
                <a class="btn btn--primary btn--sm header__cta" href="create-event.php">
                    <?= icon('plus', 16) ?><span>Create event</span>
                </a>
                <div class="user-menu" data-user-menu>
                    <button class="user-menu__trigger" type="button" data-user-toggle aria-haspopup="true" aria-expanded="false">
                        <span class="avatar avatar--sm" style="--avatar-color: <?= e($user['avatar'] ? '#4B2E83' : '#4B2E83') ?>">
                            <?php if (avatar_url($user['avatar'])): ?>
                                <img src="<?= e(avatar_url($user['avatar'])) ?>" alt="">
                            <?php else: ?>
                                <?= e(initials($user['name'])) ?>
                            <?php endif; ?>
                        </span>
                        <span class="user-menu__name"><?= e(explode(' ', $user['name'])[0]) ?></span>
                        <?= icon('chevron-down', 14) ?>
                    </button>
                    <div class="user-menu__panel" data-user-panel hidden>
                        <div class="user-menu__head">
                            <strong><?= e($user['name']) ?></strong>
                            <span><?= e($user['email']) ?></span>
                            <?php if ((int) $user['verified'] === 1): ?>
                                <span class="pill pill--verified"><?= icon('check-circle', 13) ?> Verified student</span>
                            <?php else: ?>
                                <span class="pill pill--warn"><?= icon('alert', 13) ?> Unverified</span>
                            <?php endif; ?>
                        </div>
                        <a class="user-menu__item" href="profile.php"><?= icon('user', 17) ?> My profile</a>
                        <a class="user-menu__item" href="my-events.php"><?= icon('ticket', 17) ?> My events</a>
                        <a class="user-menu__item" href="create-event.php"><?= icon('plus', 17) ?> Host an event</a>
                        <form method="post" action="logout.php" class="user-menu__logout">
                            <?= csrf_field() ?>
                            <button type="submit" class="user-menu__item"><?= icon('logout', 17) ?> Sign out</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <a class="btn btn--ghost btn--sm header__cta" href="login.php">Sign in</a>
                <a class="btn btn--primary btn--sm" href="register.php">Join free</a>
            <?php endif; ?>

            <button class="menu-toggle" type="button" data-menu-toggle aria-label="Open menu" aria-expanded="false" aria-controls="primary-nav">
                <span data-menu-icon><?= icon('menu', 22) ?></span>
            </button>
        </div>
    </div>
</header>

<main id="main" class="site-main">
