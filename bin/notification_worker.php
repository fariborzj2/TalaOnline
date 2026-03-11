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

    // Run predictive & social checks every 1 hour
    if (time() - $last_predictive_check > 3600) {
        echo "[" . date('Y-m-d H:i:s') . "] Running predictive behavioral triggers...\n";

        // 1. Churn Prevention
        $triggerEngine->handleChurnPrevention();

        // 2. Interest Clustering (Social suggestion)
        $triggerEngine->handleInterestClustering();

        $last_predictive_check = time();
    }

    // Process email queue
    $email_limit = 5;
    $stmt = $pdo->prepare("SELECT * FROM email_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT ?");
    $stmt->bindValue(1, $email_limit, PDO::PARAM_INT);
    $stmt->execute();
    $emails = $stmt->fetchAll();

    foreach ($emails as $email) {
        echo "[" . date('Y-m-d H:i:s') . "] Sending email to {$email['to_email']}...\n";
        $success = Mail::sendRaw($email['to_email'], $email['subject'], $email['body_html'], [
            'sender_name' => $email['sender_name'],
            'sender_email' => $email['sender_email']
        ]);

        if ($success) {
            $pdo->prepare("UPDATE email_queue SET status = 'sent', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$email['id']]);
        } else {
            $error = Mail::getLastError();
            $pdo->prepare("UPDATE email_queue SET status = 'failed', attempts = attempts + 1, last_error = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$error, $email['id']]);
        }
    }

    // Sleep for a bit before next check
    sleep(5);
}
