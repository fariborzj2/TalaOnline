<?php
require_once __DIR__ . '/../../includes/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
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

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100;200;300;400;500;600;700;800;900&display=swap');

        body {
            font-family: 'Vazirmatn', sans-serif;
        }

        @layer components {
            .login-input {
                @apply w-full border-2 border-slate-100 bg-white/50 rounded-2xl px-5 py-3.5 pr-14 outline-none transition-all duration-200 font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 focus:bg-white;
            }
            .login-button {
                @apply w-full bg-indigo-600 text-white py-4 md:py-5 rounded-2xl font-black text-base md:text-lg shadow-xl shadow-indigo-200 hover:bg-indigo-700 active:scale-[0.98] transition-all flex items-center justify-center gap-3;
            }
        }
    </style>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#f8fafc] flex items-center justify-center min-h-screen p-4 overflow-hidden relative">

    <!-- Decorative background elements -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-100/50 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-amber-100/50 rounded-full blur-[120px]"></div>

    <div class="w-full max-w-[440px] relative z-10">
        <div class="bg-white/80 backdrop-blur-2xl p-8 md:p-12 rounded-[32px] md:rounded-[40px] border border-white shadow-2xl shadow-indigo-100/50">
            <div class="text-center mb-8 md:mb-10">
                <div class="w-20 h-20 md:w-24 md:h-24 bg-indigo-600 rounded-[28px] md:rounded-[32px] flex items-center justify-center mx-auto mb-6 shadow-2xl shadow-indigo-200 ring-8 ring-indigo-50">
                    <i data-lucide="shield-check" class="text-white w-10 h-10 md:w-12 md:h-12"></i>
                </div>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">خوش آمدید</h1>
                <p class="text-slate-400 mt-2 font-bold uppercase tracking-widest text-[10px]">احراز هویت مدیر سیستم</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 animate-shake">
                    <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 flex items-center gap-3 text-rose-700">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <span class="font-bold text-sm"><?= $error ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5 md:space-y-6">
                <div class="space-y-2">
                    <label class="block pr-1 font-black text-slate-700 text-sm">نام کاربری</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </span>
                        <input type="text" name="username" required autocomplete="username" placeholder="Username" class="login-input">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block pr-1 font-black text-slate-700 text-sm">رمز عبور</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-5 text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                            <i data-lucide="key-round" class="w-5 h-5"></i>
                        </span>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="login-input">
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
