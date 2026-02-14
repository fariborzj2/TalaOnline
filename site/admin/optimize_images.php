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

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($files as $file) {
        if ($file->isDir()) continue;

        $path = $file->getRealPath();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $webp_path = pathinfo($path, PATHINFO_DIRNAME) . '/' . pathinfo($path, PATHINFO_FILENAME) . '.webp';

            if (file_exists($webp_path)) continue;

            $img = null;
            if ($ext === 'png') $img = imagecreatefrompng($path);
            else $img = imagecreatefromjpeg($path);

            if ($img) {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                imagewebp($img, $webp_path, 80);
                imagedestroy($img);
                $count++;

                // Try to update DB if it points to this asset
                $relative_path = str_replace($base_dir, '', $path);
                $new_relative_path = str_replace($base_dir, '', $webp_path);

                try {
                    require_once __DIR__ . '/../../includes/db.php';
                    if ($pdo) {
                        $stmt = $pdo->prepare("UPDATE items SET logo = ? WHERE logo = ?");
                        $stmt->execute([$new_relative_path, $relative_path]);

                        $stmt = $pdo->prepare("UPDATE platforms SET logo = ? WHERE logo = ?");
                        $stmt->execute([$new_relative_path, $relative_path]);

                        $stmt = $pdo->prepare("UPDATE categories SET logo = ? WHERE logo = ?");
                        $stmt->execute([$new_relative_path, $relative_path]);
                    }
                } catch(Exception $e) {}

                echo "Optimized: " . $relative_path . " -> webp\n";
            }
        }
    }
}

echo "Total images optimized: $count\n";
