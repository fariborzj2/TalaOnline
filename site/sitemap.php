<?php
header("Content-type: text/xml; charset=utf-8");
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$base_url = rtrim(get_base_url(), '/');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<?xml-stylesheet type="text/xsl" href="' . $base_url . '/sitemap.xsl"?>' . PHP_EOL;

$lastmod = date('Y-m-d\TH:i:sP');

if ($pdo) {
    try {
        // Robust way to get last update without breaking if column doesn't exist
        $stmt = $pdo->query("SELECT updated_at FROM items LIMIT 1");
        $has_updated_at = ($stmt !== false);

        if ($has_updated_at) {
            $stmt = $pdo->query("SELECT MAX(updated_at) as last_update FROM (
                SELECT updated_at FROM items
                UNION
                SELECT updated_at FROM categories
            ) as updates");
            $res = $stmt->fetch();
            if ($res && $res['last_update']) {
                $lastmod = date('Y-m-d\TH:i:sP', strtotime($res['last_update']));
            }
        }
    } catch (Exception $e) {
        // Fallback to current time is already set
    }
}
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
</sitemapindex>
