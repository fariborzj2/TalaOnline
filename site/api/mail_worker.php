<?php
/**
 * Email Queue Worker
 * Processes pending emails in the background
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/mail.php';

// Ensure no session is holding up the request if helpers start it
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Prevent concurrent execution (simple lock)
$lock_file = __DIR__ . '/mail_worker.lock';
if (file_exists($lock_file) && (time() - filemtime($lock_file) < 300)) {
    die('Worker already running');
}
file_put_contents($lock_file, time());

// Set execution limits
set_time_limit(240);
ignore_user_abort(true);

$processed_count = 0;
try {
    if (!$pdo) {
        throw new Exception("Database connection not available.");
    }

    // Fetch pending emails
    $stmt = $pdo->prepare("SELECT * FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT 10");
    $stmt->execute();
    $emails = $stmt->fetchAll();

    foreach ($emails as $email) {
        $options = [
            'sender_name' => $email['sender_name'],
            'sender_email' => $email['sender_email'],
            'debug' => false
        ];

        if (!empty($email['metadata'])) {
            $metadata = json_decode($email['metadata'], true);
            if (is_array($metadata)) {
                $options = array_merge($options, $metadata);
            }
        }

        // Process sending
        $success = Mail::sendRaw($email['to_email'], $email['subject'], $email['body_html'], $options);

        if ($success) {
            $pdo->prepare("UPDATE email_queue SET status = 'sent', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$email['id']]);
        } else {
            // Log attempt failure and mark as failed if attempts >= 2 (so next time it hits 3 and stops)
            $error_msg = Mail::getLastError() ?: 'Sending failed';
            $new_status = ($email['attempts'] >= 2) ? 'failed' : 'pending';

            $pdo->prepare("UPDATE email_queue SET status = ?, attempts = attempts + 1, last_error = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$new_status, $error_msg, $email['id']]);
        }
        $processed_count++;
    }
} catch (Exception $e) {
    error_log("Worker Error: " . $e->getMessage());
    echo "Worker Error: " . $e->getMessage() . "\n";
} finally {
    if (file_exists($lock_file)) unlink($lock_file);
}

echo "Processed $processed_count emails.\n";
