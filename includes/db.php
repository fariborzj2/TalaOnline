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

    // Global Migration / Self-Healing
    if ($pdo) {
        try {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `name` VARCHAR(255),
                    `email` VARCHAR(255) UNIQUE,
                    `phone` VARCHAR(20) UNIQUE,
                    `username` VARCHAR(50) UNIQUE,
                    `password` VARCHAR(255),
                    `avatar` VARCHAR(255),
                    `role` VARCHAR(20) DEFAULT 'user',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(255),
                    `email` VARCHAR(255) UNIQUE,
                    `phone` VARCHAR(20) UNIQUE,
                    `username` VARCHAR(50) UNIQUE,
                    `password` VARCHAR(255),
                    `avatar` VARCHAR(255),
                    `role` VARCHAR(20) DEFAULT 'user',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }

            // Ensure missing columns exist
            $cols = [];
            if ($driver === 'sqlite') {
                $stmt = $pdo->query("PRAGMA table_info(users)");
                while ($row = $stmt->fetch()) { $cols[] = $row['name']; }
            } else {
                $cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
            }
            if (!in_array('phone', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20)");
            }
            if (!in_array('avatar', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255)");
            }
            if (!in_array('username', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(50)");
                try {
                    $pdo->exec("CREATE UNIQUE INDEX idx_users_username ON users(username)");
                } catch (Exception $e) {}

                // Backfill usernames for existing users
                $stmt = $pdo->query("SELECT id, name, email FROM users WHERE username IS NULL");
                $users_to_update = $stmt->fetchAll();
                foreach ($users_to_update as $u) {
                     // Simple generation if helper not loaded yet
                     $base = preg_replace('/[^a-zA-Z0-9]/', '', $u['name'] ?? 'user');
                     if (empty($base)) $base = 'user';
                     $uname = strtolower($base) . $u['id'];
                     $pdo->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$uname, $u['id']]);
                }
            }

            // Role & Permission Management Tables
            if ($driver === 'sqlite') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `roles` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `description` TEXT,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `permissions` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `module` VARCHAR(100) NOT NULL,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `role_permissions` (
                    `role_id` INTEGER,
                    `permission_id` INTEGER,
                    PRIMARY KEY (`role_id`, `permission_id`),
                    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
                    `ip` VARCHAR(45) NOT NULL,
                    `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                try {
                    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time ON login_attempts(ip, attempt_time)");
                } catch (Exception $e) {}
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `roles` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `description` TEXT,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `permissions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `module` VARCHAR(100) NOT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `role_permissions` (
                    `role_id` INT,
                    `permission_id` INT,
                    PRIMARY KEY (`role_id`, `permission_id`),
                    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
                    `ip` VARCHAR(45) NOT NULL,
                    `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                try {
                    $pdo->exec("CREATE INDEX idx_login_attempts_ip_time ON login_attempts(ip, attempt_time)");
                } catch (Exception $e) {}
            }

            if (!in_array('role_id', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN role_id INTEGER DEFAULT 0");
                // Note: role_id 0 will be 'user' (no admin access)
            }

            // Seeding Default Roles & Permissions
            $role_count = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
            if ($role_count == 0) {
                // Create Super Admin Role
                $pdo->exec("INSERT INTO roles (name, slug, description) VALUES ('مدیر کل', 'super_admin', 'دسترسی کامل به تمامی بخش‌های سیستم')");
                $super_admin_id = $pdo->lastInsertId();

                // Create Editor Role
                $pdo->exec("INSERT INTO roles (name, slug, description) VALUES ('نویسنده', 'editor', 'مدیریت مطالب وبلاگ و محتوا')");
                $editor_id = $pdo->lastInsertId();

                // Define Permissions
                $modules = [
                    'dashboard' => ['view' => 'مشاهده داشبورد'],
                    'assets' => ['view' => 'مشاهده دارایی‌ها', 'create' => 'افزودن دارایی', 'edit' => 'ویرایش دارایی', 'delete' => 'حذف دارایی'],
                    'categories' => ['view' => 'مشاهده دسته‌بندی‌ها', 'create' => 'افزودن دسته‌بندی', 'edit' => 'ویرایش دسته‌بندی', 'delete' => 'حذف دسته‌بندی'],
                    'platforms' => ['view' => 'مشاهده پلتفرم‌ها', 'create' => 'افزودن پلتفرم', 'edit' => 'ویرایش پلتفرم', 'delete' => 'حذف پلتفرم'],
                    'posts' => ['view' => 'مشاهده نوشته‌ها', 'create' => 'افزودن نوشته', 'edit' => 'ویرایش نوشته', 'delete' => 'حذف نوشته'],
                    'blog_categories' => ['view' => 'مشاهده دسته‌بندی‌های وبلاگ', 'create' => 'افزودن دسته‌بندی وبلاگ', 'edit' => 'ویرایش دسته‌بندی وبلاگ', 'delete' => 'حذف دسته‌بندی وبلاگ'],
                    'blog_tags' => ['view' => 'مشاهده برچسب‌ها', 'create' => 'افزودن برچسب', 'edit' => 'ویرایش برچسب', 'delete' => 'حذف برچسب'],
                    'rss' => ['view' => 'مشاهده فیدهای RSS', 'create' => 'افزودن فید RSS', 'edit' => 'ویرایش فید RSS', 'delete' => 'حذف فید RSS'],
                    'feedbacks' => ['view' => 'مشاهده بازخوردها', 'delete' => 'حذف بازخورد'],
                    'settings' => ['view' => 'مشاهده تنظیمات', 'edit' => 'ویرایش تنظیمات'],
                    'users' => ['view' => 'مشاهده کاربران', 'create' => 'افزودن کاربر', 'edit' => 'ویرایش کاربر', 'delete' => 'حذف کاربر'],
                    'roles' => ['view' => 'مشاهده نقش‌ها', 'create' => 'افزودن نقش', 'edit' => 'ویرایش نقش', 'delete' => 'حذف نقش'],
                ];

                $stmt = $pdo->prepare("INSERT INTO permissions (slug, name, module) VALUES (?, ?, ?)");
                $perm_stmt = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");

                foreach ($modules as $module => $actions) {
                    foreach ($actions as $action => $name) {
                        $slug = "$module.$action";
                        $stmt->execute([$slug, $name, $module]);
                        $perm_id = $pdo->lastInsertId();

                        // Super Admin gets everything
                        $perm_stmt->execute([$super_admin_id, $perm_id]);

                        // Editor gets content modules
                        if (in_array($module, ['dashboard', 'posts', 'blog_categories', 'blog_tags', 'rss'])) {
                            $perm_stmt->execute([$editor_id, $perm_id]);
                        }
                    }
                }

                // Migrate existing 'admin' users to super_admin role
                $pdo->exec("UPDATE users SET role_id = $super_admin_id WHERE role = 'admin'");
            }

        } catch (Exception $e) {}
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
