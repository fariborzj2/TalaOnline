<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
session_start();

$error = '';

// Rate Limiting Configuration
$max_attempts = 5;
$lockout_minutes = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $identifier = convert_to_en_num($_POST['username'] ?? ''); // Email or Phone
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // 1. Verify CSRF Token
    if (!verify_csrf_token($csrf_token)) {
        die('Security violation: Invalid CSRF token.');
    }

    // 2. Check Rate Limit
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempt_time > datetime('now', '-' || ? || ' minutes')");
        $stmt->execute([$ip, $lockout_minutes]);
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        $stmt->execute([$ip, $lockout_minutes]);
    }
    $failed_attempts = $stmt->fetchColumn();

    if ($failed_attempts >= $max_attempts) {
        $error = 'تعداد تلاش‌های ناموفق بیش از حد مجاز است. لطفا ۱۵ دقیقه صبر کنید.';
    } else {
        // 3. Authenticate
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR phone = ?) AND role_id > 0");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Success: Clear failed attempts
            $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE ip = ?");
            $stmt->execute([$ip]);

            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_role_id'] = $user['role_id'];
            $_SESSION['user_avatar'] = $user['avatar'] ?? '';

            header('Location: index.php');
            exit;
        } else {
            // Failure: Log attempt
            $stmt = $pdo->prepare("INSERT INTO login_attempts (ip) VALUES (?)");
            $stmt->execute([$ip]);
            $error = 'نام کاربری یا رمز عبور اشتباه است.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به مدیریت - طلا آنلاین</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&amp;display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        body {
            font-family: 'Vazirmatn', sans-serif;
        }

        @layer components {
            .login-input {
                @apply w-full border border-slate-200 bg-white rounded-lg pl-12 pr-12 py-2.5 outline-none transition-all duration-200 font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10;
            }
            .login-button {
                @apply w-full bg-indigo-600 text-white py-2.5 rounded-lg font-black text-xs hover:bg-indigo-700 active:scale-[0.98] transition-all flex items-center justify-center gap-3;
            }
        }
    </style>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#f8fafc] flex items-center justify-center min-h-screen p-4 overflow-hidden relative">

    <!-- Decorative background elements -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-100/50 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-amber-100/50 rounded-full blur-[120px]"></div>

    <div class="w-full max-w-[400px] relative z-10">
        <div class="bg-white p-8 md:p-10 rounded-xl border border-slate-200">
            <div class="text-center mb-8 md:mb-10">
                <div class="w-16 h-16 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-6 ring-8 ring-indigo-50">
                    <i data-lucide="shield-check" class="text-white w-8 h-8"></i>
                </div>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">خوش آمدید</h1>
                <p class="text-slate-400 mt-2 font-bold uppercase  text-[10px]">احراز هویت مدیر سیستم</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 animate-shake">
                    <div class="bg-rose-50 border border-rose-100 rounded-xl p-3 flex items-center gap-3 text-rose-700">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <span class="font-bold text-sm"><?= $error ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4 md:space-y-5">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="space-y-1.5">
                    <label class="block pr-1 font-black text-slate-700 text-xs">ایمیل یا شماره موبایل</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                            <i data-lucide="user" class="w-4 h-4"></i>
                        </span>
                        <input type="text" name="username" required autocomplete="username" placeholder="Email or Phone" class="login-input ltr-input">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block pr-1 font-black text-slate-700 text-xs">رمز عبور</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                            <i data-lucide="key-round" class="w-4 h-4"></i>
                        </span>
                        <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="login-input ltr-input">
                        <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400 hover:text-indigo-600 transition-colors">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-button">
                    <span>ورود به پنل مدیریت</span>
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </button>
            </form>

            <div class="mt-8 md:mt-10 pt-8 border-t border-slate-100 text-center">
                <a href="../" class="inline-flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-xs transition-colors group">
                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                    <span>بازگشت به صفحه اصلی سایت</span>
                </a>
            </div>
        </div>

        <p class="text-center mt-8 text-slate-300 text-[10px] font-bold uppercase tracking-[0.3em]">Designed with precision &copy; 2026</p>
    </div>

<script>
    lucide.createIcons();

    /**
     * Converts Persian and Arabic numerals to English numerals
     */
    function convertDigitsToEnglish(str) {
        if (!str || typeof str !== 'string') return str;
        const persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        const english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        let result = str;
        for (let i = 0; i < 10; i++) {
            result = result.replace(new RegExp(persian[i], 'g'), english[i]);
            result = result.replace(new RegExp(arabic[i], 'g'), english[i]);
        }
        return result;
    }

    // Convert numerals on form submission
    document.querySelector('form').addEventListener('submit', function() {
        const usernameInput = this.querySelector('input[name="username"]');
        if (usernameInput) {
            usernameInput.value = convertDigitsToEnglish(usernameInput.value);
        }
    });

    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.setAttribute('data-lucide', 'eye-off');
        } else {
            input.type = 'password';
            icon.setAttribute('data-lucide', 'eye');
        }
        lucide.createIcons();
    }
</script>

<style>
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-8px); }
        75% { transform: translateX(8px); }
    }
    .animate-shake {
        animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
    }
</style>
</body>
</html>
