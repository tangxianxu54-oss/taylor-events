<?php
/**
 * 编辑活动（仅主办人）
 */
require __DIR__ . '/includes/init.php';
require_verified();

$user = current_user();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$event = $id > 0 ? fetch_event($id) : null;

if (!$event) {
    http_response_code(404);
    flash('error', 'That event could not be found.');
    redirect('my-events.php');
}

if ((int) $event['organizer_id'] !== (int) $user['id']) {
    http_response_code(403);
    $page = 'edit';
    $pageTitle = 'Not allowed — ' . APP_NAME;
    require __DIR__ . '/includes/header.php';
    ?>
    <section class="state-page">
        <div class="container">
            <span class="state-page__icon"><?= icon('lock', 40) ?></span>
            <h1>You can only edit your own events</h1>
            <p>This event was published by someone else. Head back to your own events to manage them.</p>
            <div class="state-page__actions">
                <a class="btn btn--primary" href="my-events.php">My events</a>
                <a class="btn btn--ghost" href="event.php?id=<?= (int) $event['id'] ?>">View event</a>
            </div>
        </div>
    </section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$categories = get_categories();
$errors = [];

$form = [
    'title' => $event['title'],
    'category_id' => (string) $event['category_id'],
    'description' => $event['description'],
    'location' => $event['location'],
    'start_at' => datetime_local_value($event['start_at']),
    'end_at' => datetime_local_value($event['end_at']),
    'capacity' => (string) $event['capacity'],
];

$coverUrl = $event['cover'] ? 'uploads/covers/' . rawurlencode($event['cover']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $form = event_form_values();
    [$errors, $startAt, $endAt] = validate_event_form($form, $categories);

    $cover = $event['cover'];
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
            'UPDATE events SET title = ?, category_id = ?, description = ?, location = ?,
                start_at = ?, end_at = ?, capacity = ?, cover = ?
             WHERE id = ? AND organizer_id = ?'
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
            (int) $event['id'],
            (int) $user['id'],
        ]);
        if ($cover !== $event['cover']) {
            delete_image('covers', $event['cover']);
        }
        flash('success', 'Event updated. Your changes are live.');
        redirect('event.php?id=' . (int) $event['id']);
    }

    if ($cover !== $event['cover'] && $cover) {
        $coverUrl = 'uploads/covers/' . rawurlencode($cover);
    }
}

$page = 'edit';
$pageTitle = 'Edit ' . $event['title'] . ' — ' . APP_NAME;
require __DIR__ . '/includes/header.php';
?>

<section class="page-head page-head--compact">
    <div class="container page-head__inner">
        <div>
            <span class="eyebrow"><?= icon('edit', 15) ?> Editing event</span>
            <h1><?= e($event['title']) ?></h1>
            <p class="page-head__lede">Changes are visible immediately. Registrations are kept.</p>
        </div>
        <a class="btn btn--ghost btn--sm" href="event.php?id=<?= (int) $event['id'] ?>">View event <?= icon('arrow-right', 15) ?></a>
    </div>
</section>

<div class="container form-layout">
    <div class="form-layout__main">
        <?php
        $isEdit = true;
        $formAction = 'edit-event.php?id=' . (int) $event['id'];
        $submitLabel = 'Save changes';
        $cancelUrl = 'event.php?id=' . (int) $event['id'];
        require __DIR__ . '/includes/event-form.php';
        ?>
    </div>

    <aside class="form-layout__aside">
        <div class="tip-card">
            <h3><?= icon('users', 18) ?> Current interest</h3>
            <p class="tip-card__stat"><strong><?= (int) $event['going_count'] ?></strong> of <?= (int) $event['capacity'] ?> spots filled.</p>
            <div class="progress"><span style="width: <?= (int) $event['fill_percent'] ?>%"></span></div>
            <p class="muted">If you lower capacity below the number of registered students, existing RSVPs stay valid.</p>
        </div>

        <div class="tip-card tip-card--danger">
            <h3><?= icon('alert', 18) ?> Danger zone</h3>
            <p>Deleting an event removes all registrations. This cannot be undone.</p>
            <button type="button" class="btn btn--danger btn--sm" data-delete-event="<?= (int) $event['id'] ?>">
                <?= icon('trash', 16) ?> Delete event
            </button>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
