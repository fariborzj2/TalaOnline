<?php
require_once __DIR__ . '/includes/db.php';
$pdo->exec("INSERT OR IGNORE INTO users (id, name, email, role_id, is_verified) VALUES (1, 'Admin', 'admin@example.com', 1, 1)");
$pdo->exec("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES ('mail_driver', 'smtp')");
