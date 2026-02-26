<?php
/**
 * Market Sentiment API
 */

ob_start(); // Start buffering all output

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/sentiment.php';

ensure_session();

$action = $_GET['action'] ?? '';
$sentiment_handler = new MarketSentiment($pdo);

$user_id = $_SESSION['user_id'] ?? null;
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$response = ['success' => false, 'message' => 'درخواست نامعتبر.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        $response = ['success' => false, 'message' => 'خطای امنیتی: توکن نامعتبر است.'];
    } elseif ($action === 'vote') {
        $input = json_decode(file_get_contents('php://input'), true);
        $currency_id = $input['currency_id'] ?? '';
        $vote = $input['vote'] ?? '';

        if (empty($currency_id) || !in_array($vote, ['bullish', 'bearish'])) {
            $response = ['success' => false, 'message' => 'ورودی‌های نامعتبر.'];
        } else {
            $success = $sentiment_handler->vote($currency_id, $user_id, $ip_address, $vote);

            if ($success) {
                $results = $sentiment_handler->getResults($currency_id);
                $response = [
                    'success' => true,
                    'message' => 'رأی شما با موفقیت ثبت شد.',
                    'results' => $results,
                    'user_vote' => $vote,
                    'today_fa' => jalali_date('now', 'day_month')
                ];
            } else {
                $response = ['success' => false, 'message' => 'خطا در ثبت رأی.'];
            }
        }
    }
} else {
    if ($action === 'get_results') {
        $currency_id = $_GET['currency_id'] ?? '';
        if (empty($currency_id)) {
            $response = ['success' => false, 'message' => 'شناسه ارز نامشخص است.'];
        } else {
            $results = $sentiment_handler->getResults($currency_id);
            $user_vote_data = $sentiment_handler->getUserVote($currency_id, $user_id, $ip_address);

            $response = [
                'success' => true,
                'results' => $results,
                'user_vote' => $user_vote_data ? $user_vote_data['vote'] : null,
                'today_fa' => jalali_date('now', 'day_month')
            ];
        }
    }
}

// Ensure no previous output (like warnings) breaks the JSON response
if (ob_get_level()) ob_clean();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_UNESCAPED_UNICODE);
ob_end_flush();
