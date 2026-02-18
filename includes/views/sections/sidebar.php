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
        $popular_posts = $pdo->query("SELECT p.*, c.slug as category_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.status = 'published' ORDER BY p.views DESC LIMIT 5")->fetchAll();
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

     <?= View::renderComponent('news_card', ['news' => $news]) ?>

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

    <?php if (!empty($popular_posts)): ?>
    <div class="bg-block border radius-20 p-1-5 ">
        <div class="d-flex align-center gap-05 mb-1-5 border-bottom pb-1">
            <i data-lucide="flame" class="icon-size-4 text-error"></i>
            <h3 class="font-size-2 font-bold">داغ‌ترین مطالب</h3>
        </div>
        <div class="d-column gap-1">
            <?php foreach ($popular_posts as $idx => $p): ?>
            <a href="/blog/<?= htmlspecialchars($p['category_slug'] ?? 'uncategorized') ?>/<?= htmlspecialchars($p['slug']) ?>" class="popular-item d-flex gap-1 group">
                <div class="popular-number"><?= $idx + 1 ?></div>
                <div class="d-column gap-02">
                    <h4 class="text-[11px] font-black text-title ellipsis-y ellipsis-y line-clamp-2 transition-colors"><?= htmlspecialchars($p['title']) ?></h4>
                    <span class="text-[9px] opacity-50"><?= number_format($p['views']) ?> بازدید</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</aside>

<style>
    .cat-link { color: var(--color-text); }
    .cat-link:hover { background: #f8fafc; color: var(--color-primary); }
    .cat-link.active { background: var(--bg-warning); color: var(--color-warning); }

    .popular-item { text-decoration: none; }
    .popular-number {
        font-size: 20px;
        font-weight: 900;
        color: var(--color-primary);
        opacity: 0.15;
        line-height: 1;
        transition: opacity 0.3s;
    }
    .popular-item:hover .popular-number { opacity: 0.8; }
    .popular-item:hover h4 { color: var(--color-primary); }

    .sidebar {
        width: 350px;
        flex-shrink: 0;
        overflow: hidden;
    }
</style>
