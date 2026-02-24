<?php
/**
 * API for LiteSpeed Cache management
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/core/LSCache.php';
require_once __DIR__ . '/../admin/auth.php';

// Only allow logged in admins
if (!is_logged_in() || !check_permission("settings.edit", false)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'purge_all':
        LSCache::purgeAll();
        echo json_encode(['success' => true, 'message' => 'All cache purged successfully']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}
