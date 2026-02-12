<!-- Sidebar Overlay -->
<div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 lg:hidden hidden"></div>

<?php
// Fetch unread feedback count
$unread_feedbacks_count = 0;
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM feedbacks WHERE is_read = 0");
        $unread_feedbacks_count = $stmt->fetchColumn();
    } catch (Exception $e) {}
}
?>
<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 right-0 w-64 bg-white border-l border-slate-100 transform translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:flex flex-col transition-transform duration-300 ease-in-out z-50 lg:shadow-none lg:overflow-y-auto">
    <div class="p-5 pb-4">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center ring-4 ring-indigo-50">
                <i data-lucide="shield-check" class="text-white w-6 h-6"></i>
            </div>
            <div>
                <h2 class="font-black text-base text-slate-900 leading-tight">طلا آنلاین</h2>
                <p class="text-[9px] font-bold text-slate-400 uppercase  mt-0.5">Core Admin v4.0</p>
            </div>
        </div>

        <nav class="space-y-1">
            <a href="index.php" class="sidebar-link <?= $current_page == 'index.php' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard" class="w-6 h-6"></i>
                <span>پیشخوان</span>
            </a>
            <a href="items.php" class="sidebar-link <?= $current_page == 'items.php' ? 'active' : '' ?>">
                <i data-lucide="coins" class="w-6 h-6"></i>
                <span>ارزها</span>
            </a>
            <a href="categories.php" class="sidebar-link <?= $current_page == 'categories.php' ? 'active' : '' ?>">
                <i data-lucide="layers" class="w-6 h-6"></i>
                <span>دسته‌بندی‌ها</span>
            </a>
            <a href="rss_feeds.php" class="sidebar-link <?= $current_page == 'rss_feeds.php' ? 'active' : '' ?>">
                <i data-lucide="rss" class="w-6 h-6"></i>
                <span>خبرخوان</span>
            </a>
            <a href="platforms.php" class="sidebar-link <?= $current_page == 'platforms.php' ? 'active' : '' ?>">
                <i data-lucide="building-2" class="w-6 h-6"></i>
                <span>پتلفرم‌ها</span>
            </a>
            <a href="feedbacks.php" class="sidebar-link <?= $current_page == 'feedbacks.php' ? 'active' : '' ?> flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <i data-lucide="mail" class="w-6 h-6"></i>
                    <span>پیام‌ها</span>
                </div>
                <?php if ($unread_feedbacks_count > 0): ?>
                    <span class="bg-rose-500 text-white text-[10px] font-black px-1.5 py-0.5 rounded-full min-w-[20px] text-center">
                        <?= $unread_feedbacks_count ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="about.php" class="sidebar-link <?= $current_page == 'about.php' ? 'active' : '' ?>">
                <i data-lucide="book-open" class="w-6 h-6"></i>
                <span>درباره ما</span>
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
                <p class="text-indigo-100 text-[9px] font-bold uppercase  mb-1">نسخه سیستم</p>
                <h3 class="font-black text-sm mb-3">4.2.0-STABLE</h3>
                <a href="../" target="_blank" class="block w-full text-center bg-white/20 hover:bg-white/30 py-2 rounded-lg text-[10px] font-bold transition-all">
                    مشاهده وب‌سایت
                </a>
            </div>
        </div>
    </div>

    <div class="flex items-center p-5 gap-2 md:gap-4">
        <div class="hidden sm:flex items-center gap-3 pl-4 border-l border-slate-100">
            <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center font-black">
                <?= strtoupper(substr($_SESSION['admin_username'], 0, 1)) ?>
            </div>
            <div class="flex flex-col items-start">
                <span class="text-sm font-black text-slate-900"><?= $_SESSION['admin_username'] ?></span>
                <span class="text-[10px] font-bold text-slate-400 uppercase ">Administrator</span>
            </div>
        </div>

        <a href="logout.php" class="w-10 h-10 mr-auto md:w-10 md:h-10 bg-white text-rose-500 border border-rose-100 rounded-lg flex items-center justify-center hover:bg-rose-500 hover:text-white transition-all group" title="خروج">
            <i data-lucide="power" class="w-5 h-5 group-hover:rotate-12 transition-transform"></i>
        </a>
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
