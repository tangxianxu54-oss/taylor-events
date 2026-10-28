<?php
/**
 * Workshop 预约 / 取消预约 API
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

$workshopId = post_int('id');
$action = post_str('action');
if ($workshopId <= 0 || !in_array($action, ['book', 'cancel'], true)) {
    json_response(['ok' => false, 'error' => 'Invalid request.'], 400);
}

$pdo = db();
$message = '';

try {
    $pdo->exec('BEGIN IMMEDIATE');

    $stmt = $pdo->prepare('SELECT id, capacity, start_at, status FROM workshops WHERE id = ?');
    $stmt->execute([$workshopId]);
    $workshop = $stmt->fetch();

    if (!$workshop || $workshop['status'] !== 'published') {
        $pdo->exec('ROLLBACK');
        json_response(['ok' => false, 'error' => 'This workshop is no longer available.'], 404);
    }

    $now = new DateTimeImmutable('now', new DateTimeZone(CAMPUS_TZ));
    $start = event_dt($workshop['start_at']);

    if ($action === 'book') {
        if ($start <= $now) {
            $pdo->exec('ROLLBACK');
            json_response(['ok' => false, 'error' => 'Booking for this workshop has closed.'], 400);
        }

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM workshop_bookings
             WHERE workshop_id = ? AND status = 'booked' AND user_id != ?"
        );
        $countStmt->execute([$workshopId, (int) $user['id']]);
        $booked = (int) $countStmt->fetchColumn();

        if ($booked >= (int) $workshop['capacity']) {
            $pdo->exec('ROLLBACK');
            json_response(['ok' => false, 'error' => 'This workshop just filled up. Try another one!'], 409);
        }

        $stamp = date('Y-m-d H:i:s');
        $insert = $pdo->prepare(
            "INSERT INTO workshop_bookings (workshop_id, user_id, status, code, created_at, updated_at)
             VALUES (?, ?, 'booked', ?, ?, ?)
             ON CONFLICT (workshop_id, user_id)
             DO UPDATE SET status = 'booked', code = excluded.code, updated_at = excluded.updated_at"
        );
        $insert->execute([$workshopId, (int) $user['id'], generate_booking_code(), $stamp, $stamp]);
        $message = 'Your seat is booked. Keep your booking code.';
    } else {
        $update = $pdo->prepare(
            "UPDATE workshop_bookings SET status = 'cancelled', updated_at = ?
             WHERE workshop_id = ? AND user_id = ? AND status = 'booked'"
        );
        $update->execute([date('Y-m-d H:i:s'), $workshopId, (int) $user['id']]);
        $message = 'Your booking was cancelled.';
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

$fresh = fetch_workshop($workshopId);
$booking = workshop_booking($workshopId, (int) $user['id']);

json_response([
    'ok' => true,
    'message' => $message,
    'booked' => $booking !== null && $booking['status'] === 'booked',
    'code' => $booking['code'] ?? null,
    'going_count' => (int) $fresh['going_count'],
    'spots_left' => (int) $fresh['spots_left'],
    'fill_percent' => (int) $fresh['fill_percent'],
    'is_full' => (bool) $fresh['is_full'],
    'booking_html' => render_workshop_booking_button($fresh, $user, $booking),
]);
