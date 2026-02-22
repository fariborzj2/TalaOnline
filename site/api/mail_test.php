<?php
/**
 * SMTP Connection Tester
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/mail.php';
session_start();

if (!isset($_SESSION['user_role_id']) || $_SESSION['user_role_id'] <= 0) {
    die(json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']));
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) die(json_encode(['success' => false, 'message' => 'دیتا نامعتبر']));

$test_email = $data['test_email'] ?? '';
if (empty($test_email)) die(json_encode(['success' => false, 'message' => 'ایمیل تست الزامی است']));

// Release session lock to prevent blocking other requests
session_write_close();

// Temporarily override settings for this test
$original_settings = [
    'mail_driver' => get_setting('mail_driver'),
    'smtp_host' => get_setting('smtp_host'),
    'smtp_port' => get_setting('smtp_port'),
    'smtp_user' => get_setting('smtp_user'),
    'smtp_pass' => get_setting('smtp_pass'),
    'smtp_enc' => get_setting('smtp_enc'),
    'smtp_skip_ssl_verify' => get_setting('smtp_skip_ssl_verify'),
    'mail_sender_name' => get_setting('mail_sender_name'),
    'mail_sender_email' => get_setting('mail_sender_email')
];

// Apply temporary settings from request
set_setting('mail_driver', 'smtp');
set_setting('smtp_host', $data['smtp_host']);
set_setting('smtp_port', $data['smtp_port']);
set_setting('smtp_user', $data['smtp_user']);
set_setting('smtp_pass', $data['smtp_pass']);
set_setting('smtp_enc', $data['smtp_enc']);
set_setting('smtp_skip_ssl_verify', $data['smtp_skip_ssl_verify'] ?? '0');
set_setting('mail_sender_name', $data['mail_sender_name']);
set_setting('mail_sender_email', $data['mail_sender_email']);

ob_start();
$success = Mail::sendRaw($test_email, 'SMTP Test Email', '<h1>این یک ایمیل تست است.</h1><p>اگر این ایمیل را دریافت کردید، تنظیمات SMTP شما صحیح است.</p>', ['debug' => true]);
$debug_output = ob_get_clean();

// Restore original settings
foreach ($original_settings as $key => $val) {
    set_setting($key, $val);
}

echo json_encode([
    'success' => $success,
    'message' => $success ? 'ایمیل تست با موفقیت ارسال شد.' : 'ارسال ایمیل تست شکست خورد.',
    'debug' => $debug_output
]);
