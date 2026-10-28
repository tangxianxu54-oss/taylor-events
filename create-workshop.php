<?php
/**
 * 创建 Workshop / Seminar
 */
require __DIR__ . '/includes/init.php';
require_verified();

$user = current_user();
$topics = get_categories();
$errors = [];

$form = [
    'title' => '',
    'topic_id' => '',
    'speaker' => '',
    'mode' => 'in-person',
    'location' => '',
    'start_at' => '',
    'end_at' => '',
    'capacity' => '30',
    'description' => '',
];

if (!empty($_GET['topic'])) {
    foreach ($topics as $topic) {
        if ($topic['slug'] === $_GET['topic']) {
            $form['topic_id'] = (string) $topic['id'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $default = new DateTimeImmutable('tomorrow 15:00', new DateTimeZone(CAMPUS_TZ));
    $form['start_at'] = $default->format('Y-m-d\TH:i');
    $form['end_at'] = $default->modify('+90 minutes')->format('Y-m-d\TH:i');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $form = workshop_form_values();
    [$errors, $startAt, $endAt] = validate_workshop_form($form, $topics);

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
            'INSERT INTO workshops (title, topic_id, description, speaker, mode, location, cover, start_at, end_at, capacity, host_id, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $form['title'],
            (int) $form['topic_id'],
            $form['description'],
            $form['speaker'],
            $form['mode'],
            $form['location'],
            $cover,
            $startAt,
            $endAt,
            (int) $form['capacity'],
            (int) $user['id'],
            'published',
            date('Y-m-d H:i:s'),
        ]);
        $workshopId = (int) db()->lastInsertId();
        flash('success', 'Your workshop is live. Booking is open.');
        redirect('workshop.php?id=' . $workshopId);
    }
}

$page = 'create-workshop';
$pageTitle = 'Host a workshop — ' . APP_NAME;
$pageDescription = 'Publish a workshop or seminar and open seat booking.';
require __DIR__ . '/includes/header.php';
?>

<section class="page-head page-head--compact">
    <div class="container page-head__inner">
        <div>
            <span class="eyebrow"><?= icon('presentation', 15) ?> Workshop &amp; seminar booking</span>
            <h1>Host a workshop</h1>
            <p class="page-head__lede">Set a topic, pick a time, and open a small number of seats. Students book with one click.</p>
        </div>
        <a class="btn btn--ghost btn--sm" href="my-bookings.php?tab=hosting">My workshops <?= icon('arrow-right', 15) ?></a>
    </div>
</section>

<div class="container form-layout">
    <div class="form-layout__main">
        <?php
        $isEdit = false;
        $formAction = 'create-workshop.php';
        $cancelUrl = 'workshops.php';
        require __DIR__ . '/includes/workshop-form.php';
        ?>
    </div>

    <aside class="form-layout__aside">
        <div class="tip-card">
            <h3><?= icon('sparkles', 18) ?> What makes a great session</h3>
            <ul class="check-list">
                <li>One clear outcome — "build a one-page portfolio" beats "talk about careers".</li>
                <li>Small seats work better. 20–40 people keeps it hands-on.</li>
                <li>Say what to bring: a laptop, a draft, a question.</li>
                <li>Online? Put the meeting link in the location field.</li>
            </ul>
        </div>

        <div class="tip-card tip-card--muted">
            <h3><?= icon('shield', 18) ?> Good to know</h3>
            <p>Only verified Taylor students can host workshops. Bookings close automatically when the session starts, and every attendee gets a booking code.</p>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
