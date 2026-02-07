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
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    borderRadius: {
                        '3xl': '24px',
                        '4xl': '32px',
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="../assets/css/admin-v3.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#f8fafc] flex items-center justify-center min-h-screen p-4 overflow-hidden relative">

    <!-- Decorative background elements -->
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-100/50 rounded-full blur-[120px]"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-amber-100/50 rounded-full blur-[120px]"></div>

    <div class="w-full max-w-[480px] relative z-10">
        <div class="bg-white/70 backdrop-blur-2xl p-8 lg:p-12 rounded-4xl border border-white shadow-2xl shadow-indigo-100/50">
            <div class="text-center mb-10">
                <div class="w-20 h-20 bg-indigo-600 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-xl shadow-indigo-200 ring-8 ring-indigo-50">
                    <i data-lucide="shield-check" class="text-white w-10 h-10"></i>
                </div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">خوش آمدید</h1>
                <p class="text-slate-400 mt-2 font-bold uppercase tracking-widest text-xs">احراز هویت مدیر سیستم</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 animate-shake">
                    <div class="bg-rose-50 border border-rose-100 rounded-2xl p-4 flex items-center gap-3 text-rose-700">
                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                        <span class="font-bold text-sm"><?= $error ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div class="space-y-2">
                    <label class="block pr-1 font-black text-slate-700 text-sm">نام کاربری</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </span>
                        <input type="text" name="username" required autocomplete="username" placeholder="Username" class="pr-12 bg-white/50 border-slate-100 focus:bg-white transition-all">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block pr-1 font-black text-slate-700 text-sm">رمز عبور</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 group-focus-within:text-indigo-600 transition-colors">
                            <i data-lucide="key-round" class="w-5 h-5"></i>
                        </span>
                        <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••" class="pr-12 bg-white/50 border-slate-100 focus:bg-white transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black text-lg shadow-xl shadow-indigo-200 hover:bg-indigo-700 active:scale-95 transition-all flex items-center justify-center gap-3">
                    <span>ورود به پنل مدیریت</span>
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </button>
            </form>

            <div class="mt-10 pt-8 border-t border-slate-100 text-center">
                <a href="../" class="inline-flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-sm transition-colors group">
                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                    <span>بازگشت به صفحه اصلی سایت</span>
                </a>
            </div>
        </div>

        <p class="text-center mt-8 text-slate-300 text-[10px] font-bold uppercase tracking-[0.2em]">Designed with precision &copy; 2026</p>
    </div>

<script>
    lucide.createIcons();
</script>

<style>
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    .animate-shake {
        animation: shake 0.3s ease-in-out;
    }
</style>
</body>
</html>
