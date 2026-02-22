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

try {
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
            // Log attempt failure
            $error_msg = Mail::getLastError() ?: 'Sending failed';
            $pdo->prepare("UPDATE email_queue SET attempts = attempts + 1, last_error = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                ->execute([$error_msg, $email['id']]);
        }
    }
} catch (Exception $e) {
    error_log("Worker Error: " . $e->getMessage());
} finally {
    if (file_exists($lock_file)) unlink($lock_file);
}

echo "Processed " . count($emails) . " emails.";
