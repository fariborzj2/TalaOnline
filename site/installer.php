<?php
/**
 * TalaOnline - Professional Backend Installer
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$config_file = __DIR__ . '/../config.php';

// If already installed and config exists, redirect or block
if (file_exists($config_file)) {
    // Check if installation is already complete
    require_once $config_file;
    if (defined('INSTALLED') && INSTALLED === true) {
        die('سیستم قبلاً نصب شده است. برای نصب مجدد فایل config.php را حذف کنید.');
    }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 2) {
        // Database Setup
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';

        try {
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");

            // Save to session for next step
            $_SESSION['db_config'] = [
                'host' => $db_host,
                'name' => $db_name,
                'user' => $db_user,
                'pass' => $db_pass
            ];

            header('Location: installer.php?step=3');
            exit;
        } catch (PDOException $e) {
            $error = 'خطا در اتصال به دیتابیس: ' . $e->getMessage();
        }
    } elseif ($step === 3) {
        // Admin & Tables Setup
        $admin_user = $_POST['admin_user'] ?? '';
        $admin_pass = $_POST['admin_pass'] ?? '';
        $api_key = $_POST['api_key'] ?? '';

        if (empty($admin_user) || empty($admin_pass)) {
            $error = 'ایمیل و رمز عبور ادمین را وارد کنید.';
        } else {
            $db = $_SESSION['db_config'];
            try {
                $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']}", $db['user'], $db['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Create Tables
                $queries = [
                    "CREATE TABLE IF NOT EXISTS `users` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `name` VARCHAR(255),
                        `email` VARCHAR(255) UNIQUE,
                        `phone` VARCHAR(20) UNIQUE,
                        `username` VARCHAR(50) UNIQUE,
                        `password` VARCHAR(255),
                        `avatar` VARCHAR(255),
                        `role` VARCHAR(20) DEFAULT 'user',
                        `role_id` INT DEFAULT 0,
                        `is_verified` TINYINT DEFAULT 0,
                        `verification_token` VARCHAR(100),
                        `verification_token_expires_at` TIMESTAMP NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `roles` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `name` VARCHAR(100) NOT NULL,
                        `slug` VARCHAR(100) NOT NULL UNIQUE,
                        `description` TEXT,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `permissions` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `name` VARCHAR(100) NOT NULL,
                        `slug` VARCHAR(100) NOT NULL UNIQUE,
                        `module` VARCHAR(100) NOT NULL,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `role_permissions` (
                        `role_id` INT,
                        `permission_id` INT,
                        PRIMARY KEY (`role_id`, `permission_id`),
                        FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
                        FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `settings` (
                        `setting_key` VARCHAR(50) PRIMARY KEY,
                        `setting_value` TEXT
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `categories` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `slug` VARCHAR(50) NOT NULL UNIQUE,
                        `name` VARCHAR(100) NOT NULL,
                        `sort_order` INT DEFAULT 0
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `items` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `symbol` VARCHAR(50) NOT NULL UNIQUE,
                        `name` VARCHAR(100) NOT NULL,
                        `en_name` VARCHAR(100),
                        `logo` VARCHAR(255),
                        `description` TEXT,
                        `manual_price` VARCHAR(50),
                        `is_manual` TINYINT(1) DEFAULT 0,
                        `is_active` TINYINT(1) DEFAULT 1,
                        `category` VARCHAR(50) DEFAULT 'gold',
                        `sort_order` INT DEFAULT 0
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `prices_cache` (
                        `symbol` VARCHAR(50) PRIMARY KEY,
                        `price` VARCHAR(50),
                        `change_val` VARCHAR(50),
                        `change_percent` VARCHAR(20),
                        `high` VARCHAR(50),
                        `low` VARCHAR(50),
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `platforms` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `name` VARCHAR(100) NOT NULL,
                        `en_name` VARCHAR(100),
                        `logo` VARCHAR(255),
                        `buy_price` VARCHAR(50),
                        `sell_price` VARCHAR(50),
                        `fee` VARCHAR(20),
                        `status` VARCHAR(50),
                        `link` VARCHAR(255),
                        `is_active` TINYINT(1) DEFAULT 1,
                        `sort_order` INT DEFAULT 0
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `prices_history` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `symbol` VARCHAR(50) NOT NULL,
                        `price` VARCHAR(50) NOT NULL,
                        `high` VARCHAR(50),
                        `low` VARCHAR(50),
                        `date` DATE NOT NULL,
                        UNIQUE KEY `symbol_date` (`symbol`, `date`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `login_attempts` (
                        `ip` VARCHAR(45) NOT NULL,
                        `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX `idx_login_attempts_ip_time` (`ip`, `attempt_time`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `email_templates` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `slug` VARCHAR(100) NOT NULL UNIQUE,
                        `subject` VARCHAR(255) NOT NULL,
                        `body` TEXT NOT NULL,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `email_queue` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `to_email` VARCHAR(255) NOT NULL,
                        `subject` VARCHAR(255) NOT NULL,
                        `body_html` TEXT NOT NULL,
                        `sender_name` VARCHAR(255),
                        `sender_email` VARCHAR(255),
                        `status` VARCHAR(20) DEFAULT 'pending',
                        `attempts` INT DEFAULT 0,
                        `last_error` TEXT,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
                ];

                foreach ($queries as $q) {
                    $pdo->exec($q);
                }

                // Seed Roles & Permissions
                $pdo->exec("INSERT IGNORE INTO roles (id, name, slug, description) VALUES (1, 'مدیر کل', 'super_admin', 'دسترسی کامل به تمامی بخش‌های سیستم')");
                $pdo->exec("INSERT IGNORE INTO roles (id, name, slug, description) VALUES (2, 'نویسنده', 'editor', 'مدیریت مطالب وبلاگ و محتوا')");

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

                $stmt_p = $pdo->prepare("INSERT IGNORE INTO permissions (slug, name, module) VALUES (?, ?, ?)");
                $stmt_rp = $pdo->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)");

                foreach ($modules as $module => $actions) {
                    foreach ($actions as $action => $name) {
                        $slug = "$module.$action";
                        $stmt_p->execute([$slug, $name, $module]);
                        $perm_id = $pdo->lastInsertId();
                        if ($perm_id) {
                            $stmt_rp->execute([1, $perm_id]); // Super Admin gets all
                            if (in_array($module, ['dashboard', 'posts', 'blog_categories', 'blog_tags', 'rss'])) {
                                $stmt_rp->execute([2, $perm_id]); // Editor
                            }
                        }
                    }
                }

                // Insert Admin User
                $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
                $admin_username = 'admin';
                $stmt = $pdo->prepare("INSERT INTO users (name, email, username, password, role, role_id, is_verified) VALUES (?, ?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE password = VALUES(password), role_id = VALUES(role_id), is_verified = 1");
                $stmt->execute(['مدیر کل', $admin_user, $admin_username, $hashed_pass, 'admin', 1]);

                // Insert Default Email Templates
                $stmt = $pdo->prepare("INSERT IGNORE INTO email_templates (slug, subject, body) VALUES (?, ?, ?)");
                $stmt->execute([
                    'verification',
                    'تأیید حساب کاربری - {site_title}',
                    'سلام {name} عزیز،<br><br>به {site_title} خوش آمدید. برای فعال‌سازی حساب کاربری خود، لطفاً بر روی لینک زیر کلیک کنید:<br><br><a href="{verification_link}" style="background:#e29b21;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">تأیید حساب کاربری</a><br><br>اگر شما این درخواست را نداده‌اید، این ایمیل را نادیده بگیرید.'
                ]);
                $stmt->execute([
                    'welcome',
                    'خوش آمدید به {site_title}',
                    'سلام {name} عزیز،<br><br>حساب کاربری شما با موفقیت فعال شد. اکنون می‌توانید از تمامی امکانات سایت استفاده کنید.<br><br>با احترام،<br>تیم {site_title}'
                ]);

                // Insert Initial Settings
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute(['api_key', $api_key]);
                $stmt->execute(['site_title', 'طلا آنلاین']);
                $stmt->execute(['api_sync_interval', '10']);

                // Seed Categories
                $stmt = $pdo->prepare("INSERT IGNORE INTO categories (slug, name, sort_order) VALUES (?, ?, ?)");
                $seed_cats = [
                    ['gold', 'طلا', 1],
                    ['coin', 'سکه', 2],
                    ['currency', 'ارز', 3],
                    ['silver', 'نقره', 4]
                ];
                foreach ($seed_cats as $cat) {
                    $stmt->execute($cat);
                }

                // Seed some initial items based on current data
                $initial_items = [
                    ['gold_18k', '18ayar', 'طلای ۱۸ عیار', '18 Karat Gold', 'assets/images/gold.svg', 'gold', 1],
                    ['sekkeh_emami', 'sekkeh', 'سکه امامی', 'Emami Coin', 'assets/images/coin/tamam.png', 'gold', 2],
                    ['abshodeh', 'abshodeh', 'مثقال طلا', 'Melted Gold', 'assets/images/gold.svg', 'gold', 3],
                    ['silver', 'silver', 'نقره', 'Silver', 'assets/images/silver.svg', 'silver', 4]
                ];

                $stmt = $pdo->prepare("INSERT IGNORE INTO items (symbol, name, en_name, logo, category, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                // Note: adjusted symbols to match Navasan if known, else placeholders
                // Using Navasan symbols from NAVASAN_API.md
                $seed_data = [
                    ['18ayar', 'طلای ۱۸ عیار', '18 Karat Gold', 'assets/images/gold.svg', 'gold', 1],
                    ['sekkeh', 'سکه امامی', 'Emami Coin', 'assets/images/coin/tamam.png', 'gold', 2],
                    ['abshodeh', 'آبشده (مثقال)', 'Melted Gold', 'assets/images/gold.svg', 'gold', 3],
                    ['silver', 'نقره', 'Silver', 'assets/images/silver.svg', 'silver', 4],
                    ['usd_sell', 'دلار تهران', 'US Dollar', 'assets/images/road-wayside.svg', 'currency', 5],
                    ['eur', 'یورو', 'Euro', 'assets/images/road-wayside.svg', 'currency', 6]
                ];
                foreach ($seed_data as $item) {
                    $stmt->execute($item);
                }

                // Seed Platforms
                $platforms_data = [
                    ['گرمی', 'Gerami', 'assets/images/platforms/gerami.png', '19849500', '19749500', '0.5', 'مناسب خرید', 'https://gerami.ir', 1],
                    ['میلی', 'Milli', 'assets/images/platforms/milli.png', '19849500', '19749500', '0.3', 'مناسب خرید', 'https://milli.gold', 2],
                    ['زرپاد', 'Zarpad', 'assets/images/platforms/zarpad.png', '19849500', '19749500', '0.45', 'مناسب فروش', 'https://zarpad.com', 3],
                    ['وال گلد', 'wallGold', 'assets/images/platforms/wallgold.png', '19849500', '19749500', '0.5', 'مناسب خرید', 'https://wallgold.ir', 4],
                    ['طلاین', 'Tlyn', 'assets/images/platforms/taline.png', '19849500', '19749500', '0.6', 'مناسب فروش', 'https://tlyn.ir', 5]
                ];
                $stmt = $pdo->prepare("INSERT IGNORE INTO platforms (name, en_name, logo, buy_price, sell_price, fee, status, link, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($platforms_data as $platform) {
                    $stmt->execute($platform);
                }

                // Write Config File
                $config_content = "<?php
define('DB_HOST', '{$db['host']}');
define('DB_NAME', '{$db['name']}');
define('DB_USER', '{$db['user']}');
define('DB_PASS', '{$db['pass']}');
define('INSTALLED', true);
";
                file_put_contents($config_file, $config_content);

                header('Location: installer.php?step=4');
                exit;
            } catch (PDOException $e) {
                $error = 'خطا در راه‌اندازی دیتابیس: ' . $e->getMessage();
            }
        }
    }
}

// System Checks
$checks = [
    'PHP Version (>= 7.4)' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'PDO Extension' => extension_loaded('pdo'),
    'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
    'Intl Extension' => extension_loaded('intl'),
    'Config Writable' => is_writable('.')
];
$all_passed = !in_array(false, $checks, true);

// Check if config folder is writable
$checks['Config Root Writable'] = is_writable(__DIR__ . '/../');

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب سیستم طلا آنلاین</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #e29b21;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #475569;
            --title: #1e293b;
            --border: #e2e8f0;
        }
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .installer-card {
            background: var(--card);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 500px;
            border: 1px solid var(--border);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        h1 {
            font-size: 1.5rem;
            color: var(--title);
            margin: 0;
        }
        .steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .step {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            z-index: 2;
        }
        .step.active {
            background: var(--primary);
        }
        .step.completed {
            background: #10b981;
        }
        .steps::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--border);
            transform: translateY(-50%);
            z-index: 1;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--title);
        }
        input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            box-sizing: border-box;
            font-family: inherit;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(226, 155, 33, 0.1);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
        }
        .alert {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
        .check-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }
        .check-item:last-child {
            border-bottom: none;
        }
        .check-status {
            font-weight: bold;
        }
        .status-pass { color: #16a34a; }
        .status-fail { color: #dc2626; }
    </style>
</head>
<body>

<div class="installer-card">
    <div class="header">
        <div class="logo">✨</div>
        <h1>راه‌اندازی سیستم طلا آنلاین</h1>
    </div>

    <div class="steps">
        <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">۱</div>
        <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">۲</div>
        <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">۳</div>
        <div class="step <?= $step >= 4 ? 'completed' : '' ?>">۴</div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <div class="content">
            <p style="margin-bottom: 20px;">خوش آمدید! ابتدا بررسی‌های سیستمی را انجام می‌دهیم.</p>
            <div style="margin-bottom: 30px;">
                <?php foreach ($checks as $label => $passed): ?>
                    <div class="check-item">
                        <span><?= $label ?></span>
                        <span class="check-status <?= $passed ? 'status-pass' : 'status-fail' ?>">
                            <?= $passed ? '✓' : '✗' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="btn" onclick="window.location.href='installer.php?step=2'" <?= !$all_passed ? 'disabled' : '' ?>>
                <?= $all_passed ? 'شروع نصب' : 'رفع خطاها برای ادامه' ?>
            </button>
        </div>

    <?php elseif ($step === 2): ?>
        <form method="POST">
            <p style="margin-bottom: 20px;">تنظیمات اتصال به دیتابیس MySQL:</p>
            <div class="form-group">
                <label>میزبان (Host)</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            <div class="form-group">
                <label>نام دیتابیس</label>
                <input type="text" name="db_name" required placeholder="tala_db">
            </div>
            <div class="form-group">
                <label>نام کاربری</label>
                <input type="text" name="db_user" required>
            </div>
            <div class="form-group">
                <label>رمز عبور</label>
                <div style="position: relative;">
                    <input type="password" id="db_pass" name="db_pass">
                    <button type="button" onclick="togglePassword('db_pass', this)" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn">بررسی و ادامه</button>
        </form>

    <?php elseif ($step === 3): ?>
        <form method="POST">
            <p style="margin-bottom: 20px;">تنظیمات ادمین و API:</p>
            <div class="form-group">
                <label>ایمیل مدیر (برای ورود)</label>
                <input type="email" name="admin_user" required value="admin@tala.online" dir="ltr">
            </div>
            <div class="form-group">
                <label>رمز عبور مدیر</label>
                <div style="position: relative;">
                    <input type="password" id="admin_pass" name="admin_pass" required>
                    <button type="button" onclick="togglePassword('admin_pass', this)" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #94a3b8;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
            </div>
            <hr style="margin: 20px 0; border: 0; border-top: 1px solid var(--border);">
            <div class="form-group">
                <label>کلید API نوسان (اختیاری)</label>
                <input type="text" name="api_key" placeholder="api_key_...">
            </div>
            <button type="submit" class="btn">نصب نهایی</button>
        </form>

    <?php elseif ($step === 4): ?>
        <div class="content" style="text-align: center;">
            <div style="font-size: 4rem; margin-bottom: 20px;">🎉</div>
            <h2 style="margin-bottom: 10px;">نصب با موفقیت انجام شد!</h2>
            <p style="margin-bottom: 30px;">اکنون می‌توانید به پنل مدیریت وارد شوید.</p>
            <div class="alert alert-success">لطفاً برای امنیت بیشتر، فایل <b>installer.php</b> را از هاست خود حذف کنید.</div>
            <button class="btn" onclick="window.location.href='admin/login.php'">ورود به پنل ادمین</button>
        </div>
    <?php endif; ?>

</div>

<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const svg = btn.querySelector('svg');
        if (input.type === 'password') {
            input.type = 'text';
            svg.innerHTML = '<path d="M9.88 9.88l-3.29-3.29m7.59 7.59l3.29 3.29"/><path d="M2 12s3-7 10-7a9.91 9.91 0 0 1 5 1.34M12 19c-3.12 0-5.84-1.63-7.59-4.12"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.52 13.52 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/><circle cx="12" cy="12" r="3"/>';
        } else {
            input.type = 'password';
            svg.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
        }
    }
</script>
</body>
</html>
