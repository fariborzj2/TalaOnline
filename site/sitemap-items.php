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
<?php
if ($pdo) {
    try {
        // Safe query: Select all and filter in PHP to avoid errors if columns don't exist yet
        $stmt = $pdo->query("SELECT * FROM items ORDER BY sort_order ASC");
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                // Filter by is_active if the column exists
                if (isset($row['is_active']) && $row['is_active'] == 0) continue;

                $slug = !empty($row['slug']) ? $row['slug'] : $row['symbol'];
                $cat = $row['category'];

                // Fallback for missing category
                if (empty($cat)) continue;

                $lastmod = (!empty($row['updated_at'])) ? date('Y-m-d', strtotime($row['updated_at'])) : date('Y-m-d');

                $loc = $base_url . '/' . htmlspecialchars($cat) . '/' . htmlspecialchars($slug);

                echo "    <url>\n";
                echo "        <loc>$loc</loc>\n";
                echo "        <lastmod>$lastmod</lastmod>\n";
                echo "        <changefreq>always</changefreq>\n";
                echo "        <priority>0.9</priority>\n";

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
        }
    } catch (Exception $e) {}
}
?>
</urlset>
