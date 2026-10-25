<?php
/**
 * 统一入口：加载配置、工具、数据库、会话与图标
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/icons.php';

boot_session();
