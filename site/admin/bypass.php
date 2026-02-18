<?php
session_start();
require_once __DIR__ . '/../../includes/db.php';
$stmt = $pdo->query("SELECT id, username FROM admins LIMIT 1");
$admin = $stmt->fetch();
if ($admin) {
    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    echo "Logged in as " . $admin['username'];
} else {
    echo "No admin found";
}
