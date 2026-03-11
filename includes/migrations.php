<?php
/**
 * Production-Ready Migration & Schema Management System
 * Engineered for High Performance, Safety, and Robustness.
 */

class MigrationManager {
    private $pdo;
    private $driver;
    private $is_dry_run;
    private $logs = [];
    private $app_version;
    private $lock_fp;

    public function __construct(PDO $pdo, $app_version, $is_dry_run = false) {
        $this->pdo = $pdo;
        $this->driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->app_version = (int)$app_version;
        $this->is_dry_run = $is_dry_run;
    }

    /**
     * Executes migrations if the database version is outdated.
     */
    public static function runIfRequired(PDO $pdo, $app_version) {
        // High-performance check: single indexed lookup
        $db_version = (int)get_setting($pdo, 'db_version', 0);

        if ($db_version < $app_version) {
            $manager = new self($pdo, $app_version);
            return $manager->execute();
        }
        return true;
    }

    /**
     * Orchestrates the migration process with locking and error handling.
     */
    public function execute() {
        if (!$this->acquireLock()) {
            $this->log("Migration already in progress by another request. Skipping.");
            return false;
        }

        try {
            $this->log("Starting migration to version {$this->app_version}...");

            $this->createSchema();
            $this->selfHealColumns();
            $this->ensurePerformanceIndexes();
            $this->migrateData();
            $this->seedDefaults();

            if (!$this->is_dry_run) {
                set_setting($this->pdo, 'db_version', $this->app_version);
            }

            $this->log("Migration completed successfully.");
            return true;
        } catch (Exception $e) {
            $this->log("CRITICAL ERROR during migration: " . $e->getMessage());
            error_log("Migration Failure: " . $e->getMessage());
            return false;
        } finally {
            $this->releaseLock();
        }
    }

    /**
     * Ensures all core tables exist with production-accurate schemas.
     */
    private function createSchema() {
        $queries = [];
        if ($this->driver === 'sqlite') {
            $queries = [
                "CREATE TABLE IF NOT EXISTS `users` (
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
                )",
                "CREATE TABLE IF NOT EXISTS `settings` (
                    `setting_key` VARCHAR(50) PRIMARY KEY,
                    `setting_value` TEXT,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `roles` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `description` TEXT,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `permissions` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `name` VARCHAR(100) NOT NULL,
                    `slug` VARCHAR(100) NOT NULL UNIQUE,
                    `module` VARCHAR(100) NOT NULL,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `role_permissions` (
                    `role_id` INTEGER,
                    `permission_id` INTEGER,
                    PRIMARY KEY (`role_id`, `permission_id`),
                    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
                )",
                "CREATE TABLE IF NOT EXISTS `categories` (
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
                )",
                "CREATE TABLE IF NOT EXISTS `items` (
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
                )",
                "CREATE TABLE IF NOT EXISTS `prices_cache` (
                    `symbol` VARCHAR(50) PRIMARY KEY,
                    `price` VARCHAR(50),
                    `change_val` VARCHAR(50),
                    `change_percent` VARCHAR(20),
                    `high` VARCHAR(50),
                    `low` VARCHAR(50),
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `prices_history` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `symbol` VARCHAR(50) NOT NULL,
                    `price` VARCHAR(50) NOT NULL,
                    `high` VARCHAR(50),
                    `low` VARCHAR(50),
                    `date` DATE NOT NULL,
                    UNIQUE (`symbol`, `date`)
                )",
                "CREATE TABLE IF NOT EXISTS `platforms` (
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
                )",
                "CREATE TABLE IF NOT EXISTS `login_attempts` (
                    `ip` VARCHAR(45) NOT NULL,
                    `attempt_time` DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `comment_reactions` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `user_id` INTEGER,
                    `comment_id` INTEGER,
                    `reaction_type` VARCHAR(20),
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`comment_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE
                )",
                "CREATE TABLE IF NOT EXISTS `comments` (
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
                )",
                "CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `user_id` INTEGER,
                    `sender_id` INTEGER,
                    `type` VARCHAR(50),
                    `target_id` VARCHAR(255),
                    `is_read` TINYINT DEFAULT 0,
                    `status` VARCHAR(20) DEFAULT 'unread',
                    `read_at` DATETIME DEFAULT NULL,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                )",
                "CREATE TABLE IF NOT EXISTS `follows` (
                    `follower_id` INTEGER,
                    `following_id` INTEGER,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`follower_id`, `following_id`),
                    FOREIGN KEY (`follower_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`following_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                )",
                "CREATE TABLE IF NOT EXISTS `email_templates` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `slug` VARCHAR(100) UNIQUE NOT NULL,
                    `subject` VARCHAR(255),
                    `body` TEXT,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `email_queue` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `to_email` VARCHAR(255),
                    `subject` VARCHAR(255),
                    `body_html` TEXT,
                    `sender_name` VARCHAR(255),
                    `sender_email` VARCHAR(255),
                    `status` VARCHAR(20) DEFAULT 'pending',
                    `attempts` INTEGER DEFAULT 0,
                    `last_error` TEXT,
                    `metadata` TEXT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `push_subscriptions` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `user_id` INTEGER,
                    `endpoint` TEXT UNIQUE NOT NULL,
                    `p256dh` TEXT NOT NULL,
                    `auth` TEXT NOT NULL,
                    `content_encoding` VARCHAR(20) DEFAULT 'aes128gcm',
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                )",
                "CREATE TABLE IF NOT EXISTS `notification_settings` (
                    `user_id` INTEGER PRIMARY KEY,
                    `categories` TEXT,
                    `channels` TEXT,
                    `frequency_limit` INTEGER DEFAULT 5,
                    `quiet_hours_start` VARCHAR(5),
                    `quiet_hours_end` VARCHAR(5),
                    `timezone` VARCHAR(50) DEFAULT 'UTC',
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                )",
                "CREATE TABLE IF NOT EXISTS `notification_templates` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `slug` VARCHAR(100) UNIQUE NOT NULL,
                    `title` VARCHAR(255),
                    `body` TEXT,
                    `action_url` VARCHAR(255),
                    `icon` VARCHAR(255),
                    `channels` VARCHAR(100),
                    `priority` VARCHAR(20) DEFAULT 'medium',
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `notification_analytics` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `notification_id` INTEGER,
                    `channel` VARCHAR(20),
                    `event_type` VARCHAR(20),
                    `metadata` TEXT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )",
                "CREATE TABLE IF NOT EXISTS `notification_queue` (
                    `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                    `user_id` INTEGER,
                    `template_slug` VARCHAR(100),
                    `data` TEXT,
                    `channels` VARCHAR(100),
                    `priority` VARCHAR(20),
                    `scheduled_at` DATETIME,
                    `status` VARCHAR(20) DEFAULT 'pending',
                    `attempts` INTEGER DEFAULT 0,
                    `last_error` TEXT,
                    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
            ];
        } else {
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
                    `is_phone_verified` TINYINT DEFAULT 0,
                    `phone_verification_code` VARCHAR(10),
                    `phone_verification_expires_at` TIMESTAMP NULL,
                    `points` INT DEFAULT 0,
                    `level` INT DEFAULT 1,
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
                "CREATE TABLE IF NOT EXISTS `comment_reactions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT,
                    `comment_id` INT,
                    `reaction_type` VARCHAR(20),
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`comment_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `categories` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `items` (
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
                    `is_manual` TINYINT DEFAULT 0,
                    `is_active` TINYINT DEFAULT 1,
                    `views` INT DEFAULT 0,
                    `sort_order` INT DEFAULT 0,
                    `show_in_summary` TINYINT DEFAULT 0,
                    `show_chart` TINYINT DEFAULT 0,
                    `page_title` VARCHAR(255),
                    `h1_title` VARCHAR(255),
                    `meta_description` TEXT,
                    `meta_keywords` TEXT,
                    `related_item_symbol` VARCHAR(100),
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
                "CREATE TABLE IF NOT EXISTS `prices_history` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `symbol` VARCHAR(50) NOT NULL,
                    `price` VARCHAR(50) NOT NULL,
                    `high` VARCHAR(50),
                    `low` VARCHAR(50),
                    `date` DATE NOT NULL,
                    UNIQUE KEY `symbol_date` (`symbol`, `date`)
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
                    `is_active` TINYINT DEFAULT 1,
                    `sort_order` INT DEFAULT 0,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `login_attempts` (
                    `ip` VARCHAR(45) NOT NULL,
                    `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX `idx_login_attempts_ip_time` (`ip`, `attempt_time`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `settings` (
                    `setting_key` VARCHAR(50) PRIMARY KEY,
                    `setting_value` TEXT,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `comments` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `notifications` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT,
                    `sender_id` INT,
                    `type` VARCHAR(50),
                    `target_id` VARCHAR(255),
                    `is_read` TINYINT DEFAULT 0,
                    `status` VARCHAR(20) DEFAULT 'unread',
                    `read_at` TIMESTAMP NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `follows` (
                    `follower_id` INT,
                    `following_id` INT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`follower_id`, `following_id`),
                    FOREIGN KEY (`follower_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                    FOREIGN KEY (`following_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `email_templates` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `slug` VARCHAR(100) UNIQUE NOT NULL,
                    `subject` VARCHAR(255),
                    `body` TEXT,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `email_queue` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `to_email` VARCHAR(255),
                    `subject` VARCHAR(255),
                    `body_html` TEXT,
                    `sender_name` VARCHAR(255),
                    `sender_email` VARCHAR(255),
                    `status` VARCHAR(20) DEFAULT 'pending',
                    `attempts` INT DEFAULT 0,
                    `last_error` TEXT,
                    `metadata` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `push_subscriptions` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT,
                    `endpoint` TEXT NOT NULL,
                    `p256dh` TEXT NOT NULL,
                    `auth` TEXT NOT NULL,
                    `content_encoding` VARCHAR(20) DEFAULT 'aes128gcm',
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE (`endpoint`(255)),
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `notification_settings` (
                    `user_id` INT PRIMARY KEY,
                    `categories` TEXT,
                    `channels` TEXT,
                    `frequency_limit` INT DEFAULT 5,
                    `quiet_hours_start` VARCHAR(5),
                    `quiet_hours_end` VARCHAR(5),
                    `timezone` VARCHAR(50) DEFAULT 'UTC',
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `notification_templates` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `slug` VARCHAR(100) UNIQUE NOT NULL,
                    `title` VARCHAR(255),
                    `body` TEXT,
                    `action_url` VARCHAR(255),
                    `icon` VARCHAR(255),
                    `channels` VARCHAR(100),
                    `priority` VARCHAR(20) DEFAULT 'medium',
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `notification_analytics` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `notification_id` INT,
                    `channel` VARCHAR(20),
                    `event_type` VARCHAR(20),
                    `metadata` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
                "CREATE TABLE IF NOT EXISTS `notification_queue` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `user_id` INT,
                    `template_slug` VARCHAR(100),
                    `data` TEXT,
                    `channels` VARCHAR(100),
                    `priority` VARCHAR(20),
                    `scheduled_at` TIMESTAMP NULL,
                    `status` VARCHAR(20) DEFAULT 'pending',
                    `attempts` INT DEFAULT 0,
                    `last_error` TEXT,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
            ];
        }

        foreach ($queries as $q) {
            $this->exec($q);
        }
    }

    /**
     * Checks for missing columns and adds them with precise production types.
     */
    private function selfHealColumns() {
        $driver = $this->driver;
        $schema = [
            'users' => [
                'role_id' => 'INT DEFAULT 0',
                'username' => 'VARCHAR(50)',
                'phone' => 'VARCHAR(20)',
                'points' => 'INT DEFAULT 0',
                'level' => 'INT DEFAULT 1',
                'is_verified' => 'TINYINT DEFAULT 0',
                'updated_at' => ($driver === 'sqlite' ? "DATETIME DEFAULT '2024-01-01 00:00:00'" : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
            'items' => [
                'slug' => 'VARCHAR(255)',
                'sort_order' => 'INT DEFAULT 0',
                'views' => 'INT DEFAULT 0',
                'page_title' => 'VARCHAR(255)',
                'h1_title' => 'VARCHAR(255)',
                'meta_description' => 'TEXT',
                'related_item_symbol' => 'VARCHAR(100)',
                'updated_at' => ($driver === 'sqlite' ? "DATETIME DEFAULT '2024-01-01 00:00:00'" : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
                'is_active' => 'TINYINT DEFAULT 1',
                'is_manual' => 'TINYINT DEFAULT 0',
                'manual_price' => 'VARCHAR(50)'
            ],
            'comments' => [
                'reply_to_id' => 'INT DEFAULT NULL',
                'reply_to_user_id' => 'INT DEFAULT NULL',
                'likes_count' => 'INT DEFAULT 0',
                'type' => 'VARCHAR(20) DEFAULT "comment"',
                'guest_name' => 'VARCHAR(100)',
                'updated_at' => ($driver === 'sqlite' ? "DATETIME DEFAULT '2024-01-01 00:00:00'" : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ],
            'notifications' => [
                'status' => 'VARCHAR(20) DEFAULT "unread"',
                'read_at' => ($driver === 'sqlite' ? 'DATETIME DEFAULT NULL' : 'TIMESTAMP NULL'),
                'updated_at' => ($driver === 'sqlite' ? "DATETIME DEFAULT '2024-01-01 00:00:00'" : 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
            ]
        ];

        foreach ($schema as $table => $columns) {
            $existing = $this->getTableColumns($table);
            if (empty($existing)) continue;

            foreach ($columns as $col => $def) {
                if (!in_array($col, $existing)) {
                    $this->log("Healing $table: Adding missing column $col...");
                    $this->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");

                    // Specific migration triggers for new columns
                    if ($table === 'users' && $col === 'username') {
                        $this->backfillUsernames();
                    }
                }
            }
        }
    }

    /**
     * Handles complex data transitions, such as legacy role migration.
     */
    private function migrateData() {
        // Migrate notification is_read to status
        if ($this->tableExists('notifications')) {
            $this->log("Syncing notification is_read to status...");
            $this->exec("UPDATE notifications SET status = 'read' WHERE is_read = 1 AND (status = 'unread' OR status IS NULL)");
            $this->exec("UPDATE notifications SET status = 'unread' WHERE is_read = 0 AND (status = 'read' OR status IS NULL)");
        }

        // 1. Legacy Admin string to role_id migration
        $this->log("Checking for legacy admin roles...");
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND role_id = 0");
        if ($stmt->fetchColumn() > 0) {
            $this->log("Migrating legacy admins to RBAC role_id...");
            // We assume role_id 1 is super_admin (seeded later or existing)
            $this->exec("UPDATE users SET role_id = 1 WHERE role = 'admin'");
        }

        // 2. Data cleanup: Fix zero dates
        $tables = ['users', 'items', 'categories', 'comments', 'settings'];
        foreach ($tables as $t) {
            if ($this->tableExists($t)) {
                if ($this->driver === 'mysql') {
                    // In MySQL 8.0 strict mode, '0000-00-00 00:00:00' comparison or updates can fail.
                    // We use a safe update for NULLs. Invalid dates should ideally not exist in newer versions.
                    $this->exec("UPDATE `$t` SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL");
                } else {
                    $this->exec("UPDATE `$t` SET updated_at = CURRENT_TIMESTAMP WHERE updated_at = '0000-00-00 00:00:00' OR updated_at IS NULL");
                }
            }
        }
    }

    /**
     * Seeds essential data like Roles and Permissions if they are missing.
     */
    /**
     * Enforces high-performance composite indexes for the comment system.
     */
    private function ensurePerformanceIndexes() {
        $this->log("Ensuring performance indexes...");
        if ($this->driver === 'sqlite') {
            $this->exec("CREATE INDEX IF NOT EXISTS idx_comments_target_status ON comments(target_id, target_type, status, parent_id, created_at)");
            $this->exec("CREATE INDEX IF NOT EXISTS idx_comments_parent_likes_created ON comments(parent_id, status, likes_count, created_at)");
            $this->exec("CREATE INDEX IF NOT EXISTS idx_reactions_comment_type ON comment_reactions(comment_id, reaction_type)");
        } else {
            // MySQL logic with existence checks
            $indexes = [
                'idx_comments_target_status' => "comments(target_id, target_type, status, parent_id, created_at)",
                'idx_comments_parent_likes_created' => "comments(parent_id, status, likes_count, created_at)",
                'idx_reactions_comment_type' => "comment_reactions(comment_id, reaction_type)"
            ];

            foreach ($indexes as $name => $def) {
                try {
                    $this->exec("ALTER TABLE " . explode('(', $def)[0] . " ADD INDEX $name (" . explode('(', $def)[1]);
                } catch (Exception $e) {}
            }
        }
    }

    private function seedDefaults() {
        $this->log("Ensuring RBAC defaults are seeded...");
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM roles");
        if ($stmt->fetchColumn() == 0) {
            $this->log("Seeding Roles & Permissions...");

            // 1. Create Roles
            $this->exec("INSERT INTO roles (id, name, slug, description) VALUES (1, 'مدیر کل', 'super_admin', 'دسترسی کامل به تمامی بخش‌های سیستم')");
            $this->exec("INSERT INTO roles (id, name, slug, description) VALUES (2, 'نویسنده', 'editor', 'مدیریت مطالب وبلاگ و محتوا')");

            // 2. Define Module Permissions
            $modules = [
                'dashboard' => ['view' => 'مشاهده داشبورد'],
                'assets' => ['view' => 'مشاهده دارایی‌ها', 'create' => 'افزودن دارایی', 'edit' => 'ویرایش دارایی', 'delete' => 'حذف دارایی'],
                'comments' => ['view' => 'مشاهده نظرات', 'edit' => 'ویرایش/تایید نظر', 'delete' => 'حذف نظر'],
                'settings' => ['view' => 'مشاهده تنظیمات', 'edit' => 'ویرایش تنظیمات'],
                'users' => ['view' => 'مشاهده کاربران', 'create' => 'افزودن کاربر', 'edit' => 'ویرایش کاربر', 'delete' => 'حذف کاربر'],
            ];

            $stmt_p = $this->pdo->prepare("INSERT INTO permissions (slug, name, module) VALUES (?, ?, ?)");
            $stmt_rp = $this->pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");

            foreach ($modules as $module => $actions) {
                foreach ($actions as $action => $name) {
                    $slug = "$module.$action";
                    $stmt_p->execute([$slug, $name, $module]);
                    $perm_id = $this->pdo->lastInsertId();

                    // Super Admin (1) gets everything
                    $stmt_rp->execute([1, $perm_id]);

                    // Editor (2) gets partial
                    if (in_array($module, ['dashboard', 'comments'])) {
                        $stmt_rp->execute([2, $perm_id]);
                    }
                }
            }
            $this->log("RBAC seeding complete.");
        }

        // 3. Seed Notification Templates
        $this->log("Ensuring notification templates are seeded...");
        $templates = [
            [
                'slug' => 'asset_volatility',
                'title' => 'نوسان شدید در {name}',
                'body' => 'قیمت {name} ({symbol}) شاهد {type} {change}% بوده است.',
                'action_url' => '{url}',
                'channels' => 'webpush,email,in-app',
                'priority' => 'high'
            ],
            [
                'slug' => 'social_reply',
                'title' => 'پاسخ جدید به نظر شما',
                'body' => 'کاربر {sender_name} به نظر شما پاسخ داده است.',
                'action_url' => '{url}',
                'channels' => 'webpush,email,in-app',
                'priority' => 'medium'
            ],
            [
                'slug' => 'social_mention',
                'title' => 'از شما نام برده شد',
                'body' => 'کاربر {sender_name} در یک نظر از شما نام برده است.',
                'action_url' => '{url}',
                'channels' => 'webpush,email,in-app',
                'priority' => 'medium'
            ],
            [
                'slug' => 'social_milestone',
                'title' => 'تبریک! محبوبیت نظر شما',
                'body' => 'نظر شما به {count} لایک رسید. کاربران دیگر از تحلیل شما لذت می‌برند!',
                'action_url' => '{url}',
                'channels' => 'webpush,in-app',
                'priority' => 'medium'
            ],
            [
                'slug' => 'social_trending',
                'title' => 'بحث داغ: {title}',
                'body' => 'یک گفتگوی پرهیجان در مورد {title} در جریان است. شما هم بپیوندید!',
                'action_url' => '{url}',
                'channels' => 'webpush,in-app',
                'priority' => 'low'
            ],
            [
                'slug' => 'blog_new_post',
                'title' => 'مقاله جدید منتشر شد',
                'body' => 'مطلب جدیدی در دسته {category} با عنوان "{title}" منتشر گردید.',
                'action_url' => '{url}',
                'channels' => 'webpush,email,in-app',
                'priority' => 'medium'
            ],
            [
                'slug' => 'rehook_market_recap',
                'title' => '{name} عزیز، دلتنگتان هستیم',
                'body' => 'از آخرین بازدید شما بازار تغییرات زیادی داشته است. گزارش جدید بازار را مشاهده کنید.',
                'action_url' => '{url}',
                'channels' => 'webpush,email',
                'priority' => 'medium'
            ]
        ];

        foreach ($templates as $tpl) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notification_templates WHERE slug = ?");
            $stmt->execute([$tpl['slug']]);
            if ($stmt->fetchColumn() == 0) {
                $stmt = $this->pdo->prepare("INSERT INTO notification_templates (slug, title, body, action_url, channels, priority) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tpl['slug'], $tpl['title'], $tpl['body'], $tpl['action_url'], $tpl['channels'], $tpl['priority']]);
            }
        }
    }

    /**
     * Backfills missing usernames for existing users and enforces uniqueness.
     */
    private function backfillUsernames() {
        $this->log("Backfilling missing usernames...");
        $stmt = $this->pdo->query("SELECT id, name, email FROM users WHERE username IS NULL OR username = ''");
        $users = $stmt->fetchAll();

        $update = $this->pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        foreach ($users as $u) {
            $base = preg_replace('/[^a-zA-Z0-9]/', '', $u['name'] ?? 'user');
            if (empty($base)) $base = 'user';
            $uname = strtolower($base) . $u['id'];
            $update->execute([$uname, $u['id']]);
        }

        // Add Unique Index
        try {
            if ($this->driver === 'sqlite') {
                $this->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_username ON users(username)");
            } else {
                $this->exec("ALTER TABLE users ADD UNIQUE INDEX idx_users_username (username)");
            }
        } catch (Exception $e) {
            $this->log("Notice: Unique index creation failed (might already exist): " . $e->getMessage());
        }
    }

    /**
     * Helper to execute SQL with dry-run support.
     */
    private function exec($sql) {
        if ($this->is_dry_run) {
            $this->log("[DRY-RUN] Would execute: " . substr($sql, 0, 100) . "...");
            return true;
        }
        return $this->pdo->exec($sql);
    }

    private function log($msg) {
        $formatted = "[" . date('Y-m-d H:i:s') . "] " . $msg;
        $this->logs[] = $formatted;
        if ($this->is_dry_run) echo $formatted . PHP_EOL;
    }

    private function getTableColumns($table) {
        try {
            if ($this->driver === 'sqlite') {
                $stmt = $this->pdo->query("PRAGMA table_info(`$table`)");
                return $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
            } else {
                return $this->pdo->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_COLUMN);
            }
        } catch (Exception $e) { return []; }
    }

    private function tableExists($table) {
        try {
            $this->pdo->query("SELECT 1 FROM `$table` LIMIT 1");
            return true;
        } catch (Exception $e) { return false; }
    }

    private function acquireLock() {
        if ($this->is_dry_run) return true;
        if ($this->driver === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT GET_LOCK(?, 10)");
            $stmt->execute(['migration_lock']);
            return (bool)$stmt->fetchColumn();
        } else {
            // SQLite simple filesystem lock
            $lockFile = __DIR__ . '/migration.lock';
            $fp = fopen($lockFile, 'w');
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                $this->lock_fp = $fp;
                return true;
            }
            return false;
        }
    }

    private function releaseLock() {
        if ($this->is_dry_run) return;
        if ($this->driver === 'mysql') {
            $this->pdo->prepare("SELECT RELEASE_LOCK(?)")->execute(['migration_lock']);
        } elseif (isset($this->lock_fp)) {
            flock($this->lock_fp, LOCK_UN);
            fclose($this->lock_fp);
            @unlink(__DIR__ . '/migration.lock');
        }
    }
}
