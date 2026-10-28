<?php
/**
 * Workshop API：GET 返回筛选后的卡片 HTML；POST 删除 workshop
 */
require __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_login();
    require_csrf();

    $action = post_str('action');
    if ($action !== 'delete') {
        json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
    }

    $workshop = fetch_workshop(post_int('id'));
    if (!$workshop) {
        json_response(['ok' => false, 'error' => 'Workshop not found.'], 404);
    }

    $user = current_user();
    if ((int) $workshop['host_id'] !== (int) $user['id']) {
        json_response(['ok' => false, 'error' => 'You can only delete workshops you created.'], 403);
    }

    $stmt = db()->prepare('DELETE FROM workshops WHERE id = ?');
    $stmt->execute([(int) $workshop['id']]);
    delete_image('covers', $workshop['cover']);

    json_response(['ok' => true, 'message' => 'Workshop deleted.']);
}

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

$limit = (int) ($_GET['limit'] ?? 9);
$limit = max(3, min(24, $limit));
$offset = max(0, (int) ($_GET['offset'] ?? 0));

$results = search_workshops($filters, $limit, $offset);

ob_start();
foreach ($results['items'] as $workshop) {
    include __DIR__ . '/../includes/workshop-card.php';
}
$html = ob_get_clean();

json_response([
    'ok' => true,
    'html' => $html,
    'count' => count($results['items']),
    'total' => $results['total'],
    'has_more' => $results['has_more'],
]);
