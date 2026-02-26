<?php
/**
 * Market Sentiment API
 */

// Ensure no previous output (like warnings) breaks the JSON response
if (ob_get_level()) ob_clean();

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/sentiment.php';

ensure_session();

$action = $_GET['action'] ?? '';
$sentiment_handler = new MarketSentiment($pdo);

$user_id = $_SESSION['user_id'] ?? null;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        echo json_encode(['success' => false, 'message' => 'خطای امنیتی: توکن نامعتبر است.']);
        exit;
    }

    if ($action === 'vote') {
        $input = json_decode(file_get_contents('php://input'), true);
        $currency_id = $input['currency_id'] ?? '';
        $vote = $input['vote'] ?? '';

        if (empty($currency_id) || !in_array($vote, ['bullish', 'bearish'])) {
            echo json_encode(['success' => false, 'message' => 'ورودی‌های نامعتبر.']);
            exit;
        }

        $success = $sentiment_handler->vote($currency_id, $user_id, $ip_address, $vote);

        if ($success) {
            $results = $sentiment_handler->getResults($currency_id);
            echo json_encode([
                'success' => true,
                'message' => 'رأی شما با موفقیت ثبت شد.',
                'results' => $results,
                'user_vote' => $vote,
                'today_fa' => jalali_date('now', 'day_month')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در ثبت رأی.']);
        }
        exit;
    }
} else {
    if ($action === 'get_results') {
        $currency_id = $_GET['currency_id'] ?? '';
        if (empty($currency_id)) {
            echo json_encode(['success' => false, 'message' => 'شناسه ارز نامشخص است.']);
            exit;
        }

        $results = $sentiment_handler->getResults($currency_id);
        $user_vote_data = $sentiment_handler->getUserVote($currency_id, $user_id, $ip_address);

        echo json_encode([
            'success' => true,
            'results' => $results,
            'user_vote' => $user_vote_data ? $user_vote_data['vote'] : null,
            'today_fa' => jalali_date('now', 'day_month')
        ]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر.']);
