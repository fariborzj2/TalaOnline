<?php
/**
 * Database Connection & Utility Functions
 */

// Load Composer's autoloader at the very top
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (defined('DEV_MODE') && DEV_MODE) {
    die("Composer autoloader not found. Please run 'composer install'.");
}

if (!defined('DB_FUNCTIONS_LOADED')) {

/**
 * Global static cache for settings to prevent redundant database queries.
 */
$GLOBALS['__SETTINGS_CACHE__'] = null;

/**
 * Preloads all settings from the database into a static cache.
 */
function preload_settings($pdo_instance = null) {
    global $pdo;
    $_pdo = $pdo_instance ?: $pdo;
    if (!$_pdo) return;

    try {
        $stmt = $_pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $GLOBALS['__SETTINGS_CACHE__'] = $settings ?: [];
    } catch (Exception $e) {
        $GLOBALS['__SETTINGS_CACHE__'] = [];
    }
}

/**
 * Get a setting value from the database (optimized with static cache)
 */
function get_setting($key_or_pdo, $default_or_key = '', $actual_default = '') {
    global $pdo;

    // Handle overload: get_setting($pdo, $key, $default) OR get_setting($key, $default)
    if ($key_or_pdo instanceof PDO) {
        $_pdo = $key_or_pdo;
        $key = $default_or_key;
        $default = $actual_default;
    } else {
        $_pdo = $pdo;
        $key = $key_or_pdo;
        $default = $default_or_key;
    }

    // Use cache if available
    if ($GLOBALS['__SETTINGS_CACHE__'] !== null) {
        return $GLOBALS['__SETTINGS_CACHE__'][$key] ?? $default;
    }

    if (!isset($_pdo) || !$_pdo) return $default;

    // Performance optimization: Preload on first access if not already preloaded
    preload_settings($_pdo);
    return $GLOBALS['__SETTINGS_CACHE__'][$key] ?? $default;
}

/**
 * Set a setting value in the database and updates the cache
 */
function set_setting($key_or_pdo, $value_or_key = null, $actual_value = null) {
    global $pdo;

    // Handle overload: set_setting($pdo, $key, $value) OR set_setting($key, $value)
    if ($key_or_pdo instanceof PDO) {
        $_pdo = $key_or_pdo;
        $key = $value_or_key;
        $value = $actual_value;
    } else {
        $_pdo = $pdo;
        $key = $key_or_pdo;
        $value = $value_or_key;
    }

    if (!isset($_pdo) || !$_pdo) return false;

    try {
        $driver = $_pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $_pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                                   ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = CURRENT_TIMESTAMP");
        } else {
            $stmt = $_pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                                   ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP");
        }
        $success = $stmt->execute([$key, $value]);

        if ($success && $GLOBALS['__SETTINGS_CACHE__'] !== null) {
            $GLOBALS['__SETTINGS_CACHE__'][$key] = $value;
        }
        return $success;
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

define('DB_FUNCTIONS_LOADED', true);

} // end if !defined('DB_FUNCTIONS_LOADED')


$config_file = __DIR__ . '/../config.php';
if (!defined('APP_DB_VERSION')) {
    define('APP_DB_VERSION', 15); // Current Production Version
}

// Initialize $pdo as null to prevent "undefined variable" errors
$pdo = null;

date_default_timezone_set('Asia/Tehran');

if (file_exists($config_file)) {
    require_once $config_file;

    if (defined('DEV_MODE') && DEV_MODE) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
    }

    try {
        if (defined('USE_SQLITE') && USE_SQLITE) {
            $pdo = new PDO("sqlite:" . __DIR__ . '/../site/database.sqlite');
            $pdo->exec("PRAGMA journal_mode = WAL;");
            $pdo->exec("PRAGMA busy_timeout = 5000;");
        } else {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
            $pdo->exec("SET time_zone = '+03:30'");
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if (defined('DEV_MODE') && DEV_MODE) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    // Perform highly optimized version check
    if ($pdo) {
        preload_settings($pdo);
        require_once __DIR__ . '/migrations.php';
        MigrationManager::runIfRequired($pdo, APP_DB_VERSION);
    }
}
