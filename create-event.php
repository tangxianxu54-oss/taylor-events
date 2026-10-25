<?php
/**
 * 创建活动
 */
require __DIR__ . '/includes/init.php';
require_verified();

$user = current_user();
$categories = get_categories();
$errors = [];

$form = [
    'title' => '',
    'category_id' => '',
    'description' => '',
    'location' => '',
    'start_at' => '',
    'end_at' => '',
    'capacity' => '50',
];

// 从分类页带入默认分类
if (!empty($_GET['category'])) {
    foreach ($categories as $category) {
        if ($category['slug'] === $_GET['category']) {
            $form['category_id'] = (string) $category['id'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $default = new DateTimeImmutable('tomorrow 18:00', new DateTimeZone(CAMPUS_TZ));
    $form['start_at'] = $default->format('Y-m-d\TH:i');
    $form['end_at'] = $default->modify('+2 hours')->format('Y-m-d\TH:i');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $form = event_form_values();
    [$errors, $startAt, $endAt] = validate_event_form($form, $categories);

    $cover = null;
    if (!$errors) {
        try {
            $cover = handle_image_upload($_FILES['cover'] ?? [], 'covers');
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    if (!$errors && $startAt && $endAt) {
        $stmt = db()->prepare(
            'INSERT INTO events (title, category_id, description, location, start_at, end_at, capacity, cover, organizer_id, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $form['title'],
            (int) $form['category_id'],
            $form['description'],
            $form['location'],
            $startAt,
            $endAt,
            (int) $form['capacity'],
            $cover,
            (int) $user['id'],
            'published',
            date('Y-m-d H:i:s'),
        ]);
        $eventId = (int) db()->lastInsertId();
        flash('success', 'Your event is live. Share the link and fill those seats.');
        redirect('event.php?id=' . $eventId);
    }
}

$page = 'create';
$pageTitle = 'Host an event — ' . APP_NAME;
$pageDescription = 'Publish a new event for the Taylor University community.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head page-head--compact">
    <div class="container page-head__inner">
        <div>
            <span class="eyebrow"><?= icon('sparkles', 15) ?> For organizers</span>
            <h1>Host an event</h1>
            <p class="page-head__lede">Fill in the details, pick a category, and publish. It takes about two minutes.</p>
        </div>
        <a class="btn btn--ghost btn--sm" href="my-events.php">My events <?= icon('arrow-right', 15) ?></a>
    </div>
</section>

<div class="container form-layout">
    <div class="form-layout__main">
        <?php
        $isEdit = false;
        $formAction = 'create-event.php';
        $submitLabel = 'Publish event';
        $cancelUrl = 'events.php';
        require __DIR__ . '/includes/event-form.php';
        ?>
    </div>

    <aside class="form-layout__aside">
        <div class="tip-card">
            <h3><?= icon('sparkles', 18) ?> What makes a great event</h3>
            <ul class="check-list">
                <li>A clear, specific title — "Midnight Pancake Run" beats "Food Event".</li>
                <li>A location people can actually find. Add a room number if you have one.</li>
                <li>Two or three sentences about what happens and who it is for.</li>
                <li>An honest capacity. Small and full feels better than big and empty.</li>
            </ul>
        </div>

        <div class="tip-card tip-card--muted">
            <h3><?= icon('shield', 18) ?> Good to know</h3>
            <p>Only verified Taylor students can host events. You can edit or delete your event anytime from <a href="my-events.php">My events</a>.</p>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
