<?php
require_once __DIR__ . '/../auth.php';
$current_page = basename($_SERVER['PHP_SELF']);

// Global Schema Self-Healing for updated_at
// This ensures sitemaps and other freshness tracking works correctly
if (isset($pdo) && $pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    // Check and create core and blog tables if they don't exist
    try {
        if ($driver === 'sqlite') {
            // Core tables for SQLite
            $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL,
                `en_name` VARCHAR(100),
                `icon` VARCHAR(50),
                `sort_order` INTEGER DEFAULT 0,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `items` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `symbol` VARCHAR(50) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL,
                `category` VARCHAR(100),
                `slug` VARCHAR(100),
                `is_active` INTEGER DEFAULT 1,
                `sort_order` INTEGER DEFAULT 0,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                `setting_value` TEXT,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_categories` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT,
                `sort_order` INTEGER DEFAULT 0,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_posts` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `excerpt` TEXT,
                `content` TEXT,
                `thumbnail` VARCHAR(255),
                `category_id` INTEGER,
                `status` VARCHAR(20) DEFAULT 'draft',
                `views` INTEGER DEFAULT 0,
                `is_featured` INTEGER DEFAULT 0,
                `meta_title` VARCHAR(255),
                `meta_description` VARCHAR(255),
                `meta_keywords` VARCHAR(255),
                `tags` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`category_id`) REFERENCES `blog_categories`(`id`) ON DELETE SET NULL
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_post_categories` (
                `post_id` INTEGER,
                `category_id` INTEGER,
                PRIMARY KEY (`post_id`, `category_id`),
                FOREIGN KEY (`post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`category_id`) REFERENCES `blog_categories`(`id`) ON DELETE CASCADE
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_post_faqs` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `post_id` INTEGER NOT NULL,
                `question` TEXT NOT NULL,
                `answer` TEXT NOT NULL,
                `sort_order` INTEGER DEFAULT 0,
                FOREIGN KEY (`post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT,
                `sort_order` INT DEFAULT 0,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_posts` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL UNIQUE,
                `excerpt` TEXT,
                `content` LONGTEXT,
                `thumbnail` VARCHAR(255),
                `category_id` INT,
                `status` ENUM('draft', 'published') DEFAULT 'draft',
                `views` INT DEFAULT 0,
                `is_featured` TINYINT(1) DEFAULT 0,
                `meta_title` VARCHAR(255),
                `meta_description` VARCHAR(255),
                `meta_keywords` VARCHAR(255),
                `tags` TEXT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (`category_id`) REFERENCES `blog_categories`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_post_categories` (
                `post_id` INT,
                `category_id` INT,
                PRIMARY KEY (`post_id`, `category_id`),
                FOREIGN KEY (`post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`category_id`) REFERENCES `blog_categories`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_post_faqs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `post_id` INT NOT NULL,
                `question` TEXT NOT NULL,
                `answer` TEXT NOT NULL,
                `sort_order` INT DEFAULT 0,
                FOREIGN KEY (`post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
    } catch (Exception $e) {}

    $tables_to_check = ['items', 'categories', 'settings', 'blog_categories', 'blog_posts'];
    foreach ($tables_to_check as $table) {
        try {
            $cols = [];
            if ($driver === 'sqlite') {
                $stmt = $pdo->query("PRAGMA table_info($table)");
                while ($row = $stmt->fetch()) {
                    $cols[] = $row['name'];
                }
            } else {
                $cols = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
            }

            if (!empty($cols) && !in_array('updated_at', $cols)) {
                $column_def = ($driver === 'sqlite')
                    ? "DATETIME DEFAULT CURRENT_TIMESTAMP"
                    : "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
                $pdo->exec("ALTER TABLE $table ADD COLUMN updated_at $column_def");
            }

            if ($table === 'blog_posts' && !empty($cols) && !in_array('tags', $cols)) {
                $pdo->exec("ALTER TABLE blog_posts ADD COLUMN tags TEXT");
            }

            // Data Self-Healing: Fix any existing "zero dates" that cause display issues
            $pdo->exec("UPDATE $table SET updated_at = CURRENT_TIMESTAMP WHERE updated_at = '0000-00-00 00:00:00' OR updated_at IS NULL");
            if ($table === 'blog_posts') {
                $pdo->exec("UPDATE blog_posts SET created_at = CURRENT_TIMESTAMP WHERE created_at = '0000-00-00 00:00:00' OR created_at IS NULL");
            }
        } catch (Exception $e) {}
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - ' : '' ?>مدیریت طلا آنلاین</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&amp;display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
        }

        body {
            font-family: 'Vazirmatn', sans-serif;
            @apply bg-[#f8fafc] text-slate-900 antialiased text-[13px];
        }

        @layer base {
            h1, h2, h3, h4, h5, h6 { @apply font-black; }
            label { @apply block pr-1 font-black text-slate-700 text-xs mb-1.5; }
            input[type="text"], input[type="password"], input[type="email"], input[type="number"], select, textarea {
                @apply w-full border border-slate-200 bg-white rounded-lg px-4 py-2 outline-none transition-all duration-200 font-bold focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10;
            }
        }

        @layer components {
            .glass-card {
                @apply bg-white rounded-xl border border-slate-200;
            }
            .admin-table {
                @apply w-full border-separate border-spacing-0;
            }
            .admin-table th {
                @apply bg-slate-50/50 border-y border-slate-100 px-6 py-4 text-right text-[10px] font-black text-slate-400 uppercase  first:rounded-r-lg last:rounded-l-lg first:border-r last:border-l whitespace-nowrap;
            }
            .admin-table th:first-child, .admin-table th:last-child {
                border-radius: 0 !important
            }
            .admin-table td {
                @apply px-6 py-4 border-b border-slate-50 text-[12px] font-bold text-slate-700 transition-colors whitespace-nowrap;
            }
            .admin-table tr:last-child td {
                @apply border-b-0;
            }
            .admin-table tr:hover td {
                @apply bg-slate-50/30;
            }
            .btn-v3 {
                @apply px-4 py-2 md:px-5 md:py-2 rounded-lg font-black text-xs transition-all flex items-center justify-center gap-2 active:scale-95;
            }
            .btn-v3-primary {
                @apply bg-indigo-600 text-white hover:bg-indigo-700;
            }
            .btn-v3-outline {
                @apply bg-white text-slate-600 border border-slate-200 hover:bg-slate-50;
            }
            .sidebar-link {
                @apply flex items-center gap-4 px-4 py-3 rounded-lg font-bold text-slate-400 transition-all duration-300 hover:bg-slate-50 hover:text-indigo-600;
            }
            .sidebar-link.active {
                @apply bg-indigo-50 text-indigo-700;
            }
            .sidebar-link i {
                @apply w-5 h-5;
            }

            /* Button Loading State */
            .btn-loading {
                @apply relative !text-transparent pointer-events-none;
            }
            .btn-loading::after {
                content: "";
                @apply absolute inset-0 m-auto w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin;
            }
            .btn-v3-outline.btn-loading::after {
                @apply border-slate-200 border-t-indigo-600;
            }
        }

        @keyframes modalUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-modal-up {
            animation: modalUp 0.2s ease-out forwards;
        }

        /* Modal scroll fix */
        .modal-container {
            @apply max-h-[90vh] overflow-y-auto;
        }

        /* Input with icon fix */
        .input-icon-wrapper {
            @apply relative;
        }
        .input-icon-wrapper input {
            @apply !pr-12;
        }
        .input-icon-wrapper .icon {
            @apply absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none flex items-center justify-center;
        }

        /* LTR Inputs */
        .ltr-input {
            direction: ltr !important;
            text-align: left !important;
        }

        /* Custom File Input */
        .file-input-wrapper {
            @apply relative overflow-hidden;
        }
        .file-input-custom {
            @apply w-full border border-slate-200 bg-white rounded-lg px-4 py-2 outline-none transition-all duration-200 font-bold focus:border-indigo-500 flex items-center justify-between cursor-pointer hover:bg-slate-50;
        }
        .file-input-custom input[type="file"] {
            @apply absolute inset-0 opacity-0 cursor-pointer;
        }

        /* Toggle Switch */
        .toggle-dot {
            @apply w-9 h-5 bg-slate-200 rounded-full transition-all duration-300 relative;
        }
        .toggle-dot::after {
            content: '';
            @apply absolute top-[2px] right-[2px] bg-white rounded-full h-4 w-4 transition-all duration-300  border border-slate-200;
        }
        .peer:checked ~ .toggle-dot {
            @apply bg-indigo-600;
        }
        .peer:checked ~ .toggle-dot.toggle-emerald {
            @apply bg-emerald-600;
        }
        .peer:checked ~ .toggle-dot::after {
            @apply -translate-x-[16px];
        }

        /* Custom Dialogs */
        .custom-dialog-overlay {
            @apply fixed inset-0 z-[2000] bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-4 opacity-0 pointer-events-none transition-opacity duration-300;
        }
        .custom-dialog-overlay.active {
            @apply opacity-100 pointer-events-auto;
        }
        .custom-dialog-box {
            @apply bg-white w-full max-w-[340px] rounded-2xl p-8  transform scale-95 opacity-0 transition-all duration-300 text-center;
        }
        .custom-dialog-overlay.active .custom-dialog-box {
            @apply scale-100 opacity-100;
        }

        /* Tag/Category Pill Styles */
        .tag-item, .cat-item {
            @apply transition-all duration-200 hover:brightness-95;
        }
        .remove-btn {
            @apply flex items-center justify-center w-4 h-4 rounded-full hover:bg-black/10 transition-colors;
        }
    </style>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- Persian Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
</head>
<body class="min-h-screen flex flex-col lg:flex-row overflow-x-hidden">
    <!-- Mobile Header -->
    <header class="lg:hidden bg-white border-b border-slate-100 px-4 py-3 flex items-center justify-between sticky top-0 z-50">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-600 rounded-xl flex items-center justify-center">
                <i data-lucide="shield-check" class="text-white w-6 h-6"></i>
            </div>
            <span class="font-black text-lg text-slate-900">مدیریت</span>
        </div>
        <button onclick="toggleSidebar()" class="p-2 text-slate-500">
            <i data-lucide="menu" class="w-7 h-7"></i>
        </button>
    </header>

    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 p-4 lg:p-8 max-w-7xl mx-auto w-full">
        <!-- Dashboard Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8 md:mb-10">
            <div>
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                    <?= $page_title ?? 'مدیریت' ?>
                </h1>
                <?php if (isset($page_subtitle)): ?>
                    <p class="text-slate-400 mt-1 font-bold text-xs md:text-base"><?= $page_subtitle ?></p>
                <?php endif; ?>
            </div>

            <div class="flex items-center gap-2 md:gap-4">
                <?php if (isset($header_action)) echo $header_action; ?>
            </div>
        </div>
