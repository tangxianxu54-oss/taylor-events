<?php
/**
 * Workshop & Seminar 列表：服务端首屏渲染 + AJAX 筛选
 */
require __DIR__ . '/includes/init.php';

$topics = get_categories();
$slugs = array_column($topics, 'slug');

$when = (string) ($_GET['when'] ?? 'upcoming');
$when = in_array($when, ['upcoming', 'past', 'all'], true) ? $when : 'upcoming';
$sort = (string) ($_GET['sort'] ?? 'soonest');
$sort = in_array($sort, ['soonest', 'popular', 'newest'], true) ? $sort : 'soonest';
$topic = (string) ($_GET['topic'] ?? 'all');
$topic = in_array($topic, $slugs, true) ? $topic : 'all';

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'topic' => $topic,
    'when' => $when,
    'sort' => $sort,
];

$results = search_workshops($filters, 9, 0);
$topicNames = array_column($topics, 'name', 'slug');

$page = 'workshops';
$pageTitle = 'Workshops & seminars — ' . APP_NAME;
$pageDescription = 'Book a seat at Taylor University workshops, seminars and skill sessions.';
$scripts = ['assets/js/workshops.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="container page-head__inner">
        <div>
            <span class="eyebrow"><?= icon('presentation', 15) ?> Workshop &amp; seminar booking</span>
            <h1>Learn something new</h1>
            <p class="page-head__lede">Small-group workshops and seminars run by students, staff and guest speakers. Reserve a seat — you will get a booking code.</p>
        </div>
        <div class="page-head__aside">
            <span class="page-head__count" data-results-count><?= (int) $results['total'] ?></span>
            <span>workshops open for booking</span>
        </div>
    </div>
</section>

<div class="container">
    <form class="filter-bar" data-workshops-form role="search">
        <div class="filter-bar__search">
            <?= icon('search', 19) ?>
            <input type="search" name="q" value="<?= e($filters['q']) ?>"
                   placeholder="Search workshops, speakers, topics…" aria-label="Search workshops" autocomplete="off">
        </div>
        <label class="filter-bar__select">
            <span class="filter-bar__label"><?= icon('calendar', 15) ?> When</span>
            <select name="when">
                <option value="upcoming"<?= sel('upcoming', $when) ?>>Upcoming</option>
                <option value="past"<?= sel('past', $when) ?>>Past sessions</option>
                <option value="all"<?= sel('all', $when) ?>>All time</option>
            </select>
            <?= icon('chevron-down', 15) ?>
        </label>
        <label class="filter-bar__select">
            <span class="filter-bar__label"><?= icon('filter', 15) ?> Sort</span>
            <select name="sort">
                <option value="soonest"<?= sel('soonest', $sort) ?>>Happening soonest</option>
                <option value="popular"<?= sel('popular', $sort) ?>>Most booked</option>
                <option value="newest"<?= sel('newest', $sort) ?>>Recently added</option>
            </select>
            <?= icon('chevron-down', 15) ?>
        </label>
    </form>

    <div class="chip-row" data-topic-chips>
        <button type="button" class="chip<?= $topic === 'all' ? ' is-active' : '' ?>" data-topic="all">
            <?= icon('grid', 14) ?> All topics
        </button>
        <?php foreach ($topics as $item): ?>
            <button type="button"
                    class="chip<?= $topic === $item['slug'] ? ' is-active' : '' ?>"
                    data-topic="<?= e($item['slug']) ?>"
                    style="--cat: <?= e($item['color']) ?>">
                <i class="chip__dot"></i><?= e($item['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="results-bar">
        <span>
            <strong data-filter-label><?= $topic === 'all' ? 'All topics' : e($topicNames[$topic]) ?></strong>
            ·
            <span data-when-label><?= $when === 'past' ? 'Past sessions' : ($when === 'all' ? 'All time' : 'Upcoming') ?></span>
        </span>
        <button type="button" class="results-bar__reset" data-reset hidden>Reset filters</button>
    </div>

    <div class="event-grid" data-workshops-grid>
        <?php foreach ($results['items'] as $workshop): ?>
            <?php include __DIR__ . '/includes/workshop-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="empty-state" data-workshops-empty <?= $results['items'] ? 'hidden' : '' ?>>
        <?= icon('presentation', 36) ?>
        <h3>No workshops match those filters</h3>
        <p>Try a different topic, clear your search, or check back soon.</p>
        <button type="button" class="btn btn--primary" data-reset>Reset filters</button>
    </div>

    <div class="load-more">
        <button type="button" class="btn btn--ghost" data-load-more <?= $results['has_more'] ? '' : 'hidden' ?>>
            Load more workshops <?= icon('chevron-down', 16) ?>
        </button>
    </div>

    <section class="workshop-host-cta">
        <div>
            <span class="eyebrow"><?= icon('sparkles', 15) ?> Run your own session?</span>
            <h2>Share a skill with campus</h2>
            <p>Teaching a software tool, running a portfolio review, or hosting a research seminar? Publish it in two minutes.</p>
        </div>
        <a class="btn btn--primary" href="create-workshop.php"><?= icon('plus', 17) ?> Host a workshop</a>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
