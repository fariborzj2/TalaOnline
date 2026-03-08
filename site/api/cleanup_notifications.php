<?php
/**
 * Cron Task: Notification Cleanup
 * Deletes seen notifications older than 30 days and unread ones older than 90 days.
 *
 * Recommended execution: Once daily via cron job (e.g., 3 AM).
 * Example: 0 3 * * * php /path/to/site/api/cleanup_notifications.php > /dev/null 2>&1
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/notifications.php';

// Allow CLI or internal triggers only (security)
if (php_sapi_name() !== 'cli' && !isset($_GET['secret_key'])) {
    http_response_code(403);
    exit('Direct access not allowed.');
}

// Optional: check secret key if triggered via HTTP
$secret = get_setting('cron_secret_key');
if (php_sapi_name() !== 'cli' && $secret && ($_GET['secret_key'] ?? '') !== $secret) {
    http_response_code(403);
    exit('Invalid secret key.');
}

$notif_manager = new Notifications($pdo);
$success = $notif_manager->cleanup();

if ($success) {
    echo "[" . date('Y-m-d H:i:s') . "] Notification cleanup completed successfully.\n";
} else {
    echo "[" . date('Y-m-d H:i:s') . "] Notification cleanup failed.\n";
}
