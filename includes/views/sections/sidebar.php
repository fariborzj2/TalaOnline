<?php
require_once __DIR__ . '/../../rss_service.php';
require_once __DIR__ . '/../../db.php';

global $pdo;
$news = [];
if ($pdo) {
    $rssService = new RssService($pdo);
    $news_count = get_setting('news_count', 5);
    $news = $rssService->getLatestNews($news_count);
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
    <?= View::renderComponent('news_card', ['news' => $news]) ?>

    <!-- Possible future sidebar cards -->
    <div class="bg-block radius-16 pd-md border">
        <div class="d-flex align-center gap-05 mb-1">
            <i data-lucide="info" class="text-primary icon-size-4"></i>
            <h3 class="font-size-2 font-bold">راهنمای استفاده</h3>
        </div>
        <p class="font-size-1-2 line-height-2">
            قیمت‌ها هر ۱۰ دقیقه به صورت خودکار بروزرسانی می‌شوند. برای مشاهده نمودار تغییرات هر مورد، روی آن کلیک کنید.
        </p>
    </div>
</aside>

<style>
    .w-sidebar {
        width: 320px;
        flex-shrink: 0;
    }
    .w-sidebar .card {
        white-space: normal;
    }
    @media (max-width: 1100px) {
        .w-sidebar {
            display: none;
        }
    }
</style>
