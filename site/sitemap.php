<?php
header("Content-type: text/xml");
require_once __DIR__ . '/../includes/db.php';

$base_url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]";

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?= $base_url ?>/</loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>always</changefreq>
        <priority>1.0</priority>
    </url>
</urlset>
