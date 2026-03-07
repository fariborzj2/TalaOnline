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
        $filter_type = $_GET['filter_type'] ?? 'all';
        $sort = $_GET['sort'] ?? 'newest';
        $per_page = (int)get_setting('comments_per_page', '20');
        if ($target_type === 'user_profile') $per_page = 10;

        if (empty($target_id) || empty($target_type)) {
            echo json_encode(['success' => false, 'message' => 'پارامترهای نامعتبر']);
            exit;
        }

        $data = $comments_handler->getComments($target_id, $target_type, $user_id, $page, $per_page, $filter_type, $sort);
        echo json_encode(array_merge(['success' => true], $data));
        exit;
    }

    if ($action === 'replies') {
        $parent_id = $_GET['parent_id'] ?? 0;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

        $replies = $comments_handler->getReplies($parent_id, $offset, $limit, $user_id);
        echo json_encode(['success' => true, 'replies' => $replies]);
        exit;
    }

    if ($action === 'thread') {
        $comment_id = $_GET['comment_id'] ?? 0;
        if (empty($comment_id)) {
            echo json_encode(['success' => false, 'message' => 'شناسه نظر الزامی است']);
            exit;
        }

        $thread = $comments_handler->getThread($comment_id, $user_id);
        if ($thread) {
            echo json_encode(['success' => true, 'thread' => $thread]);
        } else {
            echo json_encode(['success' => false, 'message' => 'گفتگو پیدا نشد']);
        }
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

    if ($action === 'add') {
        // Support both JSON and multipart/form-data
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data')) {
            $data = $_POST;
            $mentions = isset($data['mentions']) ? json_decode($data['mentions'], true) : [];
        } else {
            $data = $input;
            $mentions = $data['mentions'] ?? [];
        }

        $hp = $data['hp'] ?? '';
        if ($hp !== '') {
            echo json_encode(['success' => false, 'message' => 'بوت شناسایی شد.']);
            exit;
        }

        $target_id = $data['target_id'] ?? '';
        $target_type = $data['target_type'] ?? '';
        $type = ($target_type === 'post') ? 'comment' : ($data['type'] ?? 'comment');

        if (!$user_id) {
            if ($type === 'analysis') {
                echo json_encode(['success' => false, 'message' => 'برای ثبت تحلیل باید ابتدا وارد حساب خود شوید.', 'require_auth' => true]);
                exit;
            }

            $guest_enabled = get_setting('comments_guest_comment_' . $target_type, '0') === '1';
            if (!$guest_enabled) {
                echo json_encode(['success' => false, 'message' => 'لطفا ابتدا وارد حساب خود شوید.']);
                exit;
            }
        }
        // Rate Limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ip_limit = check_rate_limit('comment', 'ip', $ip);
        if ($ip_limit !== true) {
            echo json_encode(['success' => false, 'message' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً ' . fa_num(ceil($ip_limit / 60)) . ' دقیقه صبر کنید.']);
            exit;
        }

        if ($user_id) {
            $user_limit = check_rate_limit('comment', 'user', $user_id);
            if ($user_limit !== true) {
                echo json_encode(['success' => false, 'message' => 'شما به تازگی نظر ثبت کرده‌اید. لطفاً کمی صبر کنید.']);
                exit;
            }
        }
        $content = $data['content'] ?? '';
        $parent_id = !empty($data['parent_id']) ? $data['parent_id'] : null;
        $reply_to_user_id = !empty($data['reply_to_user_id']) ? $data['reply_to_user_id'] : null;
        $reply_to_id = !empty($data['reply_to_id']) ? $data['reply_to_id'] : null;

        $guest_name = !empty($data['guest_name']) ? $data['guest_name'] : null;
        $guest_email = !empty($data['guest_email']) ? $data['guest_email'] : null;

        if (!$user_id) {
            if (empty($guest_name)) {
                echo json_encode(['success' => false, 'message' => 'وارد کردن نام برای ارسال نظر به صورت مهمان اجباری است.']);
                exit;
            }
            if (empty($guest_email) || !filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'وارد کردن ایمیل معتبر برای ارسال نظر به صورت مهمان اجباری است.']);
                exit;
            }
        }

        if (empty($content)) {
            echo json_encode(['success' => false, 'message' => 'متن نظر نمی‌تواند خالی باشد.']);
            exit;
        }

        $image_url = null;
        if ($type === 'analysis' && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_exts = ['png', 'webp', 'avif', 'jpg', 'jpeg'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed_exts)) {
                echo json_encode(['success' => false, 'message' => 'فرمت تصویر معتبر نیست. فرمت‌های مجاز: png, webp, avif, jpg, jpeg']);
                exit;
            }
            $image_url = handle_upload($_FILES['image'], 'uploads/comments/');
        }

        $id = $comments_handler->addComment($user_id, $target_id, $target_type, $content, $parent_id, $reply_to_user_id, $reply_to_id, $mentions, $guest_name, $guest_email, $type, $image_url);
        if ($id) {
            record_rate_limit_attempt('comment', 'ip', $ip);
            if ($user_id) record_rate_limit_attempt('comment', 'user', $user_id);

            // Fetch the full comment data to return for AJAX insertion
            $new_comment = $comments_handler->getComment($id, $user_id);
            $total_count = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE target_id = ? AND target_type = ? AND status = 'approved' AND parent_id IS NULL");
            $total_count->execute([$target_id, $target_type]);
            $count = $total_count->fetchColumn();

            $msg = ($new_comment['status'] === 'pending')
                   ? 'نظر شما ثبت شد و پس از تایید مدیر نمایش داده خواهد شد.'
                   : 'نظر شما با موفقیت ثبت شد.';

            echo json_encode([
                'success' => true,
                'id' => $id,
                'comment' => $new_comment,
                'total_count' => $count,
                'message' => $msg
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در ثبت نظر.']);
        }
        exit;
    }

    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'لطفا ابتدا وارد حساب خود شوید.']);
        exit;
    }

    if ($action === 'react') {
        // Rate Limiting (30 per 15 mins by default)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ip_limit = check_rate_limit('comment_react', 'ip', $ip, null, 30, 15);
        if ($ip_limit !== true) {
            echo json_encode(['success' => false, 'message' => 'تعداد واکنش‌های شما بیش از حد مجاز است. لطفاً کمی صبر کنید.']);
            exit;
        }

        $user_limit = check_rate_limit('comment_react', 'user', $user_id, null, 30, 15);
        if ($user_limit !== true) {
            echo json_encode(['success' => false, 'message' => 'لطفاً کمی صبر کنید.']);
            exit;
        }

        $comment_id = $input['comment_id'] ?? 0;
        $reaction_type = $input['reaction_type'] ?? null;
        $success = $comments_handler->react($user_id, $comment_id, $reaction_type);
        if ($success) {
            record_rate_limit_attempt('comment_react', 'ip', $ip);
            record_rate_limit_attempt('comment_react', 'user', $user_id);

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
        $mentions = $input['mentions'] ?? [];
        $type = $input['type'] ?? null;
        $success = $comments_handler->updateComment($user_id, $comment_id, $content, $mentions, $type);
        if ($success) {
            // Get the updated comment to have correctly parsed mentions
            $updated_comment = $comments_handler->getComment($comment_id, $user_id);
            echo json_encode([
                'success' => true,
                'message' => 'نظر ویرایش شد.',
                'comment' => $updated_comment,
                'content_html' => $updated_comment['content_html'],
                'content' => $content
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'امکان ویرایش این نظر وجود ندارد (زمان سپری شده است یا نظر متعلق به شما نیست).']);
        }
        exit;
    }

    if ($action === 'delete') {
        $comment_id = $input['comment_id'] ?? 0;
        $success = $comments_handler->deleteComment($user_id, $comment_id);
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'نظر حذف شد.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'امکان حذف این نظر وجود ندارد (زمان سپری شده است یا نظر متعلق به شما نیست).']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر.']);
