<?php
$current_page = basename($_SERVER['PHP_SELF']);

function is_active_group($pages) {
    global $current_page;
    return in_array($current_page, $pages);
}

$groups = [
    [
        'label' => 'اصلی',
        'icon' => 'layout-dashboard',
        'items' => [
            ['label' => 'داشبورد', 'url' => 'index.php', 'icon' => 'home'],
        ]
    ],
    [
        'label' => 'مدیریت بازار',
        'icon' => 'trending-up',
        'pages' => ['items.php', 'item_edit.php', 'categories.php', 'category_edit.php', 'platforms.php', 'rss_feeds.php'],
        'items' => [
            ['label' => 'لیست دارایی‌ها', 'url' => 'items.php', 'icon' => 'coins'],
            ['label' => 'دسته‌بندی‌ها', 'url' => 'categories.php', 'icon' => 'layers'],
            ['label' => 'پلتفرم‌ها', 'url' => 'platforms.php', 'icon' => 'briefcase'],
            ['label' => 'فیدهای RSS', 'url' => 'rss_feeds.php', 'icon' => 'rss'],
        ]
    ],
    [
        'label' => 'محتوا و وبلاگ',
        'icon' => 'file-text',
        'pages' => ['posts.php', 'post_edit.php', 'blog_categories.php', 'blog_category_edit.php', 'blog_settings.php', 'about.php'],
        'items' => [
            ['label' => 'نوشته‌ها', 'url' => 'posts.php', 'icon' => 'pen-tool'],
            ['label' => 'دسته‌بندی وبلاگ', 'url' => 'blog_categories.php', 'icon' => 'folder-open'],
            ['label' => 'تنظیمات وبلاگ', 'url' => 'blog_settings.php', 'icon' => 'settings-2'],
            ['label' => 'درباره ما', 'url' => 'about.php', 'icon' => 'info'],
        ]
    ],
    [
        'label' => 'سیستم',
        'icon' => 'settings',
        'pages' => ['feedbacks.php', 'settings.php'],
        'items' => [
            ['label' => 'نظرات و بازخورد', 'url' => 'feedbacks.php', 'icon' => 'message-square'],
            ['label' => 'تنظیمات عمومی', 'url' => 'settings.php', 'icon' => 'sliders'],
        ]
    ]
];
?>

<aside id="sidebar" class="fixed lg:sticky top-0 right-0 h-screen w-72 bg-white border-l border-slate-100 flex-shrink-0 z-50 transition-transform duration-300 translate-x-full lg:translate-x-0 overflow-y-auto">
    <div class="p-8">
        <div class="flex items-center gap-4 mb-10 pr-2">
            <div class="w-12 h-12 bg-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-200">
                <i data-lucide="shield-check" class="text-white w-7 h-7"></i>
            </div>
            <div>
                <span class="font-black text-xl text-slate-900 block leading-tight">مدیریت</span>
                <span class="text-slate-400 font-bold text-[10px] uppercase tracking-widest">Tala Online</span>
            </div>
        </div>

        <nav class="space-y-2">
            <?php foreach ($groups as $group): ?>
                <?php if (count($group['items']) === 1 && !isset($group['pages'])): ?>
                    <?php $item = $group['items'][0]; ?>
                    <a href="<?= $item['url'] ?>" class="sidebar-link <?= $current_page == $item['url'] ? 'active' : '' ?>">
                        <i data-lucide="<?= $item['icon'] ?>"></i>
                        <span><?= $item['label'] ?></span>
                    </a>
                <?php else: ?>
                    <?php $isOpen = is_active_group($group['pages'] ?? []); ?>
                    <div class="group-container">
                        <button onclick="toggleGroup(this)" class="sidebar-group-btn <?= $isOpen ? 'active' : '' ?>">
                            <div class="flex items-center gap-4">
                                <i data-lucide="<?= $group['icon'] ?>"></i>
                                <span><?= $group['label'] ?></span>
                            </div>
                            <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-300 dropdown-arrow <?= $isOpen ? 'rotate-180' : '' ?>"></i>
                        </button>
                        <div class="group-content overflow-hidden transition-all duration-300" style="max-height: <?= $isOpen ? '500px' : '0' ?>;">
                            <div class="pr-6 pt-1 pb-2 space-y-1">
                                <?php foreach ($group['items'] as $item): ?>
                                    <a href="<?= $item['url'] ?>" class="sidebar-sub-link <?= $current_page == $item['url'] ? 'active' : '' ?>">
                                        <div class="flex items-center gap-3">
                                            <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4"></i>
                                            <span><?= $item['label'] ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <div class="pt-6 mt-6 border-t border-slate-50">
                <a href="../" target="_blank" class="sidebar-link">
                    <i data-lucide="external-link"></i>
                    <span>مشاهده سایت</span>
                </a>
                <a href="logout.php" class="sidebar-link text-rose-500 hover:bg-rose-50 hover:text-rose-600">
                    <i data-lucide="log-out"></i>
                    <span>خروج</span>
                </a>
            </div>
        </nav>
    </div>
</aside>

<style type="text/tailwindcss">
    @layer components {
        .sidebar-group-btn {
            @apply flex items-center justify-between w-full px-4 py-3 rounded-lg font-bold text-slate-400 transition-all duration-300 hover:bg-slate-50 hover:text-indigo-600;
        }
        .sidebar-group-btn.active {
            @apply text-indigo-700 font-black;
        }
        .sidebar-sub-link {
            @apply flex items-center gap-4 px-4 py-2.5 rounded-lg font-bold text-slate-400 transition-all duration-300 hover:bg-slate-50 hover:text-indigo-600 text-[12px];
        }
        .sidebar-sub-link.active {
            @apply bg-indigo-50/50 text-indigo-600;
        }
    }
</style>

<script>
function toggleGroup(btn) {
    const container = btn.closest('.group-container');
    const content = container.querySelector('.group-content');
    const arrow = btn.querySelector('.dropdown-arrow');
    const isActive = btn.classList.contains('active');

    // Close all other groups (optional, but cleaner)
    /*
    document.querySelectorAll('.sidebar-group-btn').forEach(otherBtn => {
        if (otherBtn !== btn) {
            otherBtn.classList.remove('active');
            const otherContent = otherBtn.closest('.group-container').querySelector('.group-content');
            const otherArrow = otherBtn.querySelector('.dropdown-arrow');
            otherContent.style.maxHeight = '0';
            otherArrow.classList.remove('rotate-180');
        }
    });
    */

    if (content.style.maxHeight === '0px' || content.style.maxHeight === '') {
        content.style.maxHeight = '500px';
        btn.classList.add('active');
        arrow.classList.add('rotate-180');
    } else {
        content.style.maxHeight = '0px';
        btn.classList.remove('active');
        arrow.classList.remove('rotate-180');
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('translate-x-full');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', (e) => {
    const sidebar = document.getElementById('sidebar');
    const menuBtn = document.querySelector('header button');
    if (window.innerWidth < 1024) {
        if (!sidebar.contains(e.target) && !menuBtn.contains(e.target) && !sidebar.classList.contains('translate-x-full')) {
            sidebar.classList.add('translate-x-full');
        }
    }
});
</script>
