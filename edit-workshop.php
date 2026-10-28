<?php
/**
 * 编辑 Workshop / Seminar（仅主办人）
 */
require __DIR__ . '/includes/init.php';
require_verified();

$user = current_user();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$workshop = $id > 0 ? fetch_workshop($id) : null;

if (!$workshop) {
    http_response_code(404);
    flash('error', 'That workshop could not be found.');
    redirect('my-bookings.php?tab=hosting');
}

if ((int) $workshop['host_id'] !== (int) $user['id']) {
    http_response_code(403);
    $page = 'edit-workshop';
    $pageTitle = 'Not allowed — ' . APP_NAME;
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="state-page">
        <div class="container">
            <span class="state-page__icon"><?= icon('lock', 40) ?></span>
            <h1>You can only edit your own workshops</h1>
            <p>This session was published by someone else. Head back to your own workshops to manage them.</p>
            <div class="state-page__actions">
                <a class="btn btn--primary" href="my-bookings.php?tab=hosting">My workshops</a>
                <a class="btn btn--ghost" href="workshop.php?id=<?= (int) $workshop['id'] ?>">View workshop</a>
            </div>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$topics = get_categories();
$errors = [];

$form = [
    'title' => $workshop['title'],
    'topic_id' => (string) $workshop['topic_id'],
    'speaker' => $workshop['speaker'],
    'mode' => $workshop['mode'],
    'location' => $workshop['location'],
    'start_at' => datetime_local_value($workshop['start_at']),
    'end_at' => datetime_local_value($workshop['end_at']),
    'capacity' => (string) $workshop['capacity'],
    'description' => $workshop['description'],
];

$coverUrl = $workshop['cover'] ? 'uploads/covers/' . rawurlencode($workshop['cover']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $form = workshop_form_values();
    [$errors, $startAt, $endAt] = validate_workshop_form($form, $topics);

    $cover = $workshop['cover'];
    if (!$errors) {
        try {
            $newCover = handle_image_upload($_FILES['cover'] ?? [], 'covers');
            if ($newCover) {
                $cover = $newCover;
            }
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    if (!$errors && $startAt && $endAt) {
        $stmt = db()->prepare(
            'UPDATE workshops SET title = ?, topic_id = ?, description = ?, speaker = ?, mode = ?,
                location = ?, start_at = ?, end_at = ?, capacity = ?, cover = ?
             WHERE id = ? AND host_id = ?'
        );
        $stmt->execute([
            $form['title'],
            (int) $form['topic_id'],
            $form['description'],
            $form['speaker'],
            $form['mode'],
            $form['location'],
            $startAt,
            $endAt,
            (int) $form['capacity'],
            $cover,
            (int) $workshop['id'],
            (int) $user['id'],
        ]);
        if ($cover !== $workshop['cover']) {
            delete_image('covers', $workshop['cover']);
        }
        flash('success', 'Workshop updated. Your changes are live.');
        redirect('workshop.php?id=' . (int) $workshop['id']);
    }

    if ($cover !== $workshop['cover'] && $cover) {
        $coverUrl = 'uploads/covers/' . rawurlencode($cover);
    }
}

$page = 'edit-workshop';
$pageTitle = 'Edit ' . $workshop['title'] . ' — ' . APP_NAME;
require __DIR__ . '/includes/header.php';
?>

<section class="page-head page-head--compact">
    <div class="container page-head__inner">
        <div>
            <span class="eyebrow"><?= icon('edit', 15) ?> Editing workshop</span>
            <h1><?= e($workshop['title']) ?></h1>
            <p class="page-head__lede">Changes are visible immediately. Existing bookings are kept.</p>
        </div>
        <a class="btn btn--ghost btn--sm" href="workshop.php?id=<?= (int) $workshop['id'] ?>">View workshop <?= icon('arrow-right', 15) ?></a>
    </div>
</section>

<div class="container form-layout">
    <div class="form-layout__main">
        <?php
        $isEdit = true;
        $formAction = 'edit-workshop.php?id=' . (int) $workshop['id'];
        $cancelUrl = 'workshop.php?id=' . (int) $workshop['id'];
        require __DIR__ . '/includes/workshop-form.php';
        ?>
    </div>

    <aside class="form-layout__aside">
        <div class="tip-card">
            <h3><?= icon('users', 18) ?> Booking status</h3>
            <p class="tip-card__stat"><strong><?= (int) $workshop['going_count'] ?></strong> of <?= (int) $workshop['capacity'] ?> seats booked.</p>
            <div class="progress"><span style="width: <?= (int) $workshop['fill_percent'] ?>%"></span></div>
            <p class="muted">If you lower the seat count below current bookings, existing bookings stay valid.</p>
        </div>

        <div class="tip-card tip-card--danger">
            <h3><?= icon('alert', 18) ?> Danger zone</h3>
            <p>Deleting a workshop removes every booking and booking code. This cannot be undone.</p>
            <button type="button" class="btn btn--danger btn--sm" data-delete-workshop="<?= (int) $workshop['id'] ?>">
                <?= icon('trash', 16) ?> Delete workshop
            </button>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
