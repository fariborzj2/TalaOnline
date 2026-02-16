<?php
/**
 * Image Optimization Script
 * Scans uploads/ and assets/images/ and converts all images to WebP
 */

if (php_sapi_name() !== 'cli' && !isset($_GET['run'])) {
    die('Run from CLI or use ?run=1');
}

$base_dir = __DIR__ . '/../';
$dirs = [
    $base_dir . 'uploads/',
    $base_dir . 'assets/images/'
];

$count = 0;
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($iterator as $file) {
        if ($file->isDir()) continue;

        $path = $file->getRealPath();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $webp_path = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.webp';

            if (file_exists($webp_path)) continue;

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

echo "Total images optimized: $count\n";
