<?php
/**
 * Users API - Search for mentions
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';

ensure_session();

// Only allow authenticated users to search (optional but safer)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'search') {
        $q = $_GET['q'] ?? '';
        if (strlen($q) < 2) {
            echo json_encode(['success' => true, 'users' => []]);
            exit;
        }

        if (!$pdo) {
            echo json_encode(['success' => false, 'message' => 'DB error']);
            exit;
        }

        try {
            // Search by username or name
            $stmt = $pdo->prepare("SELECT id, name, username, avatar FROM users
                                 WHERE (LOWER(username) LIKE LOWER(?) OR LOWER(name) LIKE LOWER(?))
                                 LIMIT 10");
            $searchTerm = "%$q%";
            $stmt->execute([$searchTerm, $searchTerm]);
            $users = $stmt->fetchAll();

            // Format avatar URLs
            $baseUrl = get_base_url();
            foreach ($users as &$user) {
                if ($user['avatar']) {
                    if (!preg_match('/^https?:\/\//', $user['avatar'])) {
                        $user['avatar'] = $baseUrl . '/' . ltrim($user['avatar'], '/');
                    }
                } else {
                    $user['avatar'] = $baseUrl . '/assets/images/default-avatar.png';
                }
            }

            echo json_encode(['success' => true, 'users' => $users]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
