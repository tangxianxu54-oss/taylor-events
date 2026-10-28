<?php
/**
 * 我的预约：我报名的 workshop / 我主办的 workshop
 */
require __DIR__ . '/includes/init.php';
require_login();

$user = current_user();
$tab = (string) ($_GET['tab'] ?? 'booked');
$tab = in_array($tab, ['booked', 'hosting'], true) ? $tab : 'booked';

$booked = user_booked_workshops((int) $user['id']);
$hosting = user_hosted_workshops((int) $user['id']);

$upcomingBooked = array_values(array_filter($booked, static fn(array $item): bool => !$item['is_past']));
$totalBookedSeats = array_sum(array_map(static fn(array $item): int => (int) $item['going_count'], $hosting));

$page = 'my-bookings';
$pageTitle = 'My bookings — ' . APP_NAME;
require __DIR__ . '/includes/header.php';
?>

<section class="page-head page-head--compact">
    <div class="container page-head__inner">
        <div>
            <span class="eyebrow"><?= icon('presentation', 15) ?> Workshop &amp; seminar booking</span>
            <h1>My bookings</h1>
            <p class="page-head__lede">Your reserved seats, booking codes, and the workshops you host.</p>
        </div>
        <a class="btn btn--primary" href="create-workshop.php"><?= icon('plus', 17) ?> Host a workshop</a>
    </div>
</section>

<div class="container">
    <div class="stat-strip">
        <div class="stat-strip__item">
            <strong data-stat-booked><?= count($upcomingBooked) ?></strong>
            <span>Upcoming bookings</span>
        </div>
        <div class="stat-strip__item">
            <strong><?= count($hosting) ?></strong>
            <span>Workshops hosted</span>
        </div>
        <div class="stat-strip__item">
            <strong><?= (int) $totalBookedSeats ?></strong>
            <span>Seats booked in yours</span>
        </div>
        <div class="stat-strip__item stat-strip__item--profile">
            <a class="link-arrow" href="workshops.php">Find a workshop <?= icon('arrow-right', 15) ?></a>
        </div>
    </div>

    <div class="tabs" role="tablist">
        <a class="tab<?= $tab === 'booked' ? ' is-active' : '' ?>" href="my-bookings.php?tab=booked" role="tab">
            <?= icon('ticket', 16) ?> Booked
            <span class="tab__count"><?= count($booked) ?></span>
        </a>
        <a class="tab<?= $tab === 'hosting' ? ' is-active' : '' ?>" href="my-bookings.php?tab=hosting" role="tab">
            <?= icon('presentation', 16) ?> Hosting
            <span class="tab__count"><?= count($hosting) ?></span>
        </a>
    </div>

    <?php if ($tab === 'booked'): ?>
        <?php if ($booked): ?>
            <div class="manage-list" data-booked-list>
                <?php foreach ($booked as $item): ?>
                    <article class="manage-row<?= $item['is_past'] ? ' is-past' : '' ?>" data-booked-workshop="<?= (int) $item['id'] ?>">
                        <a class="manage-row__date" href="workshop.php?id=<?= (int) $item['id'] ?>"
                           style="--cat-color: <?= e($item['topic_color']) ?>">
                            <strong><?= e($item['date_number']) ?></strong>
                            <small><?= e($item['month_label']) ?></small>
                        </a>

                        <div class="manage-row__body">
                            <div class="manage-row__kicker">
                                <span class="pill pill--cat" style="--cat: <?= e($item['topic_color']) ?>">
                                    <?= icon($item['topic_icon'], 12) ?> <?= e($item['topic_name']) ?>
                                </span>
                                <?php if ($item['is_past']): ?>
                                    <span class="pill pill--muted">Past</span>
                                <?php else: ?>
                                    <span class="pill pill--live"><?= icon('clock', 12) ?> <?= e($item['day_label']) ?></span>
                                <?php endif; ?>
                                <span class="pill pill--code"><?= icon('ticket', 12) ?> <?= e($item['booking_code']) ?></span>
                            </div>
                            <h3><a href="workshop.php?id=<?= (int) $item['id'] ?>"><?= e($item['title']) ?></a></h3>
                            <p class="manage-row__meta">
                                <?= icon('clock', 14) ?> <?= e($item['time_label']) ?>
                                <span class="dot"></span>
                                <?= icon('microphone', 14) ?> <?= e($item['speaker']) ?>
                                <span class="dot"></span>
                                <?= icon('map-pin', 14) ?> <?= e($item['location']) ?>
                            </p>
                        </div>

                        <div class="manage-row__side">
                            <span class="manage-row__count">
                                <strong><?= (int) $item['going_count'] ?></strong> / <?= (int) $item['capacity'] ?> booked
                            </span>
                            <div class="manage-row__actions">
                                <a class="btn btn--ghost btn--sm" href="workshop.php?id=<?= (int) $item['id'] ?>">View</a>
                                <?php if (!$item['is_started']): ?>
                                    <button type="button" class="btn btn--ghost btn--sm" data-cancel-booking="<?= (int) $item['id'] ?>">
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
                <?= icon('presentation', 36) ?>
                <h3>No workshop bookings yet</h3>
                <p>Pick a topic you want to get better at — seats are small and go fast.</p>
                <a class="btn btn--primary" href="workshops.php">Browse workshops</a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php if ($hosting): ?>
            <div class="manage-list" data-hosting-list>
                <?php foreach ($hosting as $item): ?>
                    <article class="manage-row<?= $item['is_past'] ? ' is-past' : '' ?>" data-hosted-workshop="<?= (int) $item['id'] ?>">
                        <a class="manage-row__date" href="workshop.php?id=<?= (int) $item['id'] ?>"
                           style="--cat-color: <?= e($item['topic_color']) ?>">
                            <strong><?= e($item['date_number']) ?></strong>
                            <small><?= e($item['month_label']) ?></small>
                        </a>

                        <div class="manage-row__body">
                            <div class="manage-row__kicker">
                                <span class="pill pill--cat" style="--cat: <?= e($item['topic_color']) ?>">
                                    <?= icon($item['topic_icon'], 12) ?> <?= e($item['topic_name']) ?>
                                </span>
                                <?php if ($item['is_past']): ?>
                                    <span class="pill pill--muted">Past</span>
                                <?php elseif ($item['is_full']): ?>
                                    <span class="pill pill--warn">Full</span>
                                <?php else: ?>
                                    <span class="pill pill--live"><?= (int) $item['spots_left'] ?> seats left</span>
                                <?php endif; ?>
                            </div>
                            <h3><a href="workshop.php?id=<?= (int) $item['id'] ?>"><?= e($item['title']) ?></a></h3>
                            <p class="manage-row__meta">
                                <?= icon('clock', 14) ?> <?= e($item['time_label']) ?>
                                <span class="dot"></span>
                                <?= icon('microphone', 14) ?> <?= e($item['speaker']) ?>
                            </p>
                        </div>

                        <div class="manage-row__side">
                            <span class="manage-row__count">
                                <strong><?= (int) $item['going_count'] ?></strong> / <?= (int) $item['capacity'] ?> booked
                            </span>
                            <div class="manage-row__actions">
                                <a class="btn btn--ghost btn--sm" href="workshop.php?id=<?= (int) $item['id'] ?>">View</a>
                                <a class="btn btn--ghost btn--sm" href="edit-workshop.php?id=<?= (int) $item['id'] ?>">Edit</a>
                                <button type="button" class="btn btn--danger-outline btn--sm" data-delete-workshop="<?= (int) $item['id'] ?>">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= icon('presentation', 36) ?>
                <h3>You have not hosted a workshop yet</h3>
                <p>Teach something you know well — a tool, a skill, a research method. Small sessions fill fast.</p>
                <a class="btn btn--primary" href="create-workshop.php">Host your first workshop</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
