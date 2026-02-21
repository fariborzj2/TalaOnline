<?php
require_once __DIR__ . '/../../includes/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['username'] ?? ''; // Email or Phone
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE (email = ? OR phone = ?) AND role_id > 0");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_role_id'] = $user['role_id'];
        $_SESSION['user_avatar'] = $user['avatar'] ?? '';

        header('Location: index.php');
        exit;
    } else {
        $error = 'نام کاربری یا رمز عبور اشتباه است.';
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
                @apply w-full border border-slate-200 bg-white rounded-lg px-4 py-2.5 pr-12 outline-none transition-all duration-200 font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10;
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
                        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="login-input ltr-input">
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
