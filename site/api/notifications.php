<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/notifications.php';
require_once __DIR__ . '/../../includes/comments.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'لطفا وارد حساب خود شوید.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$notif_manager = new Notifications($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $page = (int)($_GET['page'] ?? 1);
        $type = $_GET['type'] ?? null;
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $notifications = $notif_manager->getNotifications($user_id, $limit, $offset, $type);
        $unread_count = $notif_manager->countUnread($user_id);

        // Enhance notifications with target info
        $comments_manager = new Comments($pdo);

        $comment_ids = [];
        foreach ($notifications as $n) {
            if ($n['type'] === 'mention' || $n['type'] === 'reply') {
                $comment_ids[] = $n['target_id'];
            }
        }

        $targetInfoMap = $comments_manager->bulkGetTargetInfoByCommentIds($comment_ids);

        foreach ($notifications as &$n) {
            $n['created_at_fa'] = jalali_date($n['created_at']);
            if ($n['type'] === 'mention' || $n['type'] === 'reply') {
                $n['target_info'] = $targetInfoMap[$n['target_id']] ?? null;
                if ($n['target_info'] && isset($n['target_info']['url'])) {
                    $n['target_info']['url'] .= '#comment-' . $n['target_id'];
                }
            } elseif ($n['type'] === 'follow') {
                $n['target_info'] = ['url' => "/profile/{$n['sender_id']}/{$n['sender_username']}"];
            }
        }

        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'خطای امنیتی: توکن CSRF معتبر نیست.']);
        exit;
    }

    if ($action === 'mark_read') {
        $input_data = file_get_contents('php://input');
        $data = json_decode($input_data, true);
        $id = $data['id'] ?? null;

        if ($id) {
            $success = $notif_manager->markAsRead($user_id, $id);
        } else {
            $success = $notif_manager->markAllAsRead($user_id);
        }

        echo json_encode(['success' => $success]);
        exit;
    }

    if ($action === 'archive') {
        $input_data = file_get_contents('php://input');
        $data = json_decode($input_data, true);
        $id = $data['id'] ?? null;

        if ($id) {
            $success = $notif_manager->archive($user_id, $id);
        } else {
            $success = $notif_manager->archiveAll($user_id);
        }

        echo json_encode(['success' => $success]);
        exit;
    }
}
