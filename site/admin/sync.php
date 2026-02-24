<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/navasan_service.php';
check_login();

$navasan = new NavasanService($pdo);
if ($navasan->syncPrices()) {
    set_setting('last_sync_time', time());

    if (get_setting('lscache_purge_on_update', '1') === '1') {
        LSCache::purgeAll();
    }

    header('Location: index.php?sync=success');
} else {
    header('Location: index.php?sync=error');
}
exit;
