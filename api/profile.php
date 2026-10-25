<?php
/**
 * 个人资料 API：更新资料 / 修改密码
 */
require __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

require_login();
require_csrf();

$user = current_user();
$action = post_str('action');

if ($action === 'profile') {
    $name = post_str('name');
    $studentId = post_str('student_id');
    $major = post_str('major');
    $year = post_str('year');
    $bio = post_str('bio');
    $interests = post_str('interests');

    $errors = [];
    if (mb_strlen($name) < 2) {
        $errors[] = 'Please enter your full name.';
    } elseif (mb_strlen($name) > 60) {
        $errors[] = 'Name is too long (max 60 characters).';
    }
    if (mb_strlen($studentId) > 20) {
        $errors[] = 'Student ID is too long (max 20 characters).';
    }
    if (mb_strlen($major) > 80) {
        $errors[] = 'Major is too long (max 80 characters).';
    }
    $validYears = ['Freshman', 'Sophomore', 'Junior', 'Senior', 'Graduate', 'Staff'];
    if ($year !== '' && !in_array($year, $validYears, true)) {
        $errors[] = 'Please choose a valid year.';
    }
    if (mb_strlen($bio) > 500) {
        $errors[] = 'Bio is too long (max 500 characters).';
    }
    if (mb_strlen($interests) > 200) {
        $errors[] = 'Interests are too long (max 200 characters).';
    }

    if ($errors) {
        json_response(['ok' => false, 'error' => implode(' ', $errors)], 422);
    }

    $avatar = $user['avatar'];
    try {
        $newAvatar = handle_image_upload($_FILES['avatar'] ?? [], 'avatars', 2 * 1024 * 1024);
        if ($newAvatar) {
            $avatar = $newAvatar;
        }
    } catch (RuntimeException $exception) {
        json_response(['ok' => false, 'error' => $exception->getMessage()], 422);
    }

    $stmt = db()->prepare(
        'UPDATE users SET name = ?, student_id = ?, major = ?, year = ?, bio = ?, interests = ?, avatar = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $name,
        $studentId !== '' ? $studentId : null,
        $major !== '' ? $major : null,
        $year !== '' ? $year : null,
        $bio !== '' ? $bio : null,
        $interests !== '' ? $interests : null,
        $avatar,
        (int) $user['id'],
    ]);

    if ($avatar !== $user['avatar']) {
        delete_image('avatars', $user['avatar']);
    }

    json_response([
        'ok' => true,
        'message' => 'Profile updated.',
        'user' => [
            'name' => $name,
            'email' => $user['email'],
            'initials' => initials($name),
            'avatar_url' => avatar_url($avatar),
            'student_id' => $studentId,
            'major' => $major,
            'year' => $year,
            'bio' => $bio,
            'interests' => $interests,
        ],
    ]);
}

if ($action === 'password') {
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!password_verify($current, $user['password_hash'])) {
        json_response(['ok' => false, 'error' => 'Your current password is incorrect.'], 422);
    }
    if (strlen($new) < 8) {
        json_response(['ok' => false, 'error' => 'New password needs at least 8 characters.'], 422);
    }
    if ($new !== $confirm) {
        json_response(['ok' => false, 'error' => 'The new passwords do not match.'], 422);
    }
    if ($new === $current) {
        json_response(['ok' => false, 'error' => 'Choose a password different from your current one.'], 422);
    }

    $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $stmt->execute([password_hash($new, PASSWORD_DEFAULT), (int) $user['id']]);

    json_response(['ok' => true, 'message' => 'Password changed. Use it next time you sign in.']);
}

json_response(['ok' => false, 'error' => 'Unknown action.'], 400);
