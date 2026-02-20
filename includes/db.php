<?php
/**
 * Database Connection & Utility Functions
 */

$config_file = __DIR__ . '/../config.php';

// Initialize $pdo as null to prevent "undefined variable" errors
$pdo = null;

date_default_timezone_set('Asia/Tehran');

if (file_exists($config_file)) {
    require_once $config_file;

    try {
        if (defined('USE_SQLITE') && USE_SQLITE) {
            $pdo = new PDO("sqlite:" . __DIR__ . '/../site/database.sqlite');
        } else {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->exec("SET time_zone = '+03:30'");
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Just continue, we'll handle null $pdo in other places
    }
} else {
    // Redirect to installer if not in installer already
    if (strpos($_SERVER['PHP_SELF'], 'installer.php') === false) {
        // We can't easily redirect from here without knowing the path,
        // but we can at least avoid the fatal error of missing config.php
        // header('Location: /installer.php');
    }
}

/**
 * Get a setting value from the database
 */
function get_setting($key, $default = '') {
    global $pdo;
    if (!isset($pdo) || !$pdo) return $default;

    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Set a setting value in the database
 */
function set_setting($key, $value) {
    global $pdo;
    if (!isset($pdo) || !$pdo) return false;

    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                                   ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = CURRENT_TIMESTAMP");
        } else {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
        }
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Handle File Uploads with WebP conversion
 */
function handle_upload($file, $target_dir = 'uploads/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $info = getimagesize($file['tmp_name']);
    $extension = $info ? image_type_to_extension($info[2], false) : pathinfo($file['name'], PATHINFO_EXTENSION);

    $full_target_dir = __DIR__ . '/../site/' . $target_dir;
    if (!is_dir($full_target_dir)) {
        mkdir($full_target_dir, 0755, true);
    }

    $filename = uniqid();

    // Convert to WebP if GD is available and it's a standard image
    if (function_exists('imagewebp') && in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        $img = null;
        switch ($extension) {
            case 'jpg':
            case 'jpeg': $img = imagecreatefromjpeg($file['tmp_name']); break;
            case 'png':  $img = imagecreatefrompng($file['tmp_name']); break;
            case 'gif':  $img = imagecreatefromgif($file['tmp_name']); break;
        }

        if ($img) {
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
            $filename .= '.webp';
            imagewebp($img, $full_target_dir . $filename, 80);
            imagedestroy($img);
            return $target_dir . $filename;
        }
    }

    // Fallback to original
    $filename .= '.' . $extension;
    $target_path = $full_target_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $target_dir . $filename;
    }

    return null;
}
