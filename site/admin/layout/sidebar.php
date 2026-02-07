<!-- Sidebar Overlay -->
<div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 lg:hidden hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 right-0 w-64 bg-white border-l border-slate-100 transform translate-x-full lg:translate-x-0 lg:static lg:flex flex-col transition-transform duration-300 ease-in-out z-50 lg:shadow-none">
    <div class="p-5 pb-4">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center ring-4 ring-indigo-50">
                <i data-lucide="shield-check" class="text-white w-6 h-6"></i>
            </div>
            <div>
                <h2 class="font-black text-base text-slate-900 leading-tight">طلا آنلاین</h2>
                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Core Admin v4.0</p>
            </div>
        </div>

        <nav class="space-y-1">
            <a href="index.php" class="sidebar-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
                <span>پیشخوان</span>
            </a>
            <a href="items.php" class="sidebar-link <?= $current_page == 'items.php' ? 'active' : '' ?>">
                <i data-lucide="coins" class="w-6 h-6"></i>
                <span>دارایی‌ها</span>
            </a>
            <a href="platforms.php" class="sidebar-link <?= $current_page == 'platforms.php' ? 'active' : '' ?>">
                <i data-lucide="building-2" class="w-6 h-6"></i>
                <span>سکوها</span>
            </a>
            <a href="settings.php" class="sidebar-link <?= $current_page == 'settings.php' ? 'active' : '' ?>">
                <i data-lucide="settings" class="w-6 h-6"></i>
                <span>تنظیمات</span>
            </a>
        </nav>
    </div>

    <div class="mt-auto p-5 border-t border-slate-50">
        <div class="bg-indigo-600 rounded-xl p-5 text-white relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-indigo-100 text-[9px] font-bold uppercase tracking-widest mb-1">نسخه سیستم</p>
                <h3 class="font-black text-sm mb-3">4.2.0-STABLE</h3>
                <a href="../" target="_blank" class="block w-full text-center bg-white/20 hover:bg-white/30 py-2 rounded-lg text-[10px] font-bold transition-all">
                    مشاهده وب‌سایت
                </a>
            </div>
        </div>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        sidebar.classList.toggle('translate-x-full');
        overlay.classList.toggle('hidden');
    }
</script>
