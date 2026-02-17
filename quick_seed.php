<?php
$pdo = new PDO("sqlite:" . __DIR__ . '/site/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_categories` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `name` TEXT NOT NULL,
        `slug` TEXT NOT NULL UNIQUE,
        `description` TEXT,
        `sort_order` INTEGER DEFAULT 0,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_posts` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `title` TEXT NOT NULL,
        `slug` TEXT NOT NULL UNIQUE,
        `excerpt` TEXT,
        `content` TEXT,
        `thumbnail` TEXT,
        `category_id` INTEGER,
        `status` TEXT DEFAULT 'draft',
        `views` INTEGER DEFAULT 0,
        `is_featured` INTEGER DEFAULT 0,
        `meta_title` TEXT,
        `meta_description` TEXT,
        `meta_keywords` TEXT,
        `tags` TEXT,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("INSERT OR IGNORE INTO blog_categories (name, slug, description) VALUES ('تحلیل بازار', 'market-analysis', 'تحلیل‌های تخصصی روزانه')");

    $stmt = $pdo->query("SELECT id FROM blog_categories WHERE slug = 'market-analysis'");
    $cat_id = $stmt->fetchColumn();

    for($i=1; $i<=6; $i++) {
        $pdo->prepare("INSERT OR IGNORE INTO blog_posts (title, slug, excerpt, content, category_id, status, is_featured, tags, views, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                "مقاله تست شماره $i",
                "test-post-$i",
                "این یک خلاصه تست برای مقاله شماره $i است.",
                "<h2>بخش اول</h2><p>محتوای متنی تست برای مقاله شماره $i.</p><h2>بخش دوم</h2><p>پایان تست.</p>",
                $cat_id,
                'published',
                $i <= 2 ? 1 : 0,
                'طلا,سکه,تحلیل',
                150 * $i,
                date('Y-m-d H:i:s', strtotime("-$i days"))
            ]);
    }
    echo "Done";
} catch (Exception $e) { echo $e->getMessage(); }
