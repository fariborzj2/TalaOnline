<?php
/**
 * TalaOnline - Professional Backend Installer
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$config_file = 'config.php';

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
            $error = 'نام کاربری و رمز عبور ادمین را وارد کنید.';
        } else {
            $db = $_SESSION['db_config'];
            try {
                $pdo = new PDO("mysql:host={$db['host']};dbname={$db['name']}", $db['user'], $db['pass']);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Create Tables
                $queries = [
                    "CREATE TABLE IF NOT EXISTS `admins` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `username` VARCHAR(50) NOT NULL UNIQUE,
                        `password` VARCHAR(255) NOT NULL,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

                    "CREATE TABLE IF NOT EXISTS `settings` (
                        `setting_key` VARCHAR(50) PRIMARY KEY,
                        `setting_value` TEXT
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
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
                ];

                foreach ($queries as $q) {
                    $pdo->exec($q);
                }

                // Insert Admin
                $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
                $stmt->execute([$admin_user, $hashed_pass]);

                // Insert Initial Settings
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
                $stmt->execute(['api_key', $api_key]);
                $stmt->execute(['site_title', 'طلا آنلاین']);

                // Seed some initial items based on current data
                $initial_items = [
                    ['gold_18k', '18ayar', 'طلای ۱۸ عیار', '18 Karat Gold', 'images/gold.png', 'gold', 1],
                    ['sekkeh_emami', 'sekkeh', 'سکه امامی', 'Emami Coin', 'images/coin.png', 'gold', 2],
                    ['abshodeh', 'abshodeh', 'مثقال طلا', 'Melted Gold', 'images/melted.png', 'gold', 3],
                    ['silver', 'silver', 'نقره', 'Silver', 'images/silver.png', 'silver', 4]
                ];

                $stmt = $pdo->prepare("INSERT IGNORE INTO items (symbol, name, en_name, logo, category, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                // Note: adjusted symbols to match Navasan if known, else placeholders
                // Using Navasan symbols from NAVASAN_API.md
                $seed_data = [
                    ['18ayar', 'طلای ۱۸ عیار', '18 Karat Gold', 'images/gold.png', 'gold', 1],
                    ['sekkeh', 'سکه امامی', 'Emami Coin', 'images/coin.png', 'gold', 2],
                    ['abshodeh', 'آبشده (مثقال)', 'Melted Gold', 'images/melted.png', 'gold', 3],
                    ['silver', 'نقره', 'Silver', 'images/silver.png', 'silver', 4],
                    ['usd_sell', 'دلار تهران', 'US Dollar', 'images/flags/usd.png', 'currency', 5],
                    ['eur', 'یورو', 'Euro', 'images/flags/eur.png', 'currency', 6]
                ];
                foreach ($seed_data as $item) {
                    $stmt->execute($item);
                }

                // Seed Platforms
                $platforms_data = [
                    ['گرمی', 'Gerami', 'images/platforms/gerami.png', '19849500', '19749500', '0.5', 'مناسب خرید', 'https://gerami.ir', 1],
                    ['میلی', 'Milli', 'images/platforms/milli.png', '19849500', '19749500', '0.3', 'مناسب خرید', 'https://milli.gold', 2],
                    ['زرپاد', 'Zarpad', 'images/platforms/zarpad.png', '19849500', '19749500', '0.45', 'مناسب فروش', 'https://zarpad.com', 3],
                    ['وال گلد', 'wallGold', 'images/platforms/wallgold.png', '19849500', '19749500', '0.5', 'مناسب خرید', 'https://wallgold.ir', 4],
                    ['طلاین', 'Tlyn', 'images/platforms/taline.png', '19849500', '19749500', '0.6', 'مناسب فروش', 'https://tlyn.ir', 5]
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
    'Config Writable' => is_writable('.')
];
$all_passed = !in_array(false, $checks, true);

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
                <input type="password" name="db_pass">
            </div>
            <button type="submit" class="btn">بررسی و ادامه</button>
        </form>

    <?php elseif ($step === 3): ?>
        <form method="POST">
            <p style="margin-bottom: 20px;">تنظیمات ادمین و API:</p>
            <div class="form-group">
                <label>نام کاربری مدیر</label>
                <input type="text" name="admin_user" required value="admin">
            </div>
            <div class="form-group">
                <label>رمز عبور مدیر</label>
                <input type="password" name="admin_pass" required>
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

</body>
</html>
