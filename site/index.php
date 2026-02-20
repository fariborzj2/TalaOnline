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

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/core/App.php';
require_once __DIR__ . '/../includes/helpers.php';

$app = new App();
$app->run();
