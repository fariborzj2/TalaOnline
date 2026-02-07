<!-- Sidebar Overlay -->
<div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 lg:hidden hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 right-0 w-72 bg-white border-l border-slate-100 transform translate-x-full lg:translate-x-0 lg:static lg:flex flex-col transition-transform duration-300 ease-in-out z-50 shadow-2xl lg:shadow-none">
    <div class="p-8 pb-4">
        <div class="flex items-center gap-4 mb-10">
            <div class="w-12 h-12 bg-indigo-600 rounded-[1.25rem] flex items-center justify-center shadow-2xl shadow-indigo-200 ring-4 ring-indigo-50">
                <i data-lucide="shield-check" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <h2 class="font-black text-xl text-slate-900">طلا آنلاین</h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Core Admin v4.0</p>
            </div>
        </div>

        <nav class="space-y-2">
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

    <div class="mt-auto p-8 border-t border-slate-50">
        <div class="bg-indigo-600 rounded-3xl p-6 text-white relative overflow-hidden shadow-xl shadow-indigo-100">
            <div class="relative z-10">
                <p class="text-indigo-100 text-[10px] font-bold uppercase tracking-widest mb-1">نسخه سیستم</p>
                <h3 class="font-black text-lg mb-4">4.2.0-STABLE</h3>
                <a href="../" target="_blank" class="block w-full text-center bg-white/20 backdrop-blur-md hover:bg-white/30 py-3 rounded-2xl text-sm font-bold transition-all">
                    مشاهده وب‌سایت
                </a>
            </div>
            <!-- Decorative circle -->
            <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
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
