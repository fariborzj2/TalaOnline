<?php
/**
 * Application Entry Point
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/core/App.php';
require_once __DIR__ . '/../includes/helpers.php';

$app = new App();
$app->run();
