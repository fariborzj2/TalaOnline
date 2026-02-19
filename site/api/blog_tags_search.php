<?php
/**
 * Blog Tags Autocomplete API
 */
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';

// Only search if 3 or more characters
if (mb_strlen($q) < 3) {
    echo json_encode([]);
    exit;
}

if (!$pdo) {
    echo json_encode([]);
    exit;
}

try {
    // Search tags by name
    $stmt = $pdo->prepare("SELECT name FROM blog_tags WHERE name LIKE ? ORDER BY name ASC LIMIT 10");
    $stmt->execute(['%' . $q . '%']);
    $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($tags);
} catch (Exception $e) {
    echo json_encode([]);
}
