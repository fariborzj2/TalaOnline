<?php
/**
 * Image Optimization Script
 * Scans uploads/ and assets/images/ and converts all images to WebP
 * Also updates the database to point to the new WebP files.
 */

require_once __DIR__ . '/../includes/db.php';

if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die('Run from CLI or use ?run=1');
}

$base_dir = __DIR__ . '/../'; // This is the site/ directory
$dirs = [
    $base_dir . 'uploads/',
    $base_dir . 'assets/images/'
];

$count = 0;
$extensions_to_convert = ['jpg', 'jpeg', 'png'];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($iterator as $file) {
        if ($file->isDir()) continue;

        $path = $file->getRealPath();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, $extensions_to_convert)) {
            $webp_path = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.webp';

            if (!file_exists($webp_path)) {
                $img = null;
                if ($ext === 'png') $img = @imagecreatefrompng($path);
                else $img = @imagecreatefromjpeg($path);

                if ($img) {
                    imagepalettetotruecolor($img);
                    imagealphablending($img, true);
                    imagesavealpha($img, true);
                    imagewebp($img, $webp_path, 80);
                    imagedestroy($img);
                    $count++;
                    echo "Optimized: " . str_replace($base_dir, '', $path) . " -> webp\n";
                }
            }
        }
    }
}

// Update Database
if ($pdo) {
    foreach ($extensions_to_convert as $ext) {
        $ext_dot = '.' . $ext;

        // Update items table
        $stmt = $pdo->prepare("UPDATE items SET logo = REPLACE(logo, ?, '.webp') WHERE logo LIKE ?");
        $stmt->execute([$ext_dot, '%' . $ext_dot]);
        echo "Updated items table for $ext_dot\n";

        // Update categories table (if it has logo)
        try {
            $stmt = $pdo->prepare("UPDATE categories SET logo = REPLACE(logo, ?, '.webp') WHERE logo LIKE ?");
            $stmt->execute([$ext_dot, '%' . $ext_dot]);
            echo "Updated categories table for $ext_dot\n";
        } catch (Exception $e) {
            // Category table might not have logo column
        }
    }
}

echo "Total images optimized: $count\n";
