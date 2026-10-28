<?php
/**
 * Workshop / Seminar 详情与预约页
 */
require __DIR__ . '/includes/init.php';

$id = (int) ($_GET['id'] ?? 0);
$workshop = $id > 0 ? fetch_workshop($id) : null;

if (!$workshop) {
    http_response_code(404);
    $page = 'workshop';
    $pageTitle = 'Workshop not found — ' . APP_NAME;
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="state-page">
        <div class="container">
            <span class="state-page__icon"><?= icon('presentation', 40) ?></span>
            <h1>This session is not on the schedule</h1>
            <p>The workshop you are looking for does not exist, or the host has taken it down.</p>
            <div class="state-page__actions">
                <a class="btn btn--primary" href="workshops.php">Browse workshops</a>
                <a class="btn btn--ghost" href="index.php">Back home</a>
            </div>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();
$isHost = $user && (int) $workshop['host_id'] === (int) $user['id'];
$booking = $user ? workshop_booking((int) $workshop['id'], (int) $user['id']) : null;
$attendees = workshop_attendees((int) $workshop['id']);

$hostStmt = db()->prepare('SELECT id, name, avatar, major, year, bio FROM users WHERE id = ?');
$hostStmt->execute([(int) $workshop['host_id']]);
$host = $hostStmt->fetch() ?: [
    'id' => $workshop['host_id'], 'name' => $workshop['host_name'],
    'avatar' => $workshop['host_avatar'], 'major' => '', 'year' => '', 'bio' => '',
];

$similar = search_workshops([
    'topic' => $workshop['topic_slug'],
    'when' => 'upcoming',
    'exclude' => (int) $workshop['id'],
], 3)['items'];

$modeIcon = match ($workshop['mode']) {
    'online', 'hybrid' => 'video',
    default => 'map-pin',
};

$page = 'workshop';
$pageTitle = $workshop['title'] . ' — ' . APP_NAME;
$pageDescription = truncate($workshop['description'], 155);
require __DIR__ . '/includes/header.php';
?>

<section class="event-hero" style="--cat-color: <?= e($workshop['topic_color']) ?>">
    <div class="event-hero__media">
        <img src="<?= e($workshop['cover_url']) ?>" alt="" onerror="this.remove()">
    </div>
    <div class="event-hero__overlay" aria-hidden="true"></div>
    <div class="container event-hero__content">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="workshops.php">Workshops</a>
            <?= icon('chevron-down', 13) ?>
            <a href="workshops.php?topic=<?= e($workshop['topic_slug']) ?>"><?= e($workshop['topic_name']) ?></a>
        </nav>
        <span class="event-hero__cat"><?= icon('presentation', 14) ?> <?= e($workshop['mode_label']) ?></span>
        <h1><?= e($workshop['title']) ?></h1>
        <div class="event-hero__meta">
            <span><?= icon('calendar', 17) ?> <?= e($workshop['full_date_label']) ?></span>
            <span><?= icon('clock', 17) ?> <?= e($workshop['time_label']) ?></span>
            <span><?= icon('microphone', 17) ?> <?= e($workshop['speaker']) ?></span>
        </div>
    </div>
</section>

<div class="container event-layout">
    <div class="event-main">
        <section class="panel">
            <div class="panel__head">
                <h2>About this session</h2>
                <span class="pill"><?= icon('clock', 13) ?> <?= e($workshop['duration_label']) ?></span>
            </div>
            <div class="prose"><?= nl2br(e($workshop['description'])) ?></div>

            <div class="event-facts">
                <div class="event-fact">
                    <?= icon('calendar', 20) ?>
                    <div><span>Date</span><strong><?= e($workshop['full_date_label']) ?></strong></div>
                </div>
                <div class="event-fact">
                    <?= icon('clock', 20) ?>
                    <div><span>Time</span><strong><?= e($workshop['time_label']) ?></strong></div>
                </div>
                <div class="event-fact">
                    <?= icon($modeIcon, 20) ?>
                    <div><span>Format</span><strong><?= e($workshop['mode_label']) ?></strong></div>
                </div>
                <div class="event-fact">
                    <?= icon('map-pin', 20) ?>
                    <div><span>Where</span><strong><?= e($workshop['location']) ?></strong></div>
                </div>
                <div class="event-fact">
                    <?= icon('microphone', 20) ?>
                    <div><span>Speaker</span><strong><?= e($workshop['speaker']) ?></strong></div>
                </div>
                <div class="event-fact">
                    <?= icon('users', 20) ?>
                    <div><span>Seats</span><strong><?= (int) $workshop['capacity'] ?> total</strong></div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head">
                <h2>Who is booked</h2>
                <span class="muted"><?= (int) $workshop['going_count'] ?> of <?= (int) $workshop['capacity'] ?> seats taken</span>
            </div>

            <?php if ($attendees): ?>
                <div class="attendee-list">
                    <?php foreach ($attendees as $attendee): ?>
                        <div class="attendee">
                            <span class="avatar">
                                <?php if (avatar_url($attendee['avatar'])): ?>
                                    <img src="<?= e(avatar_url($attendee['avatar'])) ?>" alt="">
                                <?php else: ?>
                                    <?= e(initials($attendee['name'])) ?>
                                <?php endif; ?>
                            </span>
                            <div class="attendee__text">
                                <strong><?= e($attendee['name']) ?></strong>
                                <span><?= e(trim(($attendee['major'] ?? '') . ' · ' . ($attendee['year'] ?? ''), ' ·')) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ((int) $workshop['going_count'] > count($attendees)): ?>
                    <p class="muted attendee-more">
                        + <?= (int) $workshop['going_count'] - count($attendees) ?> more students booked
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p class="muted">No bookings yet. Be the first to reserve a seat.</p>
            <?php endif; ?>
        </section>

        <section class="panel panel--organizer">
            <div class="organizer">
                <span class="avatar avatar--lg">
                    <?php if (avatar_url($host['avatar'])): ?>
                        <img src="<?= e(avatar_url($host['avatar'])) ?>" alt="">
                    <?php else: ?>
                        <?= e(initials($host['name'])) ?>
                    <?php endif; ?>
                </span>
                <div>
                    <span class="eyebrow">Hosted by</span>
                    <h3><?= e($host['name']) ?></h3>
                    <p class="muted"><?= e(trim(($host['major'] ?? '') . ' · ' . ($host['year'] ?? ''), ' ·')) ?></p>
                </div>
            </div>
            <?php if (!empty($host['bio'])): ?>
                <p class="organizer__bio"><?= e($host['bio']) ?></p>
            <?php endif; ?>
        </section>
    </div>

    <aside class="event-side">
        <div class="booking-card" data-booking-card>
            <?php if ($workshop['is_past']): ?>
                <div class="booking-card__flag">Session finished</div>
            <?php endif; ?>
            <div class="booking-card__date" style="--cat-color: <?= e($workshop['topic_color']) ?>">
                <span><?= e($workshop['day_label']) ?></span>
                <strong><?= e($workshop['date_number']) ?></strong>
                <small><?= e($workshop['month_label']) ?></small>
            </div>

            <div class="booking-card__rows">
                <div class="booking-card__row">
                    <?= icon('clock', 18) ?>
                    <div><span>Time</span><strong><?= e($workshop['time_label']) ?></strong></div>
                </div>
                <div class="booking-card__row">
                    <?= icon($modeIcon, 18) ?>
                    <div><span><?= e($workshop['mode_label']) ?></span><strong><?= e($workshop['location']) ?></strong></div>
                </div>
                <div class="booking-card__row">
                    <?= icon('microphone', 18) ?>
                    <div><span>Speaker</span><strong><?= e($workshop['speaker']) ?></strong></div>
                </div>
            </div>

            <div class="booking-card__capacity">
                <div class="booking-card__capacity-text">
                    <span><strong data-going-count><?= (int) $workshop['going_count'] ?></strong> booked</span>
                    <span data-spots-left><?= (int) $workshop['spots_left'] ?> seats left</span>
                </div>
                <div class="progress<?= $workshop['is_full'] ? ' progress--full' : '' ?>" data-progress="">
                    <span data-fill style="width: <?= (int) $workshop['fill_percent'] ?>%"></span>
                </div>
            </div>

            <div class="booking-card__actions" data-booking-actions>
                <?= render_workshop_booking_button($workshop, $user, $booking) ?>
            </div>

            <?php if (!$user): ?>
                <p class="booking-card__note">New here? <a href="register.php">Create a free account</a> with your Taylor email.</p>
            <?php elseif ($isHost): ?>
                <p class="booking-card__note">You are hosting this session. Share the page with your classmates.</p>
            <?php elseif ($booking && $booking['status'] === 'booked'): ?>
                <p class="booking-card__note">Keep your booking code — show it at the door.</p>
            <?php else: ?>
                <p class="booking-card__note">Free for Taylor students · cancel anytime before it starts.</p>
            <?php endif; ?>

            <button type="button" class="booking-card__share" data-copy-link>
                <?= icon('external-link', 15) ?> Copy workshop link
            </button>
        </div>

        <div class="side-note">
            <?= icon('sparkles', 18) ?>
            <div>
                <strong>Seats are limited</strong>
                <p>Workshops stay small on purpose — book early if you want a spot.</p>
            </div>
        </div>
    </aside>
</div>

<?php if ($similar): ?>
    <section class="section section--cream">
        <div class="container">
            <div class="section__head">
                <div>
                    <span class="eyebrow">More like this</span>
                    <h2>Other <?= e($workshop['topic_name']) ?> workshops</h2>
                </div>
                <a class="link-arrow" href="workshops.php?topic=<?= e($workshop['topic_slug']) ?>">
                    View topic <?= icon('arrow-right', 16) ?>
                </a>
            </div>
            <div class="event-grid">
                <?php foreach ($similar as $workshop): ?>
                    <?php include __DIR__ . '/includes/workshop-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
