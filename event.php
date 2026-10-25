<?php
/**
 * 活动详情页
 */
require __DIR__ . '/includes/init.php';

$id = (int) ($_GET['id'] ?? 0);
$event = $id > 0 ? fetch_event($id) : null;

if (!$event) {
    http_response_code(404);
    $page = 'event';
    $pageTitle = 'Event not found — ' . APP_NAME;
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="state-page">
        <div class="container">
            <span class="state-page__icon"><?= icon('calendar', 40) ?></span>
            <h1>This event has left the building</h1>
            <p>The event you are looking for does not exist, or it has been removed by its host.</p>
            <div class="state-page__actions">
                <a class="btn btn--primary" href="events.php">Browse events</a>
                <a class="btn btn--ghost" href="index.php">Back home</a>
            </div>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$user = current_user();
$isOrganizer = $user && (int) $event['organizer_id'] === (int) $user['id'];
$isRegistered = $user ? user_is_registered((int) $event['id'], (int) $user['id']) : false;
$attendees = event_attendees((int) $event['id']);

$orgStmt = db()->prepare('SELECT id, name, avatar, major, year, bio FROM users WHERE id = ?');
$orgStmt->execute([(int) $event['organizer_id']]);
$organizer = $orgStmt->fetch() ?: [
    'id' => $event['organizer_id'], 'name' => $event['organizer_name'],
    'avatar' => $event['organizer_avatar'], 'major' => '', 'year' => '', 'bio' => '',
];

$similar = search_events([
    'category' => $event['category_slug'],
    'when' => 'upcoming',
    'exclude' => (int) $event['id'],
], 3)['items'];

$page = 'event';
$pageTitle = $event['title'] . ' — ' . APP_NAME;
$pageDescription = truncate($event['description'], 155);
require __DIR__ . '/includes/header.php';
?>

<section class="event-hero" style="--cat-color: <?= e($event['category_color']) ?>">
    <div class="event-hero__media">
        <img src="<?= e($event['cover_url']) ?>" alt="" onerror="this.remove()">
    </div>
    <div class="event-hero__overlay" aria-hidden="true"></div>
    <div class="container event-hero__content">
        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="events.php">Events</a>
            <?= icon('chevron-down', 13) ?>
            <a href="events.php?category=<?= e($event['category_slug']) ?>"><?= e($event['category_name']) ?></a>
        </nav>
        <span class="event-hero__cat"><?= icon($event['category_icon'], 14) ?> <?= e($event['category_name']) ?></span>
        <h1><?= e($event['title']) ?></h1>
        <div class="event-hero__meta">
            <span><?= icon('calendar', 17) ?> <?= e($event['full_date_label']) ?></span>
            <span><?= icon('clock', 17) ?> <?= e($event['time_label']) ?></span>
            <span><?= icon('map-pin', 17) ?> <?= e($event['location']) ?></span>
        </div>
    </div>
</section>

<div class="container event-layout">
    <div class="event-main">
        <section class="panel">
            <div class="panel__head">
                <h2>About this event</h2>
                <span class="pill"><?= icon('clock', 13) ?> <?= e($event['duration_label']) ?></span>
            </div>
            <div class="prose"><?= nl2br(e($event['description'])) ?></div>

            <div class="event-facts">
                <div class="event-fact">
                    <?= icon('calendar', 20) ?>
                    <div><span>Date</span><strong><?= e($event['full_date_label']) ?></strong></div>
                </div>
                <div class="event-fact">
                    <?= icon('clock', 20) ?>
                    <div><span>Time</span><strong><?= e($event['time_label']) ?></strong></div>
                </div>
                <div class="event-fact">
                    <?= icon('map-pin', 20) ?>
                    <div><span>Location</span><strong><?= e($event['location']) ?></strong></div>
                </div>
                <div class="event-fact">
                    <?= icon('users', 20) ?>
                    <div><span>Capacity</span><strong><?= (int) $event['capacity'] ?> people</strong></div>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head">
                <h2>Who is going</h2>
                <span class="muted"><?= (int) $event['going_count'] ?> of <?= (int) $event['capacity'] ?> spots filled</span>
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
                <?php if ((int) $event['going_count'] > count($attendees)): ?>
                    <p class="muted attendee-more">
                        + <?= (int) $event['going_count'] - count($attendees) ?> more students are going
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p class="muted">No one has signed up yet. Be the first to claim a spot.</p>
            <?php endif; ?>
        </section>

        <section class="panel panel--organizer">
            <div class="organizer">
                <span class="avatar avatar--lg">
                    <?php if (avatar_url($organizer['avatar'])): ?>
                        <img src="<?= e(avatar_url($organizer['avatar'])) ?>" alt="">
                    <?php else: ?>
                        <?= e(initials($organizer['name'])) ?>
                    <?php endif; ?>
                </span>
                <div>
                    <span class="eyebrow">Hosted by</span>
                    <h3><?= e($organizer['name']) ?></h3>
                    <p class="muted"><?= e(trim(($organizer['major'] ?? '') . ' · ' . ($organizer['year'] ?? ''), ' ·')) ?></p>
                </div>
            </div>
            <?php if (!empty($organizer['bio'])): ?>
                <p class="organizer__bio"><?= e($organizer['bio']) ?></p>
            <?php endif; ?>
        </section>
    </div>

    <aside class="event-side">
        <div class="booking-card" data-booking-card>
            <?php if ($event['is_past']): ?>
                <div class="booking-card__flag">Past event</div>
            <?php endif; ?>
            <div class="booking-card__date" style="--cat-color: <?= e($event['category_color']) ?>">
                <span><?= e($event['day_label']) ?></span>
                <strong><?= e($event['date_number']) ?></strong>
                <small><?= e($event['month_label']) ?></small>
            </div>

            <div class="booking-card__rows">
                <div class="booking-card__row">
                    <?= icon('clock', 18) ?>
                    <div><span>Time</span><strong><?= e($event['time_label']) ?></strong></div>
                </div>
                <div class="booking-card__row">
                    <?= icon('map-pin', 18) ?>
                    <div><span>Location</span><strong><?= e($event['location']) ?></strong></div>
                </div>
            </div>

            <div class="booking-card__capacity">
                <div class="booking-card__capacity-text">
                    <span><strong data-going-count><?= (int) $event['going_count'] ?></strong> going</span>
                    <span data-spots-left><?= (int) $event['spots_left'] ?> spots left</span>
                </div>
                <div class="progress<?= $event['is_full'] ? ' progress--full' : '' ?>" data-progress="">
                    <span data-fill style="width: <?= (int) $event['fill_percent'] ?>%"></span>
                </div>
            </div>

            <div class="booking-card__actions" data-booking-actions>
                <?= render_booking_button($event, $user, $isRegistered) ?>
            </div>

            <?php if (!$user): ?>
                <p class="booking-card__note">New here? <a href="register.php">Create a free account</a> with your Taylor email.</p>
            <?php elseif ($isOrganizer): ?>
                <p class="booking-card__note">You are hosting. Share this page with your friends.</p>
            <?php else: ?>
                <p class="booking-card__note">Free for Taylor students · cancel anytime.</p>
            <?php endif; ?>

            <button type="button" class="booking-card__share" data-copy-link>
                <?= icon('external-link', 15) ?> Copy event link
            </button>
        </div>

        <div class="side-note">
            <?= icon('sparkles', 18) ?>
            <div>
                <strong>Bring a friend</strong>
                <p>Events fill up fast — share the link before spots run out.</p>
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
                    <h2>Other <?= e($event['category_name']) ?> events</h2>
                </div>
                <a class="link-arrow" href="events.php?category=<?= e($event['category_slug']) ?>">
                    View category <?= icon('arrow-right', 16) ?>
                </a>
            </div>
            <div class="event-grid">
                <?php foreach ($similar as $event): ?>
                    <?php include __DIR__ . '/includes/event-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
