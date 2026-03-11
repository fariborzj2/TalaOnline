<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/push_service.php';

session_start();
$user_id = $_SESSION['user_id'] ?? null;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'subscribe') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }

    $pushService = new PushService($pdo);
    $success = $pushService->subscribe($user_id, $input);

    echo json_encode(['success' => $success]);
    return;
}

if ($action === 'unsubscribe') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['endpoint'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
    $success = $stmt->execute([$input['endpoint']]);

    echo json_encode(['success' => $success]);
    return;
}

if ($action === 'save_settings') {
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    $categories = json_encode($input['categories'] ?? []);
    $channels = json_encode($input['channels'] ?? []);
    $limit = (int)($input['frequency_limit'] ?? 5);
    $timezone = $input['timezone'] ?? 'Asia/Tehran';
    $quiet_start = $input['quiet_hours_start'] ?? null;
    $quiet_end = $input['quiet_hours_end'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO notification_settings (user_id, categories, channels, frequency_limit, timezone, quiet_hours_start, quiet_hours_end)
                           VALUES (?, ?, ?, ?, ?, ?, ?)
                           ON CONFLICT(user_id) DO UPDATE SET categories=excluded.categories, channels=excluded.channels, frequency_limit=excluded.frequency_limit, timezone=excluded.timezone, quiet_hours_start=excluded.quiet_hours_start, quiet_hours_end=excluded.quiet_hours_end, updated_at=CURRENT_TIMESTAMP");

    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $stmt = $pdo->prepare("INSERT INTO notification_settings (user_id, categories, channels, frequency_limit, timezone, quiet_hours_start, quiet_hours_end)
                               VALUES (?, ?, ?, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE categories=VALUES(categories), channels=VALUES(channels), frequency_limit=VALUES(frequency_limit), timezone=VALUES(timezone), quiet_hours_start=VALUES(quiet_hours_start), quiet_hours_end=VALUES(quiet_hours_end), updated_at=CURRENT_TIMESTAMP");
    }

    $success = $stmt->execute([$user_id, $categories, $channels, $limit, $timezone, $quiet_start, $quiet_end]);
    echo json_encode(['success' => $success]);
    return;
}

if ($action === 'get_settings') {
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    $stmt = $pdo->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetch();

    echo json_encode(['success' => true, 'settings' => $settings]);
    return;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
