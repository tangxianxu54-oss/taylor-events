<?php
/**
 * 报名 / 取消报名 API
 */
require __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

require_login();
require_csrf();

$user = current_user();
if ((int) $user['verified'] !== 1) {
    json_response(['ok' => false, 'error' => 'Please verify your campus email first.'], 403);
}

$eventId = post_int('id');
$action = post_str('action');
if ($eventId <= 0 || !in_array($action, ['register', 'cancel'], true)) {
    json_response(['ok' => false, 'error' => 'Invalid request.'], 400);
}

$pdo = db();
$message = '';

try {
    $pdo->exec('BEGIN IMMEDIATE');

    $stmt = $pdo->prepare('SELECT id, capacity, start_at, status FROM events WHERE id = ?');
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();

    if (!$event || $event['status'] !== 'published') {
        $pdo->exec('ROLLBACK');
        json_response(['ok' => false, 'error' => 'This event is no longer available.'], 404);
    }

    $now = new DateTimeImmutable('now', new DateTimeZone(CAMPUS_TZ));
    $start = event_dt($event['start_at']);

    if ($action === 'register') {
        if ($start <= $now) {
            $pdo->exec('ROLLBACK');
            json_response(['ok' => false, 'error' => 'Registration for this event has closed.'], 400);
        }

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM registrations WHERE event_id = ? AND status = 'going'"
        );
        $countStmt->execute([$eventId]);
        $going = (int) $countStmt->fetchColumn();

        if ($going >= (int) $event['capacity']) {
            $pdo->exec('ROLLBACK');
            json_response(['ok' => false, 'error' => 'This event just filled up. Try another one!'], 409);
        }

        $stamp = date('Y-m-d H:i:s');
        $insert = $pdo->prepare(
            "INSERT INTO registrations (event_id, user_id, status, created_at, updated_at)
             VALUES (?, ?, 'going', ?, ?)
             ON CONFLICT (event_id, user_id) DO UPDATE SET status = 'going', updated_at = excluded.updated_at"
        );
        $insert->execute([$eventId, (int) $user['id'], $stamp, $stamp]);
        $message = 'You are going! Your spot is saved.';
    } else {
        $update = $pdo->prepare(
            "UPDATE registrations SET status = 'cancelled', updated_at = ?
             WHERE event_id = ? AND user_id = ? AND status = 'going'"
        );
        $update->execute([date('Y-m-d H:i:s'), $eventId, (int) $user['id']]);
        $message = 'Your RSVP was cancelled.';
    }

    $pdo->exec('COMMIT');
} catch (Throwable $exception) {
    try {
        $pdo->exec('ROLLBACK');
    } catch (Throwable) {
        // 忽略回滚失败
    }
    json_response(['ok' => false, 'error' => 'Something went wrong. Please try again.'], 500);
}

$fresh = fetch_event($eventId);
$registered = user_is_registered($eventId, (int) $user['id']);

json_response([
    'ok' => true,
    'message' => $message,
    'registered' => $registered,
    'going_count' => (int) $fresh['going_count'],
    'spots_left' => (int) $fresh['spots_left'],
    'fill_percent' => (int) $fresh['fill_percent'],
    'is_full' => (bool) $fresh['is_full'],
    'booking_html' => render_booking_button($fresh, $user, $registered),
]);
