<?php
/**
 * 活动列表：服务端首屏渲染 + JS 动态筛选
 */
require __DIR__ . '/includes/init.php';

$categories = get_categories();
$slugs = array_column($categories, 'slug');

$when = (string) ($_GET['when'] ?? 'upcoming');
$when = in_array($when, ['upcoming', 'past', 'all'], true) ? $when : 'upcoming';
$sort = (string) ($_GET['sort'] ?? 'soonest');
$sort = in_array($sort, ['soonest', 'popular', 'newest'], true) ? $sort : 'soonest';
$category = (string) ($_GET['category'] ?? 'all');
$category = in_array($category, $slugs, true) ? $category : 'all';

$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category' => $category,
    'when' => $when,
    'sort' => $sort,
];

$results = search_events($filters, 9, 0);
$categoryNames = array_column($categories, 'name', 'slug');

$page = 'events';
$pageTitle = 'Browse events — ' . APP_NAME;
$pageDescription = 'Filter upcoming Taylor University events by category, date and popularity.';
$scripts = ['assets/js/events.js'];
require __DIR__ . '/includes/header.php';
?>

<section class="page-head">
    <div class="container page-head__inner">
        <div>
            <span class="eyebrow"><?= icon('grid', 15) ?> Event directory</span>
            <h1>Browse campus events</h1>
            <p class="page-head__lede">Search by name, pick a category, or look back at what you missed.</p>
        </div>
        <div class="page-head__aside">
            <span class="page-head__count" data-results-count><?= (int) $results['total'] ?></span>
            <span>events match your filters</span>
        </div>
    </div>
</section>

<div class="container">
    <form class="filter-bar" id="event-filters" data-events-form role="search">
        <div class="filter-bar__search">
            <?= icon('search', 19) ?>
            <input type="search" name="q" value="<?= e($filters['q']) ?>"
                   placeholder="Search events, places, clubs…" aria-label="Search events" autocomplete="off">
        </div>
        <label class="filter-bar__select">
            <span class="filter-bar__label"><?= icon('calendar', 15) ?> When</span>
            <select name="when">
                <option value="upcoming"<?= sel('upcoming', $when) ?>>Upcoming</option>
                <option value="past"<?= sel('past', $when) ?>>Past events</option>
                <option value="all"<?= sel('all', $when) ?>>All time</option>
            </select>
            <?= icon('chevron-down', 15) ?>
        </label>
        <label class="filter-bar__select">
            <span class="filter-bar__label"><?= icon('filter', 15) ?> Sort</span>
            <select name="sort">
                <option value="soonest"<?= sel('soonest', $sort) ?>>Happening soonest</option>
                <option value="popular"<?= sel('popular', $sort) ?>>Most popular</option>
                <option value="newest"<?= sel('newest', $sort) ?>>Recently added</option>
            </select>
            <?= icon('chevron-down', 15) ?>
        </label>
    </form>

    <div class="chip-row" data-category-chips>
        <button type="button" class="chip<?= $category === 'all' ? ' is-active' : '' ?>" data-category="all">
            <?= icon('grid', 14) ?> All
        </button>
        <?php foreach ($categories as $item): ?>
            <button type="button"
                    class="chip<?= $category === $item['slug'] ? ' is-active' : '' ?>"
                    data-category="<?= e($item['slug']) ?>"
                    style="--cat: <?= e($item['color']) ?>">
                <i class="chip__dot"></i><?= e($item['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="results-bar">
        <span>
            <strong data-filter-label>
                <?= $category === 'all' ? 'All categories' : e($categoryNames[$category]) ?>
            </strong>
            ·
            <span data-when-label><?= $when === 'past' ? 'Past events' : ($when === 'all' ? 'All time' : 'Upcoming') ?></span>
        </span>
        <button type="button" class="results-bar__reset" data-reset hidden>Reset filters</button>
    </div>

    <div class="event-grid" data-events-grid>
        <?php foreach ($results['items'] as $event): ?>
            <?php include __DIR__ . '/includes/event-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <div class="empty-state" data-events-empty <?= $results['items'] ? 'hidden' : '' ?>>
        <?= icon('calendar', 36) ?>
        <h3>No events match those filters</h3>
        <p>Try a different category, clear your search, or look at past events.</p>
        <button type="button" class="btn btn--primary" data-reset>Reset filters</button>
    </div>

    <div class="load-more">
        <button type="button" class="btn btn--ghost" data-load-more <?= $results['has_more'] ? '' : 'hidden' ?>>
            Load more events <?= icon('chevron-down', 16) ?>
        </button>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
