<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/helpers.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'لطفا وارد حساب خود شوید.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT symbol FROM watchlist WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $symbols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'symbols' => $symbols]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Check
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        echo json_encode(['success' => false, 'message' => 'خطای امنیتی.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $symbol = $input['symbol'] ?? '';

    if (empty($symbol)) {
        echo json_encode(['success' => false, 'message' => 'نماد الزامی است.']);
        exit;
    }

    if ($action === 'toggle') {
        $stmt = $pdo->prepare("SELECT 1 FROM watchlist WHERE user_id = ? AND symbol = ?");
        $stmt->execute([$user_id, $symbol]);
        $exists = $stmt->fetchColumn();

        if ($exists) {
            $stmt = $pdo->prepare("DELETE FROM watchlist WHERE user_id = ? AND symbol = ?");
            $stmt->execute([$user_id, $symbol]);
            $status = 'removed';
        } else {
            $stmt = $pdo->prepare("INSERT INTO watchlist (user_id, symbol) VALUES (?, ?)");
            $stmt->execute([$user_id, $symbol]);
            $status = 'added';
        }
        echo json_encode(['success' => true, 'status' => $status]);
        exit;
    }
}
