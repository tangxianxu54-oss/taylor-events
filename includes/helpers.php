<?php
/**
 * 通用工具函数：转义、会话、CSRF、日期、上传
 */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------------------------------------------------------------- Flash */

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ----------------------------------------------------------------- CSRF */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

function require_csrf(): void
{
    if (csrf_verify()) {
        return;
    }
    if (is_ajax()) {
        json_response(['ok' => false, 'error' => 'Your session expired. Please refresh and try again.'], 419);
    }
    http_response_code(419);
    exit('Your session expired. Please go back and try again.');
}

/* ------------------------------------------------------------- 请求参数 */

function post_str(string $key, string $default = ''): string
{
    return trim((string) ($_POST[$key] ?? $default));
}

function post_int(string $key, int $default = 0): int
{
    $value = $_POST[$key] ?? null;
    return $value === null || $value === '' ? $default : (int) $value;
}

/* --------------------------------------------------------------- 日期 */

function event_dt(string $value): DateTimeImmutable
{
    return new DateTimeImmutable($value, new DateTimeZone(CAMPUS_TZ));
}

function day_label(string $startAt): string
{
    $date = event_dt($startAt);
    $now = new DateTimeImmutable('now', new DateTimeZone(CAMPUS_TZ));
    $day = $date->format('Y-m-d');
    if ($day === $now->format('Y-m-d')) {
        return 'Today';
    }
    if ($day === $now->modify('+1 day')->format('Y-m-d')) {
        return 'Tomorrow';
    }
    if ($day === $now->modify('-1 day')->format('Y-m-d')) {
        return 'Yesterday';
    }
    return $date->format('D, M j');
}

function month_label(string $startAt): string
{
    return strtoupper(event_dt($startAt)->format('M'));
}

function date_number(string $startAt): string
{
    return event_dt($startAt)->format('j');
}

function time_range_label(string $startAt, string $endAt): string
{
    $start = event_dt($startAt);
    $end = event_dt($endAt);
    if ($start->format('Y-m-d') !== $end->format('Y-m-d')) {
        return $start->format('M j, g:i A') . ' – ' . $end->format('M j, g:i A');
    }
    return $start->format('g:i A') . ' – ' . $end->format('g:i A');
}

function duration_label(string $startAt, string $endAt): string
{
    $minutes = (int) round((event_dt($endAt)->getTimestamp() - event_dt($startAt)->getTimestamp()) / 60);
    if ($minutes < 60) {
        return max(1, $minutes) . ' min';
    }
    $hours = intdiv($minutes, 60);
    $rest = $minutes % 60;
    return $rest ? "{$hours}h {$rest}m" : "{$hours}h";
}

function datetime_local_value(string $dbValue): string
{
    return event_dt($dbValue)->format('Y-m-d\TH:i');
}

function parse_datetime_input(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $value = str_replace('T', ' ', $value);
    if (strlen($value) === 16) {
        $value .= ':00';
    }
    try {
        $date = new DateTimeImmutable($value, new DateTimeZone(CAMPUS_TZ));
    } catch (Exception) {
        return null;
    }
    return $date->format('Y-m-d H:i:s');
}

/* --------------------------------------------------------------- 文本 / 头像 */

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        if (mb_strlen($letters) >= 2) {
            break;
        }
    }
    return $letters !== '' ? $letters : '?';
}

function avatar_url(?string $avatar): ?string
{
    return $avatar ? 'uploads/avatars/' . rawurlencode($avatar) : null;
}

function truncate(string $text, int $length = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $length - 1)) . '…';
}

function mask_email(string $email): string
{
    [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
    $visible = mb_substr($name, 0, min(2, mb_strlen($name)));
    return $visible . str_repeat('•', max(2, mb_strlen($name) - 2)) . '@' . $domain;
}

function is_school_email(string $email): bool
{
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL)
        && str_ends_with(strtolower($email), '@taylor.edu');
}

function sel(string $value, string $current): string
{
    return $value === $current ? ' selected' : '';
}

function is_active(string $page, string $current): string
{
    return $page === $current ? ' is-active' : '';
}

/* --------------------------------------------------------------- 上传 */

/**
 * 处理图片上传，返回文件名；未上传返回 null；失败抛 RuntimeException
 */
function handle_image_upload(array $file, string $subdir, int $maxBytes = MAX_UPLOAD_BYTES): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please try a smaller image.');
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        throw new RuntimeException('Image is too large. Max size is 4 MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Please upload a JPG, PNG or WEBP image.');
    }

    $dir = UPLOAD_PATH . '/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Could not save the image. Please try again.');
    }

    $filename = bin2hex(random_bytes(10)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        throw new RuntimeException('Could not save the image. Please try again.');
    }
    return $filename;
}

function delete_image(string $subdir, ?string $filename): void
{
    if (!$filename) {
        return;
    }
    $path = UPLOAD_PATH . '/' . $subdir . '/' . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

/* ----------------------------------------------------------- 活动表单 */

function event_form_values(): array
{
    return [
        'title' => post_str('title'),
        'category_id' => (string) post_int('category_id'),
        'description' => post_str('description'),
        'location' => post_str('location'),
        'start_at' => post_str('start_at'),
        'end_at' => post_str('end_at'),
        'capacity' => post_str('capacity'),
    ];
}

/**
 * 校验活动表单，返回 [errors, start_at, end_at]
 */
function validate_event_form(array $form, array $categories): array
{
    $errors = [];

    if (mb_strlen($form['title']) < 3) {
        $errors[] = 'Event title needs at least 3 characters.';
    } elseif (mb_strlen($form['title']) > 90) {
        $errors[] = 'Event title is too long (max 90 characters).';
    }

    $validIds = array_map('strval', array_column($categories, 'id'));
    if (!in_array((string) $form['category_id'], $validIds, true)) {
        $errors[] = 'Please choose a valid category.';
    }

    if (mb_strlen($form['location']) < 2) {
        $errors[] = 'Please add a location.';
    } elseif (mb_strlen($form['location']) > 120) {
        $errors[] = 'Location is too long (max 120 characters).';
    }

    if (mb_strlen($form['description']) < 20) {
        $errors[] = 'Description needs at least 20 characters so people know what to expect.';
    } elseif (mb_strlen($form['description']) > 4000) {
        $errors[] = 'Description is too long (max 4000 characters).';
    }

    $capacity = (int) $form['capacity'];
    if ($capacity < 1 || $capacity > 500) {
        $errors[] = 'Capacity must be between 1 and 500.';
    }

    $startAt = parse_datetime_input($form['start_at']);
    $endAt = parse_datetime_input($form['end_at']);
    if (!$startAt) {
        $errors[] = 'Please choose a valid start date and time.';
    }
    if (!$endAt) {
        $errors[] = 'Please choose a valid end date and time.';
    }
    if ($startAt && $endAt && new DateTimeImmutable($endAt, new DateTimeZone(CAMPUS_TZ))
            <= new DateTimeImmutable($startAt, new DateTimeZone(CAMPUS_TZ))) {
        $errors[] = 'The end time must be after the start time.';
    }

    return [$errors, $startAt, $endAt];
}

/* ----------------------------------------------------------- 报名按钮 */

/**
 * 渲染活动详情页的报名区域按钮（服务端与 AJAX 共用）
 */
function render_booking_button(array $event, ?array $user, bool $registered): string
{
    $id = (int) $event['id'];

    if (!$user) {
        $next = urlencode('event.php?id=' . $id);
        return '<a class="btn btn--primary btn--full" href="login.php?next=' . $next . '">'
            . icon('lock', 17) . ' Sign in to RSVP</a>';
    }

    if ((int) $user['verified'] !== 1) {
        return '<a class="btn btn--primary btn--full" href="verify.php">'
            . icon('shield', 17) . ' Verify your email to RSVP</a>';
    }

    if ((int) $event['organizer_id'] === (int) $user['id']) {
        return '<a class="btn btn--primary btn--full" href="edit-event.php?id=' . $id . '">'
            . icon('edit', 17) . ' Edit your event</a>';
    }

    if ($event['is_started']) {
        return '<button class="btn btn--ghost btn--full" type="button" disabled>Registration closed</button>';
    }

    if ($registered) {
        return '<div class="booking-state">' . icon('check-circle', 19) . ' You are going!</div>'
            . '<button class="btn btn--ghost btn--full" type="button" data-register="cancel" data-event-id="' . $id . '">'
            . 'Cancel my RSVP</button>';
    }

    if ($event['is_full']) {
        return '<button class="btn btn--ghost btn--full" type="button" disabled>Event is full</button>';
    }

    return '<button class="btn btn--primary btn--full" type="button" data-register="register" data-event-id="' . $id . '">'
        . icon('ticket', 18) . ' Reserve my spot</button>';
}
