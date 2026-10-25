<?php
/**
 * 我的活动：我报名的 / 我主办的
 */
require __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$tab = (string) ($_GET['tab'] ?? 'going');
$tab = in_array($tab, ['going', 'hosting'], true) ? $tab : 'going';

$going = user_going_events((int) $user['id']);
$hosting = user_hosted_events((int) $user['id']);

$goingUpcoming = array_values(array_filter($going, static fn(array $event): bool => !$event['is_past']));
$totalSignups = array_sum(array_map(static fn(array $event): int => (int) $event['going_count'], $hosting));

$page = 'my-events';
$pageTitle = 'My events — ' . APP_NAME;
require __DIR__ . '/includes/header.php';
?>

<section class="page-head page-head--compact">
    <div class="container page-head__inner">
        <div>
            <span class="eyebrow"><?= icon('ticket', 15) ?> Your calendar</span>
            <h1>My events</h1>
            <p class="page-head__lede">Everything you are going to, and everything you are running.</p>
        </div>
        <a class="btn btn--primary" href="create-event.php"><?= icon('plus', 17) ?> Host an event</a>
    </div>
</section>

<div class="container">
    <div class="stat-strip">
        <div class="stat-strip__item">
            <strong data-stat-going><?= count($goingUpcoming) ?></strong>
            <span>Upcoming RSVPs</span>
        </div>
        <div class="stat-strip__item">
            <strong><?= count($hosting) ?></strong>
            <span>Events hosted</span>
        </div>
        <div class="stat-strip__item">
            <strong><?= (int) $totalSignups ?></strong>
            <span>Total sign-ups</span>
        </div>
        <div class="stat-strip__item stat-strip__item--profile">
            <a class="link-arrow" href="profile.php">Edit profile <?= icon('arrow-right', 15) ?></a>
        </div>
    </div>

    <div class="tabs" role="tablist">
        <a class="tab<?= $tab === 'going' ? ' is-active' : '' ?>" href="my-events.php?tab=going" role="tab">
            <?= icon('ticket', 16) ?> Going
            <span class="tab__count"><?= count($going) ?></span>
        </a>
        <a class="tab<?= $tab === 'hosting' ? ' is-active' : '' ?>" href="my-events.php?tab=hosting" role="tab">
            <?= icon('calendar', 16) ?> Hosting
            <span class="tab__count"><?= count($hosting) ?></span>
        </a>
    </div>

    <?php if ($tab === 'going'): ?>
        <?php if ($going): ?>
            <div class="manage-list" data-going-list>
                <?php foreach ($going as $event): ?>
                    <article class="manage-row<?= $event['is_past'] ? ' is-past' : '' ?>" data-going-event="<?= (int) $event['id'] ?>">
                        <a class="manage-row__date" href="event.php?id=<?= (int) $event['id'] ?>"
                           style="--cat-color: <?= e($event['category_color']) ?>">
                            <strong><?= e($event['date_number']) ?></strong>
                            <small><?= e($event['month_label']) ?></small>
                        </a>

                        <div class="manage-row__body">
                            <div class="manage-row__kicker">
                                <span class="pill pill--cat" style="--cat: <?= e($event['category_color']) ?>">
                                    <?= icon($event['category_icon'], 12) ?> <?= e($event['category_name']) ?>
                                </span>
                                <?php if ($event['is_past']): ?>
                                    <span class="pill pill--muted">Past</span>
                                <?php else: ?>
                                    <span class="pill pill--live"><?= icon('clock', 12) ?> <?= e($event['day_label']) ?></span>
                                <?php endif; ?>
                            </div>
                            <h3><a href="event.php?id=<?= (int) $event['id'] ?>"><?= e($event['title']) ?></a></h3>
                            <p class="manage-row__meta">
                                <?= icon('clock', 14) ?> <?= e($event['time_label']) ?>
                                <span class="dot"></span>
                                <?= icon('map-pin', 14) ?> <?= e($event['location']) ?>
                            </p>
                        </div>

                        <div class="manage-row__side">
                            <span class="manage-row__count">
                                <strong><?= (int) $event['going_count'] ?></strong> / <?= (int) $event['capacity'] ?> going
                            </span>
                            <div class="manage-row__actions">
                                <a class="btn btn--ghost btn--sm" href="event.php?id=<?= (int) $event['id'] ?>">View</a>
                                <?php if (!$event['is_started']): ?>
                                    <button type="button" class="btn btn--ghost btn--sm" data-cancel-rsvp="<?= (int) $event['id'] ?>">
                                        Cancel
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= icon('ticket', 36) ?>
                <h3>No RSVPs yet</h3>
                <p>Find something that sounds fun — your calendar is wide open.</p>
                <a class="btn btn--primary" href="events.php">Browse events</a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($hosting): ?>
            <div class="manage-list" data-hosting-list>
                <?php foreach ($hosting as $event): ?>
                    <article class="manage-row<?= $event['is_past'] ? ' is-past' : '' ?>" data-hosted-event="<?= (int) $event['id'] ?>">
                        <a class="manage-row__date" href="event.php?id=<?= (int) $event['id'] ?>"
                           style="--cat-color: <?= e($event['category_color']) ?>">
                            <strong><?= e($event['date_number']) ?></strong>
                            <small><?= e($event['month_label']) ?></small>
                        </a>

                        <div class="manage-row__body">
                            <div class="manage-row__kicker">
                                <span class="pill pill--cat" style="--cat: <?= e($event['category_color']) ?>">
                                    <?= icon($event['category_icon'], 12) ?> <?= e($event['category_name']) ?>
                                </span>
                                <?php if ($event['is_past']): ?>
                                    <span class="pill pill--muted">Past</span>
                                <?php elseif ($event['is_full']): ?>
                                    <span class="pill pill--warn">Full</span>
                                <?php else: ?>
                                    <span class="pill pill--live"><?= (int) $event['spots_left'] ?> spots left</span>
                                <?php endif; ?>
                            </div>
                            <h3><a href="event.php?id=<?= (int) $event['id'] ?>"><?= e($event['title']) ?></a></h3>
                            <p class="manage-row__meta">
                                <?= icon('clock', 14) ?> <?= e($event['time_label']) ?>
                                <span class="dot"></span>
                                <?= icon('map-pin', 14) ?> <?= e($event['location']) ?>
                            </p>
                        </div>

                        <div class="manage-row__side">
                            <span class="manage-row__count">
                                <strong><?= (int) $event['going_count'] ?></strong> / <?= (int) $event['capacity'] ?> going
                            </span>
                            <div class="manage-row__actions">
                                <a class="btn btn--ghost btn--sm" href="event.php?id=<?= (int) $event['id'] ?>">View</a>
                                <a class="btn btn--ghost btn--sm" href="edit-event.php?id=<?= (int) $event['id'] ?>">Edit</a>
                                <button type="button" class="btn btn--danger-outline btn--sm" data-delete-event="<?= (int) $event['id'] ?>">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= icon('calendar', 36) ?>
                <h3>You have not hosted anything yet</h3>
                <p>Got an idea? A study break, a pickup game, a film night — post it and see who shows up.</p>
                <a class="btn btn--primary" href="create-event.php">Host your first event</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
