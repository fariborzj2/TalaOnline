<?php
header("Content-type: text/xml; charset=utf-8");
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
global $pdo;

date_default_timezone_set('Asia/Tehran');

$base_url = rtrim(get_base_url(), '/');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<?xml-stylesheet type="text/xsl" href="' . $base_url . '/sitemap.xsl"?>' . PHP_EOL;

// Default fallback to a stable date (project launch approx)
$lastmod_ts = strtotime('2025-01-01');

if ($pdo) {
    try {
        $updates = [];
        // Check tables individually to avoid total failure
        $tables = ['items', 'categories', 'settings', 'blog_posts', 'blog_categories'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT MAX(updated_at) FROM $table");
                if ($stmt) {
                    $val = $stmt->fetchColumn();
                    if ($val) $updates[] = strtotime($val);
                }
            } catch (Exception $e) {}
        }

        if (!empty($updates)) {
            $lastmod_ts = max($updates);
        }
    } catch (Exception $e) {}
}

$lastmod = date('Y-m-d\TH:i:sP', $lastmod_ts);
?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc><?= $base_url ?>/sitemap-pages.xml</loc>
        <lastmod><?= $lastmod ?></lastmod>
    </sitemap>
    <sitemap>
        <loc><?= $base_url ?>/sitemap-categories.xml</loc>
        <lastmod><?= $lastmod ?></lastmod>
    </sitemap>
    <sitemap>
        <loc><?= $base_url ?>/sitemap-items.xml</loc>
        <lastmod><?= $lastmod ?></lastmod>
    </sitemap>
    <sitemap>
        <loc><?= $base_url ?>/sitemap-posts.xml</loc>
        <lastmod><?= $lastmod ?></lastmod>
    </sitemap>
</sitemapindex>
