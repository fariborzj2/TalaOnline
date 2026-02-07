<?php
require_once __DIR__ . '/../auth.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>مدیریت طلا آنلاین</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        @import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100;200;300;400;500;600;700;800;900&display=swap');

        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
        }

        body {
            font-family: 'Vazirmatn', sans-serif;
            @apply bg-[#f8fafc] text-slate-900 antialiased;
        }

        @layer base {
            h1, h2, h3, h4, h5, h6 { @apply font-black; }
            label { @apply block pr-1 font-black text-slate-700 text-sm mb-2; }
            input[type="text"], input[type="password"], input[type="email"], input[type="number"], select, textarea {
                @apply w-full border-2 border-slate-100 bg-slate-50/50 rounded-2xl px-5 py-3.5 outline-none transition-all duration-200 font-bold focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 focus:bg-white;
            }
        }

        @layer components {
            .glass-card {
                @apply bg-white/80 backdrop-blur-xl rounded-[2rem] border border-white shadow-xl shadow-indigo-100/30;
            }
            .admin-table {
                @apply w-full border-separate border-spacing-0;
            }
            .admin-table th {
                @apply bg-slate-50/50 border-y border-slate-100 px-6 py-4 text-right text-[11px] font-black text-slate-400 uppercase tracking-widest first:rounded-r-2xl last:rounded-l-2xl first:border-r last:border-l;
            }
            .admin-table td {
                @apply px-6 py-5 border-b border-slate-50 text-sm font-bold text-slate-700 transition-colors;
            }
            .admin-table tr:last-child td {
                @apply border-b-0;
            }
            .admin-table tr:hover td {
                @apply bg-slate-50/30;
            }
            .btn-v3 {
                @apply px-6 py-3.5 rounded-2xl font-black text-sm transition-all flex items-center justify-center gap-2 active:scale-95;
            }
            .btn-v3-primary {
                @apply bg-indigo-600 text-white shadow-lg shadow-indigo-200 hover:bg-indigo-700;
            }
            .btn-v3-outline {
                @apply bg-white text-slate-600 border border-slate-200 hover:bg-slate-50;
            }
            .sidebar-link {
                @apply flex items-center gap-4 px-5 py-4 rounded-[1.25rem] font-bold text-slate-400 transition-all duration-300 hover:bg-slate-50 hover:text-indigo-600;
            }
            .sidebar-link.active {
                @apply bg-indigo-50 text-indigo-700 shadow-sm;
            }
            .sidebar-link i {
                @apply w-6 h-6;
            }
        }

        @keyframes modalUp {
            from { opacity: 0; transform: translateY(20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .animate-modal-up {
            animation: modalUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
    </style>

    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="min-h-screen flex flex-col lg:flex-row overflow-x-hidden">
    <!-- Mobile Header -->
    <header class="lg:hidden bg-white border-b border-slate-100 px-6 py-4 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200">
                <i data-lucide="shield-check" class="text-white w-6 h-6"></i>
            </div>
            <span class="font-black text-lg text-slate-900">مدیریت</span>
        </div>
        <button onclick="toggleSidebar()" class="p-2 text-slate-500">
            <i data-lucide="menu" class="w-7 h-7"></i>
        </button>
    </header>

    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 lg:ml-0 p-6 lg:p-10 max-w-7xl mx-auto w-full">
        <!-- Dashboard Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">
                    <?= $page_title ?? 'مدیریت' ?>
                </h1>
                <?php if (isset($page_subtitle)): ?>
                    <p class="text-slate-400 mt-1 font-bold"><?= $page_subtitle ?></p>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-4">
                <?php if (isset($header_action)) echo $header_action; ?>

                <div class="hidden sm:flex items-center gap-3 pr-4 border-r border-slate-100">
                    <div class="flex flex-col items-end">
                        <span class="text-sm font-black text-slate-900"><?= $_SESSION['admin_username'] ?></span>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Administrator</span>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center font-black">
                        <?= strtoupper(substr($_SESSION['admin_username'], 0, 1)) ?>
                    </div>
                </div>

                <a href="logout.php" class="w-12 h-12 bg-white text-rose-500 border border-rose-50 rounded-2xl flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all shadow-sm group" title="خروج">
                    <i data-lucide="power" class="w-6 h-6 group-hover:rotate-12 transition-transform"></i>
                </a>
            </div>
        </div>
