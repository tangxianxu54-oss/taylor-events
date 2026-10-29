<?php
/**
 * 首页：大图 banner、分类色块、近期活动
 */
require __DIR__ . '/includes/init.php';

$categories = get_categories();
$stats = campus_stats();
$featured = search_events(['when' => 'upcoming', 'sort' => 'soonest'], 6)['items'];

$page = 'home';
$pageTitle = 'Taylor Events — Find your next campus moment';
$pageDescription = 'Discover, host and join events across Taylor University campus. Browse sports, arts, service, social and wellness events.';
require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="hero__media">
        <img src="assets/img/hero-campus.png" alt="Taylor University campus" onerror="this.remove()">
    </div>
    <div class="hero__overlay" aria-hidden="true"></div>

    <div class="container hero__content">
        <span class="hero__eyebrow"><?= icon('sparkles', 15) ?> Taylor University · Student life</span>
        <h1>Find your next<br><em>campus moment.</em></h1>
        <p class="hero__lede">
            Twenty-four hours a day, something is happening at Taylor. Browse what is coming up,
            save your spot, or put your own event on the map.
        </p>

        <form class="hero-search" method="get" action="events.php" role="search">
            <div class="hero-search__field">
                <?= icon('search', 19) ?>
                <input type="search" name="q" placeholder="Search events, places, clubs…" aria-label="Search events">
            </div>
            <div class="hero-search__select">
                <select name="category" aria-label="Category">
                    <option value="all">All categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= e($category['slug']) ?>"><?= e($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= icon('chevron-down', 16) ?>
            </div>
            <button class="btn btn--gold hero-search__btn" type="submit">
                Find events <?= icon('arrow-right', 17) ?>
            </button>
        </form>

        <div class="hero__stats">
            <div class="hero__stat">
                <strong><?= (int) $stats['upcoming'] ?></strong>
                <span>Upcoming events</span>
            </div>
            <div class="hero__stat">
                <strong><?= (int) $stats['signups'] ?></strong>
                <span>Spots claimed</span>
            </div>
            <div class="hero__stat">
                <strong><?= (int) $stats['hosts'] ?></strong>
                <span>Student hosts</span>
            </div>
            <a class="hero__stat hero__stat--link" href="create-event.php">
                <strong><?= icon('plus', 22) ?></strong>
                <span>Host an event</span>
            </a>
        </div>
    </div>
</section>

<section class="section" id="categories">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">Find your scene</span>
                <h2>Browse by category</h2>
            </div>
            <p>Six corners of campus life. Pick the one that feels like you — or try something new.</p>
        </div>

        <div class="cat-grid">
            <?php foreach ($categories as $category): ?>
                <a class="cat-block" href="events.php?category=<?= e($category['slug']) ?>" style="--cat: <?= e($category['color']) ?>">
                    <span class="cat-block__icon"><?= icon($category['icon'], 32) ?></span>
                    <span class="cat-block__name"><?= e($category['name']) ?></span>
                    <span class="cat-block__desc"><?= e($category['description']) ?></span>
                    <span class="cat-block__foot">
                        <span><?= (int) $category['event_count'] ?> upcoming</span>
                        <?= icon('arrow-right', 16) ?>
                    </span>
                    <span class="cat-block__ghost" aria-hidden="true"><?= icon($category['icon'], 120) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--cream">
    <div class="container">
        <div class="section__head">
            <div>
                <span class="eyebrow">On the calendar</span>
                <h2>Happening soon</h2>
            </div>
            <a class="link-arrow" href="events.php">All events <?= icon('arrow-right', 16) ?></a>
        </div>

        <?php if ($featured): ?>
            <div class="event-grid">
                <?php foreach ($featured as $event): ?>
                    <?php include __DIR__ . '/includes/event-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= icon('calendar', 34) ?>
                <h3>No upcoming events yet</h3>
                <p>Be the first to put something on the calendar.</p>
                <a class="btn btn--primary" href="create-event.php">Host an event</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="cta-banner">
            <div class="cta-banner__copy">
                <span class="eyebrow eyebrow--gold">For organizers</span>
                <h2>Have something worth<br>showing up for?</h2>
                <p>
                    Reserve a room, post your event, and watch the sign-ups roll in.
                    Every verified Taylor student can host — no club paperwork required.
                </p>
                <div class="cta-banner__actions">
                    <a class="btn btn--gold" href="create-event.php"><?= icon('plus', 17) ?> Host an event</a>
                    <a class="btn btn--outline-light" href="my-events.php">Manage my events</a>
                </div>
            </div>
            <div class="cta-banner__art" aria-hidden="true">
                <span class="float-chip float-chip--1"><?= icon('calendar', 18) ?> Fall Festival</span>
                <span class="float-chip float-chip--2"><?= icon('users', 18) ?> 128 going</span>
                <span class="float-chip float-chip--3"><?= icon('ticket', 18) ?> Reserve a spot</span>
                <span class="cta-banner__ring"></span>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="section__head section__head--center">
            <div>
                <span class="eyebrow">How it works</span>
                <h2>Three steps to a better semester</h2>
            </div>
        </div>
        <div class="steps">
            <div class="step">
                <span class="step__num">01</span>
                <h3>Discover</h3>
                <p>Filter by category, search by name, or just see what is happening this week.</p>
            </div>
            <div class="step">
                <span class="step__num">02</span>
                <h3>Reserve your spot</h3>
                <p>One click to RSVP. Your spot is saved and your calendar is one step lighter.</p>
            </div>
            <div class="step">
                <span class="step__num">03</span>
                <h3>Host your own</h3>
                <p>Verified students can publish events in under two minutes — capacity and RSVPs included.</p>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
