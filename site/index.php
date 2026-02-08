<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/navasan_service.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/core/App.php';

$navasan = new NavasanService($pdo);

// Sync if needed
$sync_interval = (int)get_setting('api_sync_interval', 10) * 60;
$last_sync = get_setting('last_sync_time', 0);
if (time() - $last_sync > $sync_interval) {
    if ($navasan->syncPrices()) {
        set_setting('last_sync_time', time());
    }
}

// Initialize and run the application
$app = new App();
$app->run();
