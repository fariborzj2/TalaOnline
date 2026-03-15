<?php
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/mail.php';
require_once 'includes/notifications.php';
require_once 'includes/push_service.php';
require_once 'includes/migrations.php';

$pdo = new PDO('sqlite:site/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$push = new PushService($pdo);

$pdo->exec("DELETE FROM notification_queue");
$pdo->exec("DELETE FROM notification_deduplication");
$pdo->exec("UPDATE users SET updated_at = null");
$pdo->exec("DELETE FROM rate_limits");

$userA = 1;

$start = microtime(true);
$resT2_1 = $push->notify($userA, 'social_like', ['sender_name' => 'UserB', 'url' => '/post/43'], ['sender_id' => 2]);
$resT2_2 = $push->notify($userA, 'social_like', ['sender_name' => 'UserB', 'url' => '/post/43'], ['sender_id' => 2]);
$latency = round((microtime(true) - $start) * 1000);

echo "res1: $resT2_1 res2: $resT2_2\n";

$q = $pdo->query("SELECT * FROM notification_queue");
$rows = $q->fetchAll(PDO::FETCH_ASSOC);

echo "Total channels queued: " . count($rows) . " (should be 3 if deduplication worked, instead of 6)\n";
print_r($rows);
