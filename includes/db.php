<?php
/**
 * 数据库连接与活动查询
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $isNew = !file_exists(DB_PATH);
    $pdo = new PDO('sqlite:' . DB_PATH, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew) {
        $pdo->exec((string) file_get_contents(__DIR__ . '/../data/schema.sql'));
        require_once __DIR__ . '/../data/seed.php';
        $pdo->beginTransaction();
        try {
            seed_database($pdo);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    ensure_workshop_schema($pdo);

    return $pdo;
}

/**
 * 老数据库自动补齐 Workshop 相关表（CREATE TABLE IF NOT EXISTS 幂等）
 */
function ensure_workshop_schema(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $exists = $pdo->query(
        "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'workshops'"
    )->fetchColumn();

    if (!$exists) {
        $pdo->exec((string) file_get_contents(__DIR__ . '/../data/schema.sql'));
    }
}

/* --------------------------------------------------------------- 分类 */

function get_categories(): array
{
    static $categories = null;
    if ($categories !== null) {
        return $categories;
    }
    $stmt = db()->prepare(
        "SELECT c.*,
            (SELECT COUNT(*) FROM events e
              WHERE e.category_id = c.id AND e.status = 'published' AND e.end_at >= ?) AS event_count
         FROM categories c
         ORDER BY c.sort_order"
    );
    $stmt->execute([date('Y-m-d H:i:s')]);
    $categories = $stmt->fetchAll();
    return $categories;
}

/* --------------------------------------------------------------- 活动 */

function event_select_fields(): string
{
    return "e.*,
            c.name AS category_name, c.slug AS category_slug,
            c.color AS category_color, c.icon AS category_icon,
            u.name AS organizer_name, u.avatar AS organizer_avatar,
            (SELECT COUNT(*) FROM registrations r
              WHERE r.event_id = e.id AND r.status = 'going') AS going_count";
}

function event_select_sql(): string
{
    return 'SELECT ' . event_select_fields() . '
        FROM events e
        JOIN categories c ON c.id = e.category_id
        JOIN users u ON u.id = e.organizer_id';
}

function decorate_event(array $row): array
{
    $row['going_count'] = (int) $row['going_count'];
    $row['capacity'] = (int) $row['capacity'];
    $row['spots_left'] = max(0, $row['capacity'] - $row['going_count']);
    $row['is_full'] = $row['spots_left'] <= 0;
    $row['fill_percent'] = $row['capacity'] > 0
        ? min(100, (int) round($row['going_count'] / $row['capacity'] * 100))
        : 0;

    $start = event_dt($row['start_at']);
    $end = event_dt($row['end_at']);
    $now = new DateTimeImmutable('now', new DateTimeZone(CAMPUS_TZ));

    $row['is_started'] = $start <= $now;
    $row['is_past'] = $end < $now;
    $row['day_label'] = day_label($row['start_at']);
    $row['month_label'] = month_label($row['start_at']);
    $row['date_number'] = date_number($row['start_at']);
    $row['time_label'] = time_range_label($row['start_at'], $row['end_at']);
    $row['duration_label'] = duration_label($row['start_at'], $row['end_at']);
    $row['full_date_label'] = $start->format('l, F j, Y');
    $row['cover_url'] = $row['cover']
        ? 'uploads/covers/' . rawurlencode($row['cover'])
        : category_cover_url($row['category_slug']);

    return $row;
}

function build_event_filters(array $filters): array
{
    $where = ["e.status = 'published'"];
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(e.title LIKE :q OR e.description LIKE :q OR e.location LIKE :q OR c.name LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }

    $category = (string) ($filters['category'] ?? 'all');
    if ($category !== '' && $category !== 'all') {
        $where[] = 'c.slug = :category';
        $params[':category'] = $category;
    }

    $when = (string) ($filters['when'] ?? 'upcoming');
    if ($when === 'upcoming') {
        $where[] = 'e.end_at >= :now';
        $params[':now'] = date('Y-m-d H:i:s');
    } elseif ($when === 'past') {
        $where[] = 'e.end_at < :now';
        $params[':now'] = date('Y-m-d H:i:s');
    }

    $exclude = (int) ($filters['exclude'] ?? 0);
    if ($exclude > 0) {
        $where[] = 'e.id != :exclude';
        $params[':exclude'] = $exclude;
    }

    return [' WHERE ' . implode(' AND ', $where), $params];
}

function count_events(array $filters): int
{
    [$where, $params] = build_event_filters($filters);
    $sql = 'SELECT COUNT(*) FROM events e
            JOIN categories c ON c.id = e.category_id
            JOIN users u ON u.id = e.organizer_id' . $where;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function search_events(array $filters, int $limit = 9, int $offset = 0): array
{
    [$where, $params] = build_event_filters($filters);

    $sort = (string) ($filters['sort'] ?? 'soonest');
    $when = (string) ($filters['when'] ?? 'upcoming');
    $order = match ($sort) {
        'popular' => 'going_count DESC, e.start_at ASC',
        'newest' => 'e.created_at DESC, e.start_at ASC',
        default => $when === 'past' ? 'e.start_at DESC' : 'e.start_at ASC',
    };

    $limit = max(1, min(48, $limit));
    $offset = max(0, $offset);

    $sql = event_select_sql() . $where . ' ORDER BY ' . $order
        . ' LIMIT ' . ($limit + 1) . ' OFFSET ' . $offset;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $hasMore = count($rows) > $limit;
    if ($hasMore) {
        array_pop($rows);
    }

    return [
        'items' => array_map('decorate_event', $rows),
        'has_more' => $hasMore,
        'total' => count_events($filters),
    ];
}

function fetch_event(int $id): ?array
{
    $stmt = db()->prepare(event_select_sql() . ' WHERE e.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? decorate_event($row) : null;
}

function event_attendees(int $eventId, int $limit = 14): array
{
    $stmt = db()->prepare(
        "SELECT u.id, u.name, u.avatar, u.major, u.year, r.created_at
         FROM registrations r
         JOIN users u ON u.id = r.user_id
         WHERE r.event_id = ? AND r.status = 'going'
         ORDER BY r.created_at ASC
         LIMIT " . max(1, $limit)
    );
    $stmt->execute([$eventId]);
    return $stmt->fetchAll();
}

/* --------------------------------------------------------------- 用户相关 */

function user_is_registered(int $eventId, int $userId): bool
{
    $stmt = db()->prepare(
        "SELECT 1 FROM registrations WHERE event_id = ? AND user_id = ? AND status = 'going'"
    );
    $stmt->execute([$eventId, $userId]);
    return (bool) $stmt->fetchColumn();
}

function user_going_events(int $userId, int $limit = 60): array
{
    $sql = 'SELECT ' . event_select_fields() . ", r.created_at AS registered_at
         FROM events e
         JOIN categories c ON c.id = e.category_id
         JOIN users u ON u.id = e.organizer_id
         JOIN registrations r ON r.event_id = e.id AND r.user_id = :uid AND r.status = 'going'
         ORDER BY e.start_at ASC
         LIMIT " . max(1, $limit);
    $stmt = db()->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    return array_map('decorate_event', $stmt->fetchAll());
}

function user_hosted_events(int $userId): array
{
    $stmt = db()->prepare(
        event_select_sql() . ' WHERE e.organizer_id = ? ORDER BY e.start_at DESC'
    );
    $stmt->execute([$userId]);
    return array_map('decorate_event', $stmt->fetchAll());
}

function campus_stats(): array
{
    $pdo = db();
    $now = date('Y-m-d H:i:s');

    $upcoming = (int) $pdo->query(
        "SELECT COUNT(*) FROM events WHERE status = 'published' AND end_at >= " . $pdo->quote($now)
    )->fetchColumn();

    $signups = (int) $pdo->query(
        "SELECT COUNT(*) FROM registrations r JOIN events e ON e.id = r.event_id
         WHERE r.status = 'going' AND e.end_at >= " . $pdo->quote($now)
    )->fetchColumn();

    $students = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    $hosts = (int) $pdo->query(
        "SELECT COUNT(DISTINCT organizer_id) FROM events WHERE status = 'published'"
    )->fetchColumn();

    return [
        'upcoming' => $upcoming,
        'signups' => $signups,
        'students' => $students,
        'hosts' => $hosts,
    ];
}

/* ------------------------------------------------- Workshop & Seminar */

function workshop_mode_label(string $mode): string
{
    return match ($mode) {
        'online' => 'Online',
        'hybrid' => 'Hybrid',
        default => 'In person',
    };
}

function workshop_select_fields(): string
{
    return "w.*,
            c.name AS topic_name, c.slug AS topic_slug,
            c.color AS topic_color, c.icon AS topic_icon,
            u.name AS host_name, u.avatar AS host_avatar,
            (SELECT COUNT(*) FROM workshop_bookings b
              WHERE b.workshop_id = w.id AND b.status = 'booked') AS going_count";
}

function workshop_select_sql(): string
{
    return 'SELECT ' . workshop_select_fields() . '
        FROM workshops w
        JOIN categories c ON c.id = w.topic_id
        JOIN users u ON u.id = w.host_id';
}

function decorate_workshop(array $row): array
{
    $row['going_count'] = (int) $row['going_count'];
    $row['capacity'] = (int) $row['capacity'];
    $row['spots_left'] = max(0, $row['capacity'] - $row['going_count']);
    $row['is_full'] = $row['spots_left'] <= 0;
    $row['fill_percent'] = $row['capacity'] > 0
        ? min(100, (int) round($row['going_count'] / $row['capacity'] * 100))
        : 0;

    $start = event_dt($row['start_at']);
    $end = event_dt($row['end_at']);
    $now = new DateTimeImmutable('now', new DateTimeZone(CAMPUS_TZ));

    $row['is_started'] = $start <= $now;
    $row['is_past'] = $end < $now;
    $row['day_label'] = day_label($row['start_at']);
    $row['month_label'] = month_label($row['start_at']);
    $row['date_number'] = date_number($row['start_at']);
    $row['time_label'] = time_range_label($row['start_at'], $row['end_at']);
    $row['duration_label'] = duration_label($row['start_at'], $row['end_at']);
    $row['full_date_label'] = $start->format('l, F j, Y');
    $row['mode_label'] = workshop_mode_label((string) $row['mode']);
    $row['cover_url'] = $row['cover']
        ? 'uploads/covers/' . rawurlencode($row['cover'])
        : category_cover_url($row['topic_slug']);

    return $row;
}

function build_workshop_filters(array $filters): array
{
    $where = ["w.status = 'published'"];
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(w.title LIKE :q OR w.description LIKE :q OR w.speaker LIKE :q OR w.location LIKE :q OR c.name LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }

    $topic = (string) ($filters['topic'] ?? 'all');
    if ($topic !== '' && $topic !== 'all') {
        $where[] = 'c.slug = :topic';
        $params[':topic'] = $topic;
    }

    $when = (string) ($filters['when'] ?? 'upcoming');
    if ($when === 'upcoming') {
        $where[] = 'w.end_at >= :now';
        $params[':now'] = date('Y-m-d H:i:s');
    } elseif ($when === 'past') {
        $where[] = 'w.end_at < :now';
        $params[':now'] = date('Y-m-d H:i:s');
    }

    $mode = (string) ($filters['mode'] ?? 'all');
    if (in_array($mode, ['in-person', 'online', 'hybrid'], true)) {
        $where[] = 'w.mode = :mode';
        $params[':mode'] = $mode;
    }

    $exclude = (int) ($filters['exclude'] ?? 0);
    if ($exclude > 0) {
        $where[] = 'w.id != :exclude';
        $params[':exclude'] = $exclude;
    }

    return [' WHERE ' . implode(' AND ', $where), $params];
}

function count_workshops(array $filters): int
{
    [$where, $params] = build_workshop_filters($filters);
    $sql = 'SELECT COUNT(*) FROM workshops w
            JOIN categories c ON c.id = w.topic_id
            JOIN users u ON u.id = w.host_id' . $where;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function search_workshops(array $filters, int $limit = 9, int $offset = 0): array
{
    [$where, $params] = build_workshop_filters($filters);

    $sort = (string) ($filters['sort'] ?? 'soonest');
    $when = (string) ($filters['when'] ?? 'upcoming');
    $order = match ($sort) {
        'popular' => 'going_count DESC, w.start_at ASC',
        'newest' => 'w.created_at DESC, w.start_at ASC',
        default => $when === 'past' ? 'w.start_at DESC' : 'w.start_at ASC',
    };

    $limit = max(1, min(48, $limit));
    $offset = max(0, $offset);

    $sql = workshop_select_sql() . $where . ' ORDER BY ' . $order
        . ' LIMIT ' . ($limit + 1) . ' OFFSET ' . $offset;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $hasMore = count($rows) > $limit;
    if ($hasMore) {
        array_pop($rows);
    }

    return [
        'items' => array_map('decorate_workshop', $rows),
        'has_more' => $hasMore,
        'total' => count_workshops($filters),
    ];
}

function fetch_workshop(int $id): ?array
{
    $stmt = db()->prepare(workshop_select_sql() . ' WHERE w.id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? decorate_workshop($row) : null;
}

function workshop_attendees(int $workshopId, int $limit = 14): array
{
    $stmt = db()->prepare(
        "SELECT u.id, u.name, u.avatar, u.major, u.year, b.created_at
         FROM workshop_bookings b
         JOIN users u ON u.id = b.user_id
         WHERE b.workshop_id = ? AND b.status = 'booked'
         ORDER BY b.created_at ASC
         LIMIT " . max(1, $limit)
    );
    $stmt->execute([$workshopId]);
    return $stmt->fetchAll();
}

function workshop_booking(int $workshopId, int $userId): ?array
{
    $stmt = db()->prepare(
        'SELECT * FROM workshop_bookings WHERE workshop_id = ? AND user_id = ?'
    );
    $stmt->execute([$workshopId, $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function user_is_booked(int $workshopId, int $userId): bool
{
    $booking = workshop_booking($workshopId, $userId);
    return $booking !== null && $booking['status'] === 'booked';
}

function user_booked_workshops(int $userId, int $limit = 60): array
{
    $sql = 'SELECT ' . workshop_select_fields() . ", b.code AS booking_code, b.created_at AS booked_at
         FROM workshops w
         JOIN categories c ON c.id = w.topic_id
         JOIN users u ON u.id = w.host_id
         JOIN workshop_bookings b ON b.workshop_id = w.id AND b.user_id = :uid AND b.status = 'booked'
         ORDER BY w.start_at ASC
         LIMIT " . max(1, $limit);
    $stmt = db()->prepare($sql);
    $stmt->execute([':uid' => $userId]);
    return array_map('decorate_workshop', $stmt->fetchAll());
}

function user_hosted_workshops(int $userId): array
{
    $stmt = db()->prepare(
        workshop_select_sql() . ' WHERE w.host_id = ? ORDER BY w.start_at DESC'
    );
    $stmt->execute([$userId]);
    return array_map('decorate_workshop', $stmt->fetchAll());
}
