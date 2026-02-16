<?php
header("Content-type: text/xml; charset=utf-8");
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
global $pdo;

date_default_timezone_set('Asia/Tehran');

$base_url = rtrim(get_base_url(), '/');

$latest_update_ts = strtotime('2025-01-01');
$about_update_ts = strtotime('2025-01-01');

if ($pdo) {
    try {
        $updates = [];
        $tables = ['items', 'categories', 'settings'];
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
            $latest_update_ts = max($updates);
        }

        $stmt = $pdo->query("SELECT updated_at FROM settings WHERE setting_key = 'about_us_content'");
        $row = $stmt->fetch();
        if ($row && $row['updated_at']) {
            $about_update_ts = strtotime($row['updated_at']);
        }
    } catch (Exception $e) {}
}

$latest_update = date('Y-m-d\TH:i:sP', $latest_update_ts);
$about_update = date('Y-m-d\TH:i:sP', $about_update_ts);

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<?xml-stylesheet type="text/xsl" href="' . $base_url . '/sitemap.xsl"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= $base_url ?>/</loc>
        <lastmod><?= $latest_update ?></lastmod>
        <changefreq>always</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= $base_url ?>/about-us</loc>
        <lastmod><?= $about_update ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?= $base_url ?>/feedback</loc>
        <lastmod><?= date('Y-m-d\TH:i:sP', strtotime('2025-01-01')) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
</urlset>
