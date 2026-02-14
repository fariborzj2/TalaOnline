<?php
header("Content-type: text/xml");
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$base_url = get_base_url();

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= $base_url ?>/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>always</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= $base_url ?>/about-us</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?= $base_url ?>/feedback</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>
    <?php
    if ($pdo) {
        // Categories
        try {
            $stmt = $pdo->query("SELECT slug, updated_at FROM categories");
            while ($row = $stmt->fetch()) {
                $lastmod = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
                $loc = $base_url . '/' . htmlspecialchars($row['slug']);
                echo "    <url>\n";
                echo "        <loc>$loc</loc>\n";
                echo "        <lastmod>$lastmod</lastmod>\n";
                echo "        <changefreq>daily</changefreq>\n";
                echo "        <priority>0.8</priority>\n";
                echo "    </url>\n";
            }
        } catch (Exception $e) {}

        // Items
        try {
            $stmt = $pdo->query("SELECT slug, symbol, category, updated_at FROM items WHERE is_active = 1");
            while ($row = $stmt->fetch()) {
                $slug = $row['slug'] ?: $row['symbol'];
                $cat = $row['category'];
                $lastmod = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');

                $loc = $base_url . '/' . htmlspecialchars($cat) . '/' . htmlspecialchars($slug);
                echo "    <url>\n";
                echo "        <loc>$loc</loc>\n";
                echo "        <lastmod>$lastmod</lastmod>\n";
                echo "        <changefreq>always</changefreq>\n";
                echo "        <priority>0.9</priority>\n";
                echo "    </url>\n";
            }
        } catch (Exception $e) {}
    }
    ?>
</urlset>
