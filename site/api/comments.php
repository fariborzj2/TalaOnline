<?php
/**
 * Comments API
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/comments.php';

ensure_session();
$user_id = $_SESSION['user_id'] ?? null;
$action = $_GET['action'] ?? '';

$comments_handler = new Comments($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $target_id = $_GET['target_id'] ?? '';
        $target_type = $_GET['target_type'] ?? '';

        if (empty($target_id) || empty($target_type)) {
            echo json_encode(['success' => false, 'message' => 'پارامترهای نامعتبر']);
            exit;
        }

        $comments = $comments_handler->getComments($target_id, $target_type, $user_id);
        $sentiment = $comments_handler->getSentimentStats($target_id, $target_type);
        echo json_encode(['success' => true, 'comments' => $comments, 'sentiment' => $sentiment]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'خطای امنیتی: توکن نامعتبر']);
        exit;
    }

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'لطفا ابتدا وارد حساب خود شوید.']);
        exit;
    }

    if ($action === 'add') {
        $target_id = $input['target_id'] ?? '';
        $target_type = $input['target_type'] ?? '';
        $content = $input['content'] ?? '';
        $parent_id = $input['parent_id'] ?? null;
        $sentiment = $input['sentiment'] ?? null;

        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => 'متن نظر نمی‌تواند خالی باشد.']);
            exit;
        }

        $id = $comments_handler->addComment($user_id, $target_id, $target_type, $content, $parent_id, $sentiment);
        if ($id) {
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'نظر شما با موفقیت ثبت شد.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در ثبت نظر.']);
        }
        exit;
    }

    if ($action === 'react') {
        $comment_id = $input['comment_id'] ?? 0;
        $reaction_type = $input['reaction_type'] ?? null;
        $success = $comments_handler->react($user_id, $comment_id, $reaction_type);
        echo json_encode(['success' => $success]);
        exit;
    }

    if ($action === 'report') {
        $comment_id = $input['comment_id'] ?? 0;
        $reason = $input['reason'] ?? '';
        $success = $comments_handler->report($user_id, $comment_id, $reason);
        echo json_encode(['success' => $success, 'message' => 'گزارش شما ثبت شد.']);
        exit;
    }

    if ($action === 'edit') {
        $comment_id = $input['comment_id'] ?? 0;
        $content = $input['content'] ?? '';
        $success = $comments_handler->updateComment($user_id, $comment_id, $content);
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'نظر ویرایش شد.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'امکان ویرایش این نظر وجود ندارد (زمان سپری شده است یا نظر متعلق به شما نیست).']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر.']);
