<?php require_once __DIR__ . '/../../../includes/db.php'; ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'مدیریت' ?> - طلا آنلاین</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        'primary-hover': '#4f46e5',
                    },
                    borderRadius: {
                        'xl': '12px',
                        '2xl': '16px',
                        '3xl': '24px',
                    }
                }
            }
        }
    </script>

    <!-- Font & Custom Styles -->
    <link rel="stylesheet" href="../assets/css/admin-v3.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-[#f8fafc] text-slate-900 min-h-screen">
<?php include __DIR__ . '/sidebar.php'; ?>

<div class="lg:mr-[280px] min-h-screen flex flex-col transition-all duration-300">
    <!-- Top Navigation -->
    <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-100 px-4 lg:px-8 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <button class="lg:hidden p-2 hover:bg-slate-100 rounded-lg" id="mobile-menu-toggle">
                <i data-lucide="menu"></i>
            </button>
            <div class="hidden md:block">
                <h2 class="text-sm font-bold text-slate-400">پنل مدیریت / <span class="text-slate-900"><?= $page_title ?? 'داشبورد' ?></span></h2>
            </div>
        </div>

        <div class="flex items-center gap-3 lg:gap-6">
            <button class="relative p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-colors">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute top-2 left-2 w-2 h-2 bg-rose-500 rounded-full border-2 border-white"></span>
            </button>

            <div class="h-8 w-px bg-slate-100"></div>

            <div class="flex items-center gap-3 pl-2">
                <div class="text-left hidden lg:block">
                    <p class="text-xs font-bold leading-tight">مدیر سیستم</p>
                    <p class="text-[10px] text-slate-400">برخط</p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600 border border-indigo-200 shadow-inner">
                    <i data-lucide="user" class="w-5 h-5"></i>
                </div>
            </div>
        </div>
    </header>

    <!-- Page Content -->
    <main class="flex-grow p-4 lg:p-8">
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-black text-slate-900 leading-tight"><?= $page_title ?? 'داشبورد' ?></h1>
                <p class="text-slate-400 mt-1 font-medium"><?= $page_subtitle ?? '' ?></p>
            </div>
            <div class="flex items-center gap-3">
                <?php if(isset($header_action)) echo $header_action; ?>
            </div>
        </div>
