<?php
require_once __DIR__ . '/includes/db.php';

try {
    // Add is_active to items
    $pdo->exec("ALTER TABLE items ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    echo "Added is_active to items\n";
} catch (Exception $e) {
    echo "Items table already has is_active or error: " . $e->getMessage() . "\n";
}

try {
    // Add is_active to platforms
    $pdo->exec("ALTER TABLE platforms ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    echo "Added is_active to platforms\n";
} catch (Exception $e) {
    echo "Platforms table already has is_active or error: " . $e->getMessage() . "\n";
}
?>
