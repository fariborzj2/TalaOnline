<?php
require_once __DIR__ . '/../../rss_service.php';
require_once __DIR__ . '/../../db.php';

global $pdo;
$news = [];
$blog_categories = [];
$popular_posts = [];

if ($pdo) {
    $rssService = new RssService($pdo);
    $news_count = get_setting('news_count', 5);
    $news = $rssService->getLatestNews($news_count);

    try {
        // Fetch blog categories
        $blog_categories = $pdo->query("SELECT * FROM blog_categories ORDER BY sort_order ASC")->fetchAll();

        // Fetch popular posts (by views)
        $popular_posts = $pdo->query("SELECT p.*, c.slug as category_slug, c.name as category_name FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.status = 'published' ORDER BY p.views DESC LIMIT 5")->fetchAll();
    } catch (Exception $e) {}
} else {
    // Mock news for verification when DB is not available
    $news = [
        ['title' => 'افزایش قیمت طلا در بازارهای جهانی', 'source' => 'خبرگزاری نمونه', 'link' => '#', 'pubDate' => date('Y-m-d H:i:s')],
        ['title' => 'بررسی روند بازار سکه در هفته جاری', 'source' => 'اقتصاد آنلاین', 'link' => '#', 'pubDate' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
        ['title' => 'پیش‌بینی قیمت دلار برای روزهای آینده', 'source' => 'دنیای اقتصاد', 'link' => '#', 'pubDate' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
    ];
}
?>
<aside class="sidebar d-column gap-md basis-300 grow-1">
    <!-- To make any card sticky, simply add the 'is-sticky' class to it -->
   
    <div class="bg-block radius-16 pd-md border">
        <div class="d-flex align-center gap-05 mb-1">
            <i data-lucide="info" class="text-primary icon-size-4"></i>
            <h2 class="font-size-2 font-bold">راهنمای استفاده</h2>
        </div>
        <p class="font-size-1-2 line-height-2">
            قیمت‌ها هر ۱۰ دقیقه به صورت خودکار بروزرسانی می‌شوند. برای مشاهده نمودار تغییرات هر مورد، روی آن کلیک کنید.
        </p>
    </div>

    <?php if (!empty($blog_categories)): ?>
    <div class="bg-block border radius-20 p-1-5 ">
        <div class="d-flex align-center gap-05 mb-1 border-bottom pb-1">
            <i data-lucide="layers-3" class="icon-size-4 text-primary"></i>
            <h3 class="font-size-2 font-bold">دسته‌بندی‌های وبلاگ</h3>
        </div>
        <ul class="d-column list-none">
            <?php
            $current_uri = $_SERVER['REQUEST_URI'];
            foreach ($blog_categories as $cat):
                $cat_url = '/blog/' . $cat['slug'];
                $is_active = (strpos($current_uri, $cat_url) !== false);
            ?>
            <li>
                <a href="<?= $cat_url ?>"
                   class="cat-link d-flex just-between align-center p-1 radius-10 transition-all <?= $is_active ? 'active' : '' ?>">
                    <span class="font-bold text-[12px]"><?= htmlspecialchars($cat['name']) ?></span>
                    <i data-lucide="chevron-left" class="icon-size-2 <?= $is_active ? '' : 'opacity-30' ?>"></i>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

     <?= View::renderComponent('news_card', ['news' => $news]) ?>

    <?php if (!empty($popular_posts)): ?>
    <div class="bg-block radius-16 pd-md border d-column gap-1">
        <div class="d-flex align-center gap-05 mb-05">
            <i data-lucide="flame" class="icon-size-4 text-error"></i>
            <h2 class="font-size-2 font-bold">داغ‌ترین مطالب</h2>
        </div>
        <div class="news-list d-column gap-1">
            <?php foreach ($popular_posts as $idx => $p): ?>
            <a href="/blog/<?= htmlspecialchars($p['category_slug'] ?? 'uncategorized') ?>/<?= htmlspecialchars($p['slug']) ?>" class="news-item d-flex gap-1 text-decoration-none group align-start">
                <?php if (!empty($p['thumbnail'])): ?>
                    <div class="news-image radius-8 overflow-hidden flex-shrink-0">
                        <img src="/<?= ltrim($p['thumbnail'], '/') ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="w-full h-full object-cover" loading="lazy" decoding="async" width="64" height="64">
                    </div>
                <?php endif; ?>
                <div class="d-column gap-025 flex-1 overflow-hidden">
                    <span class="d-flex text-gray font-size-0-8 gap-05 font-medium">
                        <span><?= htmlspecialchars($p['category_name'] ?? 'وبلاگ') ?></span>
                        <span class="opacity-50">•</span>
                        <span><?= jalali_time_tag($p['created_at']) ?></span>
                    </span>
                    <h3 class="font-size-1 text-title line-height-1-5 ellipsis-x transition-colors"><?= htmlspecialchars($p['title']) ?></h3>
                </div>
            </a>
            <?php if ($idx < count($popular_posts) - 1): ?>
                <div class="border-bottom opacity-05"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</aside>

<style>
    .cat-link { color: var(--color-text); }
    .cat-link:hover { background: #f8fafc; color: var(--color-primary); }
    .cat-link.active { background: var(--color-primary-light); color: var(--color-primary); }

    .news-item { transition: all 0.2s; }
    .news-item:hover h3 { color: var(--color-primary); }
    .news-image {
        width: 64px;
        min-width: 64px;
        height: 64px;
        background: var(--color-secondary);
    }
    .opacity-05 { opacity: 0.5; }
    .gap-025 { gap: 2px; }
    .object-cover { object-fit: cover; }

    .sidebar {
        width: 350px;
        flex-shrink: 0;
        overflow: hidden;
    }
</style>
