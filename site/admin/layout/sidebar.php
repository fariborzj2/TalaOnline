<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside id="sidebar" class="fixed right-0 top-0 h-screen w-[280px] bg-white border-l border-slate-100 z-50 transition-transform duration-300 lg:translate-x-0 translate-x-full">
    <div class="p-6 h-full flex flex-col">
        <!-- Logo Area -->
        <div class="flex items-center gap-3 mb-10 px-2">
            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200">
                <i data-lucide="sparkles" class="text-white w-6 h-6"></i>
            </div>
            <div>
                <h1 class="font-black text-xl tracking-tight">طلا آنلاین</h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Core Admin v3</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-grow space-y-1">
            <p class="text-[11px] font-black text-slate-400 px-4 mb-3 uppercase tracking-widest">اصلی</p>

            <a href="index.php" class="nav-link-v3 group <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i data-lucide="layout-grid" class="w-5 h-5"></i>
                <span class="font-bold">داشبورد مدیریتی</span>
            </a>

            <a href="items.php" class="nav-link-v3 group <?= $current_page == 'items.php' ? 'active' : '' ?>">
                <i data-lucide="coins" class="w-5 h-5"></i>
                <span class="font-bold">مدیریت دارایی‌ها</span>
            </a>

            <a href="platforms.php" class="nav-link-v3 group <?= $current_page == 'platforms.php' ? 'active' : '' ?>">
                <i data-lucide="layers" class="w-5 h-5"></i>
                <span class="font-bold">پلتفرم‌های تبادل</span>
            </a>

            <div class="pt-6">
                <p class="text-[11px] font-black text-slate-400 px-4 mb-3 uppercase tracking-widest">تنظیمات</p>
                <a href="settings.php" class="nav-link-v3 group <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                    <i data-lucide="settings" class="w-5 h-5"></i>
                    <span class="font-bold">تنظیمات سیستمی</span>
                </a>
                <a href="sync.php" class="nav-link-v3 group <?= $current_page == 'sync.php' ? 'active' : '' ?>">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                    <span class="font-bold">همگام‌سازی داده‌ها</span>
                </a>
            </div>
        </nav>

        <!-- Footer Area -->
        <div class="pt-6 border-t border-slate-50">
            <div class="bg-indigo-50 rounded-2xl p-4 mb-4 relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">وضعیت سرور</p>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                        <p class="text-xs font-bold text-indigo-900">عملیاتی و پایدار</p>
                    </div>
                </div>
                <i data-lucide="shield-check" class="absolute -bottom-2 -left-2 w-12 h-12 text-indigo-100/50 group-hover:scale-110 transition-transform"></i>
            </div>

            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-rose-500 hover:bg-rose-50 rounded-xl transition-all font-bold">
                <i data-lucide="log-out" class="w-5 h-5"></i>
                <span>خروج از پنل</span>
            </a>
        </div>
    </div>
</aside>

<!-- Overlay for Mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 z-40 lg:hidden hidden backdrop-blur-sm transition-opacity duration-300 opacity-0"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('mobile-menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (toggle) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('translate-x-full');
                overlay.classList.toggle('hidden');
                setTimeout(() => overlay.classList.toggle('opacity-0'), 0);
            });
        }

        if (overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.add('translate-x-full');
                overlay.classList.add('opacity-0');
                setTimeout(() => overlay.classList.add('hidden'), 300);
            });
        }
    });
</script>
