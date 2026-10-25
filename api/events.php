<?php
/**
 * 活动 API：GET 返回筛选后的卡片 HTML；POST 删除活动
 */
require __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    require_csrf();

    $action = post_str('action');
    if ($action !== 'delete') {
        json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
    }

    $event = fetch_event(post_int('id'));
    if (!$event) {
        json_response(['ok' => false, 'error' => 'Event not found.'], 404);
    }

    $user = current_user();
    if ((int) $event['organizer_id'] !== (int) $user['id']) {
        json_response(['ok' => false, 'error' => 'You can only delete events you created.'], 403);
    }

    $stmt = db()->prepare('DELETE FROM events WHERE id = ?');
    $stmt->execute([(int) $event['id']]);
    delete_image('covers', $event['cover']);

    json_response(['ok' => true, 'message' => 'Event deleted.']);
}

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

$limit = (int) ($_GET['limit'] ?? 9);
$limit = max(3, min(24, $limit));
$offset = max(0, (int) ($_GET['offset'] ?? 0));

$results = search_events($filters, $limit, $offset);

ob_start();
foreach ($results['items'] as $event) {
    include __DIR__ . '/../includes/event-card.php';
}
$html = ob_get_clean();

json_response([
    'ok' => true,
    'html' => $html,
    'count' => count($results['items']),
    'total' => $results['total'],
    'has_more' => $results['has_more'],
]);
