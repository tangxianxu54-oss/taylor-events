<?php
/**
 * 登出（仅 POST，带 CSRF 校验）
 */
require __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

require_csrf();
logout_user();
redirect('index.php');
