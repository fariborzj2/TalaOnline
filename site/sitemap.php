<?php
header("Content-type: text/xml; charset=utf-8");
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$base_url = rtrim(get_base_url(), '/');

echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
echo '<?xml-stylesheet type="text/xsl" href="' . $base_url . '/sitemap.xsl"?>' . PHP_EOL;
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- Homepage -->
    <url>
        <loc><?= $base_url ?>/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>always</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- Static Pages -->
    <url>
        <loc><?= $base_url ?>/about-us</loc>
        <lastmod><?= date('Y-m-d', strtotime('-1 day')) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?= $base_url ?>/feedback</loc>
        <lastmod><?= date('Y-m-d', strtotime('-1 day')) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>

    <?php
    if ($pdo) {
        // Categories
        try {
            // Check if updated_at exists, else fallback to current date
            $columns = $pdo->query("DESCRIBE categories")->fetchAll(PDO::FETCH_COLUMN);
            $has_updated_at = in_array('updated_at', $columns);
            $query = "SELECT name, slug" . ($has_updated_at ? ", updated_at" : "") . " FROM categories ORDER BY sort_order ASC";

            $stmt = $pdo->query($query);
            while ($row = $stmt->fetch()) {
                $lastmod = ($has_updated_at && !empty($row['updated_at'])) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');
                $loc = $base_url . '/' . htmlspecialchars($row['slug']);

                echo "    <url>\n";
                echo "        <loc>$loc</loc>\n";
                echo "        <lastmod>$lastmod</lastmod>\n";
                echo "        <changefreq>daily</changefreq>\n";
                echo "        <priority>0.8</priority>\n";
                echo "    </url>\n";
            }
        } catch (Exception $e) {}

        // Items (Currencies/Assets)
        try {
            $columns = $pdo->query("DESCRIBE items")->fetchAll(PDO::FETCH_COLUMN);
            $has_updated_at = in_array('updated_at', $columns);
            $query = "SELECT name, slug, symbol, category, logo" . ($has_updated_at ? ", updated_at" : "") . " FROM items WHERE is_active = 1 ORDER BY sort_order ASC";

            $stmt = $pdo->query($query);
            while ($row = $stmt->fetch()) {
                $slug = !empty($row['slug']) ? $row['slug'] : $row['symbol'];
                $cat = $row['category'];
                $lastmod = ($has_updated_at && !empty($row['updated_at'])) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');

                // Asset URL is /:category/:slug
                $loc = $base_url . '/' . htmlspecialchars($cat) . '/' . htmlspecialchars($slug);

                echo "    <url>\n";
                echo "        <loc>$loc</loc>\n";
                echo "        <lastmod>$lastmod</lastmod>\n";
                echo "        <changefreq>always</changefreq>\n";
                echo "        <priority>0.9</priority>\n";

                // Add Image if exists
                if (!empty($row['logo'])) {
                    $image_loc = $row['logo'];
                    if (!filter_var($image_loc, FILTER_VALIDATE_URL)) {
                        $image_loc = $base_url . '/' . ltrim($image_loc, '/');
                    }
                    echo "        <image:image>\n";
                    echo "            <image:loc>" . htmlspecialchars($image_loc) . "</image:loc>\n";
                    echo "            <image:title>" . htmlspecialchars($row['name']) . "</image:title>\n";
                    echo "        </image:image>\n";
                }

                echo "    </url>\n";
            }
        } catch (Exception $e) {}
    }
    ?>
</urlset>
