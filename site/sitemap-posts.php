<?php
header("Content-Type: application/xml; charset=utf-8");

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<?xml-stylesheet type="text/xsl" href="/sitemap.xsl"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <url>
        <loc><?= get_base_url() ?>/blog</loc>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <?php
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT slug, thumbnail, updated_at FROM blog_posts WHERE status = 'published' ORDER BY updated_at DESC");
            while ($post = $stmt->fetch()) {
                $lastmod = !empty($post['updated_at']) ? date('Y-m-d\TH:i:sP', strtotime($post['updated_at'])) : '2025-01-01T00:00:00+03:30';
                ?>
                <url>
                    <loc><?= get_base_url() ?>/blog/<?= htmlspecialchars($post['slug']) ?></loc>
                    <lastmod><?= $lastmod ?></lastmod>
                    <changefreq>monthly</changefreq>
                    <priority>0.7</priority>
                    <?php if (!empty($post['thumbnail'])): ?>
                    <image:image>
                        <image:loc><?= get_base_url() ?>/<?= ltrim($post['thumbnail'], '/') ?></image:loc>
                    </image:image>
                    <?php endif; ?>
                </url>
                <?php
            }

            // Categories
            $stmt = $pdo->query("SELECT slug, updated_at FROM blog_categories ORDER BY updated_at DESC");
            while ($cat = $stmt->fetch()) {
                $lastmod = !empty($cat['updated_at']) ? date('Y-m-d\TH:i:sP', strtotime($cat['updated_at'])) : '2025-01-01T00:00:00+03:30';
                ?>
                <url>
                    <loc><?= get_base_url() ?>/blog/category/<?= htmlspecialchars($cat['slug']) ?></loc>
                    <lastmod><?= $lastmod ?></lastmod>
                    <changefreq>weekly</changefreq>
                    <priority>0.6</priority>
                </url>
                <?php
            }
        } catch (Exception $e) {}
    }
    ?>
</urlset>
