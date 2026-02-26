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
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $per_page = 20;

        if (empty($target_id) || empty($target_type)) {
            echo json_encode(['success' => false, 'message' => 'پارامترهای نامعتبر']);
            exit;
        }

        $data = $comments_handler->getComments($target_id, $target_type, $user_id, $page, $per_page);
        $sentiment = $comments_handler->getSentimentStats($target_id, $target_type);
        echo json_encode(array_merge(['success' => true, 'sentiment' => $sentiment], $data));
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
        // Rate Limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ip_limit = check_rate_limit('comment', 'ip', $ip);
        if ($ip_limit !== true) {
            echo json_encode(['success' => false, 'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً ' . fa_num(ceil($ip_limit / 60)) . ' دقیقه صبر کنید.']);
            exit;
        }

        $user_limit = check_rate_limit('comment', 'user', $user_id);
        if ($user_limit !== true) {
            echo json_encode(['success' => false, 'message' => 'شما به تازگی نظر ثبت کرده‌اید. لطفاً کمی صبر کنید.']);
            exit;
        }

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
            record_rate_limit_attempt('comment', 'ip', $ip);
            record_rate_limit_attempt('comment', 'user', $user_id);

            // Fetch the full comment data to return for AJAX insertion
            $new_comment = $comments_handler->getComment($id, $user_id);
            $sentiment_stats = $comments_handler->getSentimentStats($target_id, $target_type);
            $total_count = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE target_id = ? AND target_type = ? AND status = 'approved'");
            $total_count->execute([$target_id, $target_type]);
            $count = $total_count->fetchColumn();

            echo json_encode([
                'success' => true,
                'id' => $id,
                'comment' => $new_comment,
                'sentiment' => $sentiment_stats,
                'total_count' => $count,
                'message' => 'نظر شما با موفقیت ثبت شد.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در ثبت نظر.']);
        }
        exit;
    }

    if ($action === 'react') {
        $comment_id = $input['comment_id'] ?? 0;
        $reaction_type = $input['reaction_type'] ?? null;
        $success = $comments_handler->react($user_id, $comment_id, $reaction_type);
        if ($success) {
            $stmt = $pdo->prepare("SELECT
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = ? AND reaction_type = 'like') as likes,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = ? AND reaction_type = 'dislike') as dislikes,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = ? AND reaction_type = 'heart') as hearts,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = ? AND reaction_type = 'fire') as fires");
            $stmt->execute([$comment_id, $comment_id, $comment_id, $comment_id]);
            $counts = $stmt->fetch();
            echo json_encode(['success' => true, 'counts' => $counts, 'user_reaction' => $reaction_type]);
        } else {
            echo json_encode(['success' => false]);
        }
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
            $content_html = $comments_handler->parseMentions($content);
            echo json_encode(['success' => true, 'message' => 'نظر ویرایش شد.', 'content_html' => $content_html, 'content' => $content]);
        } else {
            echo json_encode(['success' => false, 'message' => 'امکان ویرایش این نظر وجود ندارد (زمان سپری شده است یا نظر متعلق به شما نیست).']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر.']);
