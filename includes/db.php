<?php
/**
 * Database Connection & Utility Functions
 */

$config_file = __DIR__ . '/../config.php';

if (file_exists($config_file)) {
    require_once $config_file;

    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if (defined('INSTALLED') && INSTALLED === true) {
            die("خطا در اتصال به دیتابیس: " . $e->getMessage());
        }
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
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        return false;
    }
}
