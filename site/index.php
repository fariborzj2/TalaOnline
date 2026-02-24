<?php
/**
 * Application Entry Point
 */

if (php_sapi_name() == 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

// Initialize session only if it exists or if we are in admin area
$is_admin = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/admin') !== false;
$is_post = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST';

if (isset($_COOKIE['PHPSESSID']) || $is_admin || $is_post) {
    if (get_setting('lscache_enabled') === '1') {
        session_cache_limiter('');
    }
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/core/App.php';
require_once __DIR__ . '/../includes/helpers.php';

$app = new App();
$app->run();
