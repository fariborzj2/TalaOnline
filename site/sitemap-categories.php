<?php
header("Content-type: text/xml; charset=utf-8");
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
global $pdo;

date_default_timezone_set('Asia/Tehran');

$base_url = rtrim(get_base_url(), '/');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<?xml-stylesheet type="text/xsl" href="' . $base_url . '/sitemap.xsl"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                $lastmod = (!empty($row['updated_at'])) ? date('Y-m-d\TH:i:sP', strtotime($row['updated_at'])) : date('Y-m-d\TH:i:sP', strtotime('2025-01-01'));
                $loc = $base_url . '/' . htmlspecialchars($row['slug']);

                echo "    <url>\n";
                echo "        <loc>$loc</loc>\n";
                echo "        <lastmod>$lastmod</lastmod>\n";
                echo "        <changefreq>daily</changefreq>\n";
                echo "        <priority>0.8</priority>\n";
                echo "    </url>\n";
            }
        }
    } catch (Exception $e) {}
}
?>
</urlset>
