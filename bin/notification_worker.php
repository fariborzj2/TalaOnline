<?php
/**
 * Notification Queue Worker
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/push_service.php';
require_once __DIR__ . '/../includes/mail.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/trigger_engine.php';

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

$pushService = new PushService($pdo);
$triggerEngine = new TriggerEngine($pdo, $pushService);

echo "[" . date('Y-m-d H:i:s') . "] Starting notification worker...\n";

$last_predictive_check = 0;

while (true) {
    $processed = $pushService->processQueue(10);

    if ($processed > 0) {
        echo "[" . date('Y-m-d H:i:s') . "] Processed $processed notifications.\n";
    }

    // Run predictive checks every 1 hour
    if (time() - $last_predictive_check > 3600) {
        echo "[" . date('Y-m-d H:i:s') . "] Running predictive behavioral triggers...\n";
        $triggerEngine->handleChurnPrevention();
        $last_predictive_check = time();
    }

    // Also process email queue if applicable
    // Mail::processQueue(); // If such a method exists or is needed

    // Sleep for a bit before next check
    sleep(5);
}
