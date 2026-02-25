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

            $queue_cols = [];
            if ($driver === 'sqlite') {
                $stmt = $pdo->query("PRAGMA table_info(email_queue)");
                while ($row = $stmt->fetch()) { $queue_cols[] = $row['name']; }
            } else {
                try {
                    $queue_cols = $pdo->query("DESCRIBE email_queue")->fetchAll(PDO::FETCH_COLUMN);
                } catch (Exception $e) {}
            }

            if (!empty($queue_cols) && !in_array('metadata', $queue_cols)) {
                $pdo->exec("ALTER TABLE email_queue ADD COLUMN metadata TEXT");
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
                $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                    `setting_key` VARCHAR(50) PRIMARY KEY,
                    `setting_value` TEXT,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
                    `ip` VARCHAR(45) NOT NULL,
                    `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                try {
                    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time ON login_attempts(ip, attempt_time)");
                } catch (Exception $e) {}

                $pdo->exec("CREATE TABLE IF NOT EXISTS `verification_attempts` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `type` VARCHAR(20), -- 'email' or 'sms'
                    `identifier_type` VARCHAR(20), -- 'ip', 'email', 'phone'
                    `identifier_value` VARCHAR(255),
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `verification_locks` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `identifier_type` VARCHAR(20),
                    `identifier_value` VARCHAR(255),
                    `unlock_at` DATETIME,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `follows` (
                    `follower_id` INTEGER,
                    `following_id` INTEGER,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`follower_id`, `following_id`),
                    FOREIGN KEY (`follower_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`following_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                )");
                try {
                    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_follows_following ON follows(following_id)");
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
                $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                    `setting_key` VARCHAR(50) PRIMARY KEY,
                    `setting_value` TEXT,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `login_attempts` (
                    `ip` VARCHAR(45) NOT NULL,
                    `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                try {
                    $pdo->exec("CREATE INDEX idx_login_attempts_ip_time ON login_attempts(ip, attempt_time)");
                } catch (Exception $e) {}

                $pdo->exec("CREATE TABLE IF NOT EXISTS `verification_attempts` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `type` VARCHAR(20),
                    `identifier_type` VARCHAR(20),
                    `identifier_value` VARCHAR(255),
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `verification_locks` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `identifier_type` VARCHAR(20),
                    `identifier_value` VARCHAR(255),
                    `unlock_at` TIMESTAMP NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `follows` (
                    `follower_id` INT,
                    `following_id` INT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`follower_id`, `following_id`),
                    FOREIGN KEY (`follower_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`following_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                try {
                    $pdo->exec("CREATE INDEX idx_follows_following ON follows(following_id)");
                } catch (Exception $e) {}
            }

            if (!in_array('role_id', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN role_id INTEGER DEFAULT 0");
                // Note: role_id 0 will be 'user' (no admin access)
            }

            if (!in_array('is_verified', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT DEFAULT 0");
            }
            if (!in_array('verification_token', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(100)");
            }
            if (!in_array('verification_token_expires_at', $cols)) {
                if ($driver === 'sqlite') {
                    $pdo->exec("ALTER TABLE users ADD COLUMN verification_token_expires_at DATETIME");
                } else {
                    $pdo->exec("ALTER TABLE users ADD COLUMN verification_token_expires_at TIMESTAMP NULL");
                }
            }

            if (!in_array('is_phone_verified', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN is_phone_verified TINYINT DEFAULT 0");
            }
            if (!in_array('phone_verification_code', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN phone_verification_code VARCHAR(10)");
            }
            if (!in_array('phone_verification_expires_at', $cols)) {
                if ($driver === 'sqlite') {
                    $pdo->exec("ALTER TABLE users ADD COLUMN phone_verification_expires_at DATETIME");
                } else {
                    $pdo->exec("ALTER TABLE users ADD COLUMN phone_verification_expires_at TIMESTAMP NULL");
                }
            }

            // Email Templates Table
            if ($driver === 'sqlite') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `email_templates` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `subject` VARCHAR(255) NOT NULL,
                    `body` TEXT NOT NULL,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `email_queue` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `to_email` VARCHAR(255) NOT NULL,
                    `subject` VARCHAR(255) NOT NULL,
                    `body_html` TEXT NOT NULL,
                    `sender_name` VARCHAR(255),
                    `sender_email` VARCHAR(255),
                    `status` VARCHAR(20) DEFAULT 'pending',
                    `attempts` INTEGER DEFAULT 0,
                    `last_error` TEXT,
                    `metadata` TEXT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `email_templates` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `subject` VARCHAR(255) NOT NULL,
                    `body` TEXT NOT NULL,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `email_queue` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `to_email` VARCHAR(255) NOT NULL,
                    `subject` VARCHAR(255) NOT NULL,
                    `body_html` TEXT NOT NULL,
                    `sender_name` VARCHAR(255),
                    `sender_email` VARCHAR(255),
                    `status` VARCHAR(20) DEFAULT 'pending',
                    `attempts` INT DEFAULT 0,
                    `last_error` TEXT,
                    `metadata` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            }

            // Comment System Tables
            if ($driver === 'sqlite') {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `comments` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `user_id` INTEGER,
                    `target_id` VARCHAR(255),
                    `target_type` VARCHAR(50),
                    `content` TEXT,
                    `parent_id` INTEGER DEFAULT NULL,
                    `sentiment` VARCHAR(20) DEFAULT NULL,
                    `status` VARCHAR(20) DEFAULT 'approved',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `comment_reactions` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `comment_id` INTEGER,
                    `user_id` INTEGER,
                    `reaction_type` VARCHAR(20),
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `comment_reports` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `comment_id` INTEGER,
                    `user_id` INTEGER,
                    `reason` TEXT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )");

                // Comment System Indexes
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comments_target ON comments(target_id, target_type, status)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comments_parent ON comments(parent_id)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comment_reactions_lookup ON comment_reactions(comment_id, reaction_type)");
                $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comment_reactions_user ON comment_reactions(user_id)");
            } else {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `comments` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT,
                    `target_id` VARCHAR(255),
                    `target_type` VARCHAR(50),
                    `content` TEXT,
                    `parent_id` INT DEFAULT NULL,
                    `sentiment` VARCHAR(20) DEFAULT NULL,
                    `status` VARCHAR(20) DEFAULT 'approved',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `comment_reactions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `comment_id` INT,
                    `user_id` INT,
                    `reaction_type` VARCHAR(20),
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                $pdo->exec("CREATE TABLE IF NOT EXISTS `comment_reports` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `comment_id` INT,
                    `user_id` INT,
                    `reason` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                // Comment System Indexes
                $pdo->exec("CREATE INDEX idx_comments_target ON comments(target_id, target_type, status)");
                $pdo->exec("CREATE INDEX idx_comments_parent ON comments(parent_id)");
                $pdo->exec("CREATE INDEX idx_comment_reactions_lookup ON comment_reactions(comment_id, reaction_type)");
                $pdo->exec("CREATE INDEX idx_comment_reactions_user ON comment_reactions(user_id)");
            }

            if (!in_array('points', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN points INTEGER DEFAULT 0");
            }
            if (!in_array('level', $cols)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN level INTEGER DEFAULT 1");
            }

            // Seeding Default Email Templates
            $template_count = $pdo->query("SELECT COUNT(*) FROM email_templates")->fetchColumn();
            if ($template_count == 0) {
                $stmt = $pdo->prepare("INSERT INTO email_templates (slug, subject, body) VALUES (?, ?, ?)");
                $stmt->execute([
                    'verification',
                    'تأیید حساب کاربری - {site_title}',
                    'سلام {name} عزیز،<br><br>به {site_title} خوش آمدید. برای فعال‌سازی حساب کاربری خود و بهره‌مندی از امکانات کامل سایت، لطفاً بر روی دکمه زیر کلیک کنید:<br><br><div style="text-align:center;margin:30px 0;"><a href="{verification_link}" style="display:inline-block;background-color:#e29b21;color:white;padding:12px 30px;text-decoration:none;border-radius:10px;font-weight:bold;box-shadow:0 4px 10px rgba(226, 155, 33, 0.2);">تأیید حساب کاربری</a></div><br>اگر شما این درخواست را نداده‌اید، می‌توانید این ایمیل را نادیده بگیرید.'
                ]);
                $stmt->execute([
                    'welcome',
                    'خوش آمدید به {site_title}',
                    'سلام {name} عزیز،<br><br>حساب کاربری شما با موفقیت فعال شد. اکنون می‌توانید از تمامی امکانات {site_title} از جمله مشاهده قیمت‌های لحظه‌ای و مقایسه پلتفرم‌های معاملاتی استفاده کنید.<br><br>با احترام،<br>تیم پشتیبانی {site_title}'
                ]);
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
