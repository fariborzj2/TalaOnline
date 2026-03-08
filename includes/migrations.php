<?php
/**
 * Database Migrations & Schema Management
 */

function run_migrations($pdo) {
    if (!$pdo) return;

    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    try {
        // 1. Core Tables
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
                `role_id` INTEGER DEFAULT 0,
                `is_verified` TINYINT DEFAULT 0,
                `verification_token` VARCHAR(100),
                `verification_token_expires_at` DATETIME,
                `is_phone_verified` TINYINT DEFAULT 0,
                `phone_verification_code` VARCHAR(10),
                `phone_verification_expires_at` DATETIME,
                `points` INTEGER DEFAULT 0,
                `level` INTEGER DEFAULT 1,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL,
                `en_name` VARCHAR(100),
                `icon` VARCHAR(50),
                `logo` VARCHAR(255),
                `views` INTEGER DEFAULT 0,
                `sort_order` INTEGER DEFAULT 0,
                `page_title` VARCHAR(255),
                `h1_title` VARCHAR(255),
                `meta_description` TEXT,
                `meta_keywords` TEXT,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `items` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `symbol` VARCHAR(50) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL,
                `en_name` VARCHAR(100),
                `category` VARCHAR(100),
                `slug` VARCHAR(100),
                `logo` VARCHAR(255),
                `description` TEXT,
                `long_description` TEXT,
                `manual_price` VARCHAR(50),
                `is_manual` TINYINT DEFAULT 0,
                `is_active` TINYINT DEFAULT 1,
                `views` INTEGER DEFAULT 0,
                `sort_order` INTEGER DEFAULT 0,
                `show_in_summary` TINYINT DEFAULT 0,
                `show_chart` TINYINT DEFAULT 0,
                `page_title` VARCHAR(255),
                `h1_title` VARCHAR(255),
                `meta_description` TEXT,
                `meta_keywords` TEXT,
                `related_item_symbol` VARCHAR(100),
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `setting_key` VARCHAR(50) PRIMARY KEY,
                `setting_value` TEXT,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `prices_cache` (
                `symbol` VARCHAR(50) PRIMARY KEY,
                `price` REAL,
                `change_val` REAL,
                `change_percent` REAL,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `prices_history` (
                `symbol` VARCHAR(50),
                `price` REAL,
                `high` REAL,
                `low` REAL,
                `date` DATE,
                PRIMARY KEY (`symbol`, `date`)
            )");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `platforms` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `en_name` VARCHAR(100),
                `logo` VARCHAR(255),
                `buy_price` VARCHAR(50),
                `sell_price` VARCHAR(50),
                `fee` VARCHAR(20),
                `status` VARCHAR(50),
                `link` VARCHAR(255),
                `is_active` TINYINT DEFAULT 1,
                `sort_order` INTEGER DEFAULT 0,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } else {
            // MySQL
            $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
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
                `is_phone_verified` TINYINT DEFAULT 0,
                `phone_verification_code` VARCHAR(10),
                `phone_verification_expires_at` TIMESTAMP NULL,
                `points` INT DEFAULT 0,
                `level` INT DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL,
                `en_name` VARCHAR(100),
                `icon` VARCHAR(50),
                `logo` VARCHAR(255),
                `views` INT DEFAULT 0,
                `sort_order` INT DEFAULT 0,
                `page_title` VARCHAR(255),
                `h1_title` VARCHAR(255),
                `meta_description` TEXT,
                `meta_keywords` TEXT,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `items` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `symbol` VARCHAR(50) NOT NULL UNIQUE,
                `name` VARCHAR(100) NOT NULL,
                `en_name` VARCHAR(100),
                `category` VARCHAR(100),
                `slug` VARCHAR(100),
                `logo` VARCHAR(255),
                `description` TEXT,
                `long_description` TEXT,
                `manual_price` VARCHAR(50),
                `is_manual` TINYINT(1) DEFAULT 0,
                `is_active` TINYINT(1) DEFAULT 1,
                `views` INT DEFAULT 0,
                `sort_order` INT DEFAULT 0,
                `show_in_summary` TINYINT(1) DEFAULT 0,
                `show_chart` TINYINT(1) DEFAULT 0,
                `page_title` VARCHAR(255),
                `h1_title` VARCHAR(255),
                `meta_description` TEXT,
                `meta_keywords` TEXT,
                `related_item_symbol` VARCHAR(100),
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `setting_key` VARCHAR(50) PRIMARY KEY,
                `setting_value` TEXT,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `prices_cache` (
                `symbol` VARCHAR(50) PRIMARY KEY,
                `price` DECIMAL(15,2),
                `change_val` DECIMAL(15,2),
                `change_percent` DECIMAL(10,2),
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `prices_history` (
                `symbol` VARCHAR(50),
                `price` DECIMAL(15,2),
                `high` DECIMAL(15,2),
                `low` DECIMAL(15,2),
                `date` DATE,
                PRIMARY KEY (`symbol`, `date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `platforms` (
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
                `sort_order` INT DEFAULT 0,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        // 2. Blog Tables
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_categories` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT,
                `meta_title` VARCHAR(255),
                `meta_description` VARCHAR(255),
                `meta_keywords` VARCHAR(255),
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
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_tags` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_post_tags` (
                `post_id` INTEGER,
                `tag_id` INTEGER,
                PRIMARY KEY (`post_id`, `tag_id`),
                FOREIGN KEY (`post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`tag_id`) REFERENCES `blog_tags`(`id`) ON DELETE CASCADE
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT,
                `meta_title` VARCHAR(255),
                `meta_description` VARCHAR(255),
                `meta_keywords` VARCHAR(255),
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
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_tags` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL UNIQUE,
                `slug` VARCHAR(100) NOT NULL UNIQUE,
                `description` TEXT,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_post_tags` (
                `post_id` INT,
                `tag_id` INT,
                PRIMARY KEY (`post_id`, `tag_id`),
                FOREIGN KEY (`post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`tag_id`) REFERENCES `blog_tags`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        // 3. System Tables
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
            try { $pdo->exec("CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time ON login_attempts(ip, attempt_time)"); } catch (Exception $e) {}

            $pdo->exec("CREATE TABLE IF NOT EXISTS `verification_attempts` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `type` VARCHAR(20),
                `identifier_type` VARCHAR(20),
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
            try { $pdo->exec("CREATE INDEX IF NOT EXISTS idx_follows_following ON follows(following_id)"); } catch (Exception $e) {}

            $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER,
                `sender_id` INTEGER,
                `type` VARCHAR(50),
                `target_id` VARCHAR(255),
                `is_read` TINYINT DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            )");
            try { $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id, is_read)"); } catch (Exception $e) {}

            $pdo->exec("CREATE TABLE IF NOT EXISTS `feedbacks` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` VARCHAR(255),
                `email` VARCHAR(255),
                `subject` VARCHAR(255),
                `message` TEXT,
                `is_read` TINYINT DEFAULT 0,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
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
            try { $pdo->exec("CREATE INDEX idx_login_attempts_ip_time ON login_attempts(ip, attempt_time)"); } catch (Exception $e) {}

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
            try { $pdo->exec("CREATE INDEX idx_follows_following ON follows(following_id)"); } catch (Exception $e) {}

            $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT,
                `sender_id` INT,
                `type` VARCHAR(50),
                `target_id` VARCHAR(255),
                `is_read` TINYINT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            try { $pdo->exec("CREATE INDEX idx_notifications_user ON notifications(user_id, is_read)"); } catch (Exception $e) {}

            $pdo->exec("CREATE TABLE IF NOT EXISTS `feedbacks` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255),
                `email` VARCHAR(255),
                `subject` VARCHAR(255),
                `message` TEXT,
                `is_read` TINYINT DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        // 4. Email System
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

        // 5. Comment System
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `comments` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `user_id` INTEGER,
                `target_id` VARCHAR(255),
                `target_type` VARCHAR(50),
                `content` TEXT,
                `parent_id` INTEGER DEFAULT NULL,
                `reply_to_id` INTEGER DEFAULT NULL,
                `reply_to_user_id` INTEGER DEFAULT NULL,
                `guest_name` VARCHAR(100) DEFAULT NULL,
                `guest_email` VARCHAR(100) DEFAULT NULL,
                `type` VARCHAR(20) DEFAULT 'comment',
                `image_url` VARCHAR(255) DEFAULT NULL,
                `likes_count` INTEGER DEFAULT 0,
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

            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comments_target ON comments(target_id, target_type, status)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comments_parent ON comments(parent_id)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comments_target_parent ON comments(target_id, parent_id)");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_comments_reply_to ON comments(reply_to_user_id)");
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
                `reply_to_id` INT DEFAULT NULL,
                `reply_to_user_id` INT DEFAULT NULL,
                `guest_name` VARCHAR(100) DEFAULT NULL,
                `guest_email` VARCHAR(100) DEFAULT NULL,
                `type` VARCHAR(20) DEFAULT 'comment',
                `image_url` VARCHAR(255) DEFAULT NULL,
                `likes_count` INT DEFAULT 0,
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

            try { $pdo->exec("CREATE INDEX idx_comments_target ON comments(target_id, target_type, status)"); } catch (Exception $e) {}
            try { $pdo->exec("CREATE INDEX idx_comments_parent ON comments(parent_id)"); } catch (Exception $e) {}
            try { $pdo->exec("CREATE INDEX idx_comments_target_parent ON comments(target_id, parent_id)"); } catch (Exception $e) {}
            try { $pdo->exec("CREATE INDEX idx_comments_reply_to_user ON comments(reply_to_user_id)"); } catch (Exception $e) {}
            try { $pdo->exec("CREATE INDEX idx_comments_reply_to_id ON comments(reply_to_id)"); } catch (Exception $e) {}
            try { $pdo->exec("CREATE INDEX idx_comment_reactions_lookup ON comment_reactions(comment_id, reaction_type)"); } catch (Exception $e) {}
            try { $pdo->exec("CREATE INDEX idx_comment_reactions_user ON comment_reactions(user_id)"); } catch (Exception $e) {}
        }

        // 6. Market Sentiment
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `market_sentiment` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `currency_id` VARCHAR(100) NOT NULL,
                `user_id` INTEGER DEFAULT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `vote` VARCHAR(20) NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_sentiment_lookup ON market_sentiment(currency_id, created_at)");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `market_sentiment` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `currency_id` VARCHAR(100) NOT NULL,
                `user_id` INT DEFAULT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `vote` VARCHAR(20) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            try { $pdo->exec("CREATE INDEX idx_sentiment_lookup ON market_sentiment(currency_id, created_at)"); } catch (Exception $e) {}
        }

        // 7. Column & Data Fixes (Self-Healing)
        $tables_to_fix = [
            'users' => [
                'role_id' => 'INTEGER DEFAULT 0',
                'is_verified' => 'TINYINT DEFAULT 0',
                'verification_token' => 'VARCHAR(100)',
                'verification_token_expires_at' => ($driver === 'sqlite' ? 'DATETIME' : 'TIMESTAMP NULL'),
                'is_phone_verified' => 'TINYINT DEFAULT 0',
                'phone_verification_code' => 'VARCHAR(10)',
                'phone_verification_expires_at' => ($driver === 'sqlite' ? 'DATETIME' : 'TIMESTAMP NULL'),
                'points' => 'INTEGER DEFAULT 0',
                'level' => 'INTEGER DEFAULT 1',
                'username' => 'VARCHAR(50)',
                'phone' => 'VARCHAR(20)',
                'avatar' => 'VARCHAR(255)',
                'updated_at' => ($driver === 'sqlite' ? 'DATETIME DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
            'items' => [
                'sort_order' => 'INTEGER DEFAULT 0',
                'slug' => 'VARCHAR(255)',
                'logo' => 'VARCHAR(255)',
                'page_title' => 'VARCHAR(255)',
                'h1_title' => 'VARCHAR(255)',
                'meta_description' => 'TEXT',
                'meta_keywords' => 'TEXT',
                'description' => 'TEXT',
                'long_description' => 'TEXT',
                'related_item_symbol' => 'VARCHAR(100)',
                'views' => 'INTEGER DEFAULT 0',
                'updated_at' => ($driver === 'sqlite' ? 'DATETIME DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
            'categories' => [
                'sort_order' => 'INTEGER DEFAULT 0',
                'page_title' => 'VARCHAR(255)',
                'h1_title' => 'VARCHAR(255)',
                'meta_description' => 'TEXT',
                'meta_keywords' => 'TEXT',
                'logo' => 'VARCHAR(255)',
                'views' => 'INTEGER DEFAULT 0',
                'updated_at' => ($driver === 'sqlite' ? 'DATETIME DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
            'blog_posts' => [
                'tags' => 'TEXT',
                'views' => 'INTEGER DEFAULT 0',
                'updated_at' => ($driver === 'sqlite' ? 'DATETIME DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
            'blog_categories' => [
                'meta_title' => 'VARCHAR(255)',
                'meta_description' => 'VARCHAR(255)',
                'meta_keywords' => 'VARCHAR(255)',
                'updated_at' => ($driver === 'sqlite' ? 'DATETIME DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
            'comments' => [
                'reply_to_id' => 'INTEGER DEFAULT NULL',
                'reply_to_user_id' => 'INTEGER DEFAULT NULL',
                'guest_name' => 'VARCHAR(100) DEFAULT NULL',
                'guest_email' => 'VARCHAR(100) DEFAULT NULL',
                'likes_count' => 'INTEGER DEFAULT 0',
                'type' => 'VARCHAR(20) DEFAULT "comment"',
                'image_url' => 'VARCHAR(255) DEFAULT NULL',
                'updated_at' => ($driver === 'sqlite' ? 'DATETIME DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
            'settings' => [
                'updated_at' => ($driver === 'sqlite' ? 'DATETIME DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
            'email_queue' => [
                'metadata' => 'TEXT',
                'updated_at' => ($driver === 'sqlite' ? 'DATETIME DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ]
        ];

        foreach ($tables_to_fix as $table => $required_cols) {
            $cols = [];
            try {
                if ($driver === 'sqlite') {
                    $stmt = $pdo->query("PRAGMA table_info($table)");
                    while ($row = $stmt->fetch()) { $cols[] = $row['name']; }
                } else {
                    $cols = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_COLUMN);
                }
            } catch (Exception $e) { continue; }

            if (empty($cols)) continue;

            foreach ($required_cols as $col => $def) {
                if (!in_array($col, $cols)) {
                    try {
                        $pdo->exec("ALTER TABLE $table ADD COLUMN $col $def");

                        // Special handling for new UNIQUE columns or data migrations
                        if ($table === 'users' && $col === 'username') {
                            try { $pdo->exec("CREATE UNIQUE INDEX idx_users_username ON users(username)"); } catch (Exception $e) {}

                            // Backfill usernames
                            $stmt = $pdo->query("SELECT id, name FROM users WHERE username IS NULL");
                            while ($u = $stmt->fetch()) {
                                $base = preg_replace('/[^a-zA-Z0-9]/', '', $u['name'] ?? 'user');
                                if (empty($base)) $base = 'user';
                                $uname = strtolower($base) . $u['id'];
                                $pdo->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$uname, $u['id']]);
                            }
                        }
                    } catch (Exception $e) {}
                }
            }

            // Data Self-Healing: Fix any existing "zero dates"
            try {
                $pdo->exec("UPDATE $table SET updated_at = CURRENT_TIMESTAMP WHERE updated_at = '0000-00-00 00:00:00' OR updated_at IS NULL");
                if ($table === 'blog_posts') {
                    $pdo->exec("UPDATE blog_posts SET created_at = CURRENT_TIMESTAMP WHERE created_at = '0000-00-00 00:00:00' OR created_at IS NULL");
                }
            } catch (Exception $e) {}
        }

        // 8. Seeding & RBAC

        // Default Email Templates
        $template_count = $pdo->query("SELECT COUNT(*) FROM email_templates")->fetchColumn();
        if ($template_count == 0) {
            $stmt = $pdo->prepare("INSERT INTO email_templates (slug, subject, body) VALUES (?, ?, ?)");
            $stmt->execute(['verification', 'تأیید حساب کاربری - {site_title}', 'سلام {name} عزیز،<br><br>به {site_title} خوش آمدید. برای فعال‌سازی حساب کاربری خود و بهره‌مندی از امکانات کامل سایت، لطفاً بر روی دکمه زیر کلیک کنید:<br><br><div style="text-align:center;margin:30px 0;"><a href="{verification_link}" style="display:inline-block;background-color:#e29b21;color:white;padding:12px 30px;text-decoration:none;border-radius:10px;font-weight:bold;box-shadow:0 4px 10px rgba(226, 155, 33, 0.2);">تأیید حساب کاربری</a></div><br>اگر شما این درخواست را نداده‌اید، می‌توانید این ایمیل را نادیده بگیرید.']);
            $stmt->execute(['welcome', 'خوش آمدید به {site_title}', 'سلام {name} عزیز،<br><br>حساب کاربری شما با موفقیت فعال شد. اکنون می‌توانید از تمامی امکانات {site_title} از جمله مشاهده قیمت‌های لحظه‌ای و مقایسه پلتفرم‌های معاملاتی استفاده کنید.<br><br>با احترام،<br>تیم پشتیبانی {site_title}']);
        }

        // Roles & Permissions (RBAC)
        $role_count = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
        if ($role_count == 0) {
            $pdo->exec("INSERT INTO roles (name, slug, description) VALUES ('مدیر کل', 'super_admin', 'دسترسی کامل به تمامی بخش‌های سیستم')");
            $super_admin_id = $pdo->lastInsertId();
            $pdo->exec("INSERT INTO roles (name, slug, description) VALUES ('نویسنده', 'editor', 'مدیریت مطالب وبلاگ و محتوا')");
            $editor_id = $pdo->lastInsertId();

            $modules = [
                'dashboard' => ['view' => 'مشاهده داشبورد'],
                'assets' => ['view' => 'مشاهده دارایی‌ها', 'create' => 'افزودن دارایی', 'edit' => 'ویرایش دارایی', 'delete' => 'حذف دارایی'],
                'categories' => ['view' => 'مشاهده دسته‌بندی‌ها', 'create' => 'افزودن دسته‌بندی', 'edit' => 'ویرایش دسته‌بندی', 'delete' => 'حذف دسته‌بندی'],
                'platforms' => ['view' => 'مشاهده پلتفرم‌ها', 'create' => 'افزودن پلتفرم', 'edit' => 'ویرایش پلتفرم', 'delete' => 'حذف پلتفرم'],
                'posts' => ['view' => 'مشاهده نوشته‌ها', 'create' => 'افزودن نوشته', 'edit' => 'ویرایش نوشته', 'delete' => 'حذف نوشته'],
                'blog_categories' => ['view' => 'مشاهده دسته‌بندی‌های وبلاگ', 'create' => 'افزودن دسته‌بندی وبلاگ', 'edit' => 'ویرایش دسته‌بندی وبلاگ', 'delete' => 'حذف دسته‌بندی وبلاگ'],
                'blog_tags' => ['view' => 'مشاهده برچسب‌ها', 'create' => 'افزودن برچسب', 'edit' => 'ویرایش برچسب', 'delete' => 'حذف برچسب'],
                'rss' => ['view' => 'مشاهده فیدهای RSS', 'create' => 'افزودن فید RSS', 'edit' => 'ویرایش فید RSS', 'delete' => 'حذف فید RSS'],
                'comments' => ['view' => 'مشاهده نظرات', 'edit' => 'ویرایش/تایید نظر', 'delete' => 'حذف نظر'],
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
                    $perm_stmt->execute([$super_admin_id, $perm_id]);
                    if (in_array($module, ['dashboard', 'posts', 'blog_categories', 'blog_tags', 'rss', 'comments'])) {
                        $perm_stmt->execute([$editor_id, $perm_id]);
                    }
                }
            }

            // Migrate existing legacy admins
            $pdo->exec("UPDATE users SET role_id = $super_admin_id WHERE role = 'admin'");
        }

    } catch (Exception $e) {
        error_log("Migration failed: " . $e->getMessage());
    }
}
