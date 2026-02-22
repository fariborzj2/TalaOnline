<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/mail.php';
session_start();

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if (empty($token)) {
    $error = 'توکن تایید نامعتبر است.';
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'توکن تایید یافت نشد یا قبلاً استفاده شده است.';
        } else {
            // Check expiry
            $expires_at = strtotime($user['verification_token_expires_at']);
            if ($expires_at < time()) {
                $error = 'لینک تایید منقضی شده است. لطفاً دوباره ثبت‌نام کنید یا درخواست لینک جدید بدهید.';
            } else {
                // Activate account
                $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, verification_token_expires_at = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);

                $success = true;

                // Update session if it's the same user
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']) {
                    $_SESSION['is_verified'] = 1;
                }

                // Send Welcome Email (Queued)
                Mail::queue($user['email'], 'welcome', [
                    'name' => $user['name']
                ]);
            }
        }
    } catch (Exception $e) {
        $error = 'خطایی در سیستم رخ داد: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تایید حساب کاربری</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100;400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Vazirmatn', sans-serif; }
    </style>
    <script>
        // Trigger mail worker to send welcome email immediately
        fetch('/api/mail_worker.php').catch(() => {});
    </script>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-8 rounded-3xl shadow-xl shadow-slate-200 border border-slate-100 max-w-md w-full text-center">
        <?php if ($success): ?>
            <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 mb-4">حساب کاربری فعال شد!</h1>
            <p class="text-slate-500 mb-8 leading-relaxed">ایمیل شما با موفقیت تایید شد. اکنون می‌توانید از تمامی امکانات سایت استفاده کنید.</p>
            <a href="/" class="block w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 transition-colors">بازگشت به صفحه اصلی</a>
        <?php else: ?>
            <div class="w-20 h-20 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <h1 class="text-2xl font-black text-slate-800 mb-4">خطا در تایید حساب</h1>
            <p class="text-slate-500 mb-8 leading-relaxed"><?= htmlspecialchars($error) ?></p>
            <a href="/" class="block w-full py-4 bg-slate-800 text-white rounded-2xl font-bold hover:bg-slate-900 transition-colors">بازگشت به سایت</a>
        <?php endif; ?>
    </div>
</body>
</html>
