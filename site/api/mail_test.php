<?php
/**
 * SMTP Connection Tester - Enhanced for clean JSON output
 */

// Disable error reporting to prevent non-JSON output
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/mail.php';

session_start();

// Ensure only admins can test
if (!isset($_SESSION['user_role_id']) || $_SESSION['user_role_id'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'دیتا نامعتبر']);
    exit;
}

$test_email = $data['test_email'] ?? '';
if (empty($test_email)) {
    echo json_encode(['success' => false, 'message' => 'ایمیل تست الزامی است']);
    exit;
}

// Release session lock immediately
session_write_close();

// Capture PHPMailer debug output
ob_start();

try {
    $config = [
        'mail_enabled' => '1',
        'mail_driver' => 'smtp',
        'smtp_host' => $data['smtp_host'] ?? get_setting('smtp_host'),
        'smtp_port' => $data['smtp_port'] ?? get_setting('smtp_port'),
        'smtp_user' => $data['smtp_user'] ?? get_setting('smtp_user'),
        'smtp_pass' => $data['smtp_pass'] ?? get_setting('smtp_pass'),
        'smtp_enc' => $data['smtp_enc'] ?? get_setting('smtp_enc'),
        'smtp_skip_ssl_verify' => $data['smtp_skip_ssl_verify'] ?? get_setting('smtp_skip_ssl_verify', '0'),
        'mail_sender_name' => $data['mail_sender_name'] ?? get_setting('mail_sender_name'),
        'mail_sender_email' => $data['mail_sender_email'] ?? get_setting('mail_sender_email')
    ];

    $template_slug = $data['template_slug'] ?? '';

    if ($template_slug) {
        // Test a specific template
        $success = Mail::send($test_email, $template_slug, [
            'name' => 'کاربر تست',
            'verification_link' => get_site_url() . '/api/verify.php?token=test_token'
        ]);
    } else {
        // Generic SMTP test
        $test_body = Mail::getProfessionalLayout('<h1>این یک ایمیل تست است.</h1><p>اگر این ایمیل را دریافت کردید، تنظیمات SMTP شما صحیح است.</p>');
        $success = Mail::sendRaw($test_email, 'SMTP Test Email', $test_body, [
            'debug' => true,
            'config' => $config
        ]);
    }

    $debug_output = ob_get_clean();

    // Ensure no garbage output before JSON
    if (ob_get_length()) ob_clean();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'ایمیل تست با موفقیت ارسال شد.' : 'ارسال ایمیل تست شکست خورد.',
        'debug' => $debug_output
    ]);

} catch (Exception $e) {
    $debug_output = ob_get_clean();
    if (ob_get_length()) ob_clean();

    echo json_encode([
        'success' => false,
        'message' => 'خطای سیستمی: ' . $e->getMessage(),
        'debug' => $debug_output
    ]);
}
