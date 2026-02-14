<?php
/**
 * Image Proxy & Optimizer
 * Fetches external images, caches them, and converts to WebP
 */

$url = $_GET['url'] ?? '';
if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit('Invalid URL');
}

$cache_dir = __DIR__ . '/../../site/uploads/cache/images/';
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}

$hash = md5($url);
$cache_file = $cache_dir . $hash . '.webp';

// Cache for 1 month
if (file_exists($cache_file) && (time() - filemtime($cache_file) < 2592000)) {
    header('Content-Type: image/webp');
    header('Cache-Control: public, max-age=2592000');
    readfile($cache_file);
    exit;
}

// Fetch image
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
$data = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if (!$data || $info['http_code'] !== 200) {
    http_response_code(404);
    exit('Image not found');
}

$img = imagecreatefromstring($data);
if (!$img) {
    // Fallback: serve original data if conversion fails
    header('Content-Type: ' . ($info['content_type'] ?? 'image/jpeg'));
    echo $data;
    exit;
}

// Convert to WebP
imagepalettetotruecolor($img);
imagealphablending($img, true);
imagesavealpha($img, true);

imagewebp($img, $cache_file, 80);
imagedestroy($img);

header('Content-Type: image/webp');
header('Cache-Control: public, max-age=2592000');
readfile($cache_file);
