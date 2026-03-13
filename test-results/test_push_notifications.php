<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/push_service.php';
require_once __DIR__ . '/../includes/migrations.php';
require_once __DIR__ . '/../includes/trigger_engine.php';
require_once __DIR__ . '/../includes/notifications.php';

// Setup Test DB
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize Schema
$migrationManager = new MigrationManager($pdo, 1);
$migrationManager->execute();

echo "Database schema initialized.\n";

// Mocking function to override the default Mail class behavior during tests
class MockMail {
    public static $sent = [];
    public static function queueRaw($to, $subject, $body) {
        self::$sent[] = ['to' => $to, 'subject' => $subject, 'body' => $body];
        return true;
    }
    public static function getProfessionalLayout($content) {
        return "<html><body>$content</body></html>";
    }
}
class_alias('MockMail', 'Mail');

// Test Suite Class
class PushNotificationTest {
    private $pdo;
    private $pushService;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->pushService = new PushService($pdo);
    }

    public function run() {
        echo "Starting Push Notification Test Suite...\n\n";

        $this->setupTestData();
        $this->testNotificationSettingsAndPreloading();
        $this->testTemplateRendering();
        $this->testSubscription();
        $this->testNotificationQueueingAndCategoryEnforcement();
        $this->testFrequencyCapping();
        $this->testQuietHours();
        $this->testEngagementOptimization();
        $this->testQueueProcessing();
        $this->testAllTemplates();
        $this->testHeavyLoad();

        echo "\nTest Suite Complete.\n";
    }

    private function setupTestData() {
        echo "Setting up test data...\n";

        // Settings
        $this->pdo->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('webpush_public_key', 'BO8i11u81h9hJ-q77Yv8l2O3b1689K8eP5L_P4gJ4V6y4sR8K1M9K7x9eB1m9o0N8zY5O4Q_0V7u3f9k8S0P4I0')");
        $this->pdo->exec("INSERT INTO settings (setting_key, setting_value) VALUES ('webpush_private_key', '8E0P9z_V6b0s5R4e2T6o5I8H3J9X4N8U3P2S9G0J5V1')");

        // Users
        $this->pdo->exec("INSERT INTO users (id, name, email) VALUES (1, 'User 1', 'user1@example.com')");
        $this->pdo->exec("INSERT INTO users (id, name, email) VALUES (2, 'User 2', 'user2@example.com')");
        $this->pdo->exec("INSERT INTO users (id, name, email) VALUES (3, 'User 3', 'user3@example.com')");
        $this->pdo->exec("INSERT INTO users (id, name, email) VALUES (4, 'User 4', 'user4@example.com')");

        // Settings - User 1 (Default)

        // Settings - User 2 (Opt out of social, frequency cap 2, no webpush)
        $this->pdo->exec("INSERT INTO notification_settings (user_id, categories, channels, frequency_limit, timezone) VALUES (2, '[\"market\",\"blog\"]', '[\"email\",\"in-app\"]', 2, 'UTC')");

        // Settings - User 3 (Quiet hours active right now)
        $tz = new DateTimeZone('UTC');
        $now = new DateTime('now', $tz);
        $start = (clone $now)->modify('-1 hour')->format('H:i');
        $end = (clone $now)->modify('+1 hour')->format('H:i');

        $stmt = $this->pdo->prepare("INSERT INTO notification_settings (user_id, categories, channels, frequency_limit, timezone, quiet_hours_start, quiet_hours_end) VALUES (3, '[\"market\",\"social\",\"blog\"]', '[\"webpush\",\"email\",\"in-app\"]', 5, 'UTC', ?, ?)");
        $stmt->execute([$start, $end]);

        // Settings - User 4 (High frequency limit, all enabled, weird timezone)
        $this->pdo->exec("INSERT INTO notification_settings (user_id, categories, channels, frequency_limit, timezone) VALUES (4, '[\"market\",\"social\",\"blog\"]', '[\"webpush\",\"email\",\"in-app\"]', 100, 'Asia/Tehran')");
    }

    private function testNotificationSettingsAndPreloading() {
        echo "Testing preloading user settings...\n";
        $this->pushService->preloadUserSettings([1, 2, 3, 4, 99]); // 99 doesn't exist

        $reflector = new ReflectionClass(PushService::class);
        $property = $reflector->getProperty('userSettingsCache');
        $property->setAccessible(true);
        $cache = $property->getValue($this->pushService);

        if (count($cache) === 5) {
            echo "✅ Settings preloaded correctly.\n";
        } else {
            echo "❌ Settings preloading failed. Count: " . count($cache) . "\n";
        }
    }

    private function testTemplateRendering() {
        echo "Testing template rendering logic implicitly in notify process...\n";
        $this->pushService->notify(1, 'asset_volatility', ['name' => 'Bitcoin', 'symbol' => 'BTC', 'type' => 'افزایش', 'change' => '5', 'url' => '/btc']);

        $stmt = $this->pdo->query("SELECT * FROM notification_queue WHERE user_id = 1");
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        $data = json_decode($item['data'], true);
        if ($data['name'] === 'Bitcoin' && $data['symbol'] === 'BTC') {
             echo "✅ Template data serialization passed.\n";
        } else {
             echo "❌ Template data serialization failed.\n";
        }
        $this->pdo->exec("DELETE FROM notification_queue");
    }

    private function testAllTemplates() {
        echo "\nTesting All Configured Templates (Rendering & Queueing)...\n";

        // Fetch all templates configured by migrations
        $stmt = $this->pdo->query("SELECT slug, channels FROM notification_templates");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = count($templates);
        $success = 0;

        // Create a dummy user specifically for this test, with all channels enabled
        $this->pdo->exec("INSERT INTO users (id, name, email) VALUES (5, 'Template Test User', 'template@example.com')");
        // Opt-in to all categories, all channels, no freq limit
        $this->pdo->exec("INSERT INTO notification_settings (user_id, categories, channels, frequency_limit, timezone) VALUES (5, '[\"market\",\"social\",\"blog\"]', '[\"webpush\",\"email\",\"in-app\"]', 1000, 'UTC')");

        $mock_data = [
            'name' => 'TestName', 'symbol' => 'TST', 'type' => 'TestType', 'change' => '100',
            'url' => '#test', 'sender_name' => 'Sender', 'count' => '99', 'title' => 'TestTitle',
            'category' => 'TestCategory', 'consensus' => 'Bullish', 'actor_name' => 'VIPUser',
            'level' => '1000', 'price' => '2000', 'label' => 'Support', 'views' => '50k',
            'follower_name' => 'NewFan', 'suggested_name' => 'SuggestUser', 'deviation' => '500'
        ];

        foreach ($templates as $tpl) {
            $slug = $tpl['slug'];

            // Queue notification
            $queued = $this->pushService->notify(5, $slug, $mock_data);

            if (!$queued) {
                echo "❌ Failed to queue template: $slug\n";
                continue;
            }

            // Verify in DB
            $item = $this->pdo->query("SELECT * FROM notification_queue WHERE user_id = 5 AND template_slug = '$slug' AND status = 'pending'")->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                $success++;
            } else {
                echo "❌ Template $slug was not stored correctly.\n";
            }
        }

        // Some templates are low priority and might be scheduled instead of queued for immediate execution
        // depending on engagement optimization. We update scheduled_at to NULL so they process immediately.
        $this->pdo->exec("UPDATE notification_queue SET scheduled_at = NULL WHERE user_id = 5");

        // Process them immediately to verify send execution
        // Webpush is configured globally for tests with dummy keys but we must ignore failures for webpush
        // because we are testing queue generation and layout binding. We count them as processed if they transition to sent/failed.

        $this->pushService->processQueue(100);
        $processed = $this->pdo->query("SELECT COUNT(*) FROM notification_queue WHERE user_id = 5 AND status != 'pending'")->fetchColumn();

        if ($success === $total && $processed === $total) {
             echo "✅ Successfully tested all $total templates (Queued and Processed).\n";
        } else {
             echo "❌ Template Test failed. Queued: $success/$total, Processed: $processed/$total.\n";
        }
    }

    private function testSubscription() {
        echo "Testing WebPush Subscription...\n";
        $subData = [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/test_endpoint_123',
            'keys' => [
                'p256dh' => 'BLtest_p256dh_key',
                'auth' => 'test_auth_key'
            ],
            'contentEncoding' => 'aes128gcm'
        ];

        $res = $this->pushService->subscribe(1, $subData);
        if ($res) {
            $count = $this->pdo->query("SELECT COUNT(*) FROM push_subscriptions WHERE user_id = 1")->fetchColumn();
            if ($count == 1) {
                echo "✅ Subscription created successfully.\n";
            } else {
                echo "❌ Subscription insert failed.\n";
            }
        } else {
            echo "❌ Subscription returned false.\n";
        }

        // Test upsert
        $this->pushService->subscribe(2, $subData); // Same endpoint, different user
        $count = $this->pdo->query("SELECT COUNT(*) FROM push_subscriptions WHERE user_id = 1")->fetchColumn();
        $count2 = $this->pdo->query("SELECT COUNT(*) FROM push_subscriptions WHERE user_id = 2")->fetchColumn();
        if ($count == 0 && $count2 == 1) {
            echo "✅ Subscription endpoint transfer (upsert) succeeded.\n";
        } else {
            echo "❌ Subscription endpoint transfer failed.\n";
        }
    }

    private function testNotificationQueueingAndCategoryEnforcement() {
        echo "Testing Notification Queueing and Category/Channel enforcement...\n";

        // User 2: Opted out of social, no webpush
        $this->pushService->notify(2, 'social_reply', ['sender_name' => 'Test', 'url' => '#']); // Category social

        $count = $this->pdo->query("SELECT COUNT(*) FROM notification_queue WHERE user_id = 2 AND template_slug = 'social_reply'")->fetchColumn();
        if ($count == 0) {
            echo "✅ Opt-out category 'social' enforced correctly.\n";
        } else {
            echo "❌ Category enforcement failed.\n";
        }

        $this->pushService->notify(2, 'asset_volatility', ['name' => 'X', 'symbol' => 'Y', 'type' => 'Z', 'change' => '1', 'url' => '#']); // Category market
        $item = $this->pdo->query("SELECT channels FROM notification_queue WHERE user_id = 2 AND template_slug = 'asset_volatility'")->fetchColumn();
        if ($item === 'email,in-app') {
            echo "✅ Channel restriction enforced correctly.\n";
        } else {
            echo "❌ Channel restriction failed. Got: $item\n";
        }
    }

    private function testFrequencyCapping() {
        echo "Testing Frequency Capping...\n";

        // User 2 has frequency cap of 2
        // We already added 1 in previous test ('asset_volatility')
        $this->pushService->notify(2, 'blog_new_post', ['title' => 'T', 'category' => 'C', 'url' => '#']); // Count = 2
        $this->pushService->notify(2, 'blog_new_post', ['title' => 'T2', 'category' => 'C', 'url' => '#']); // Count = 3 (Should be blocked)

        $count = $this->pdo->query("SELECT COUNT(*) FROM notification_queue WHERE user_id = 2")->fetchColumn();
        if ($count == 2) {
            echo "✅ Frequency capping enforced correctly.\n";
        } else {
            echo "❌ Frequency capping failed. Found $count items.\n";
        }
    }

    private function testQuietHours() {
        echo "Testing Quiet Hours...\n";

        // User 3 is currently in quiet hours.
        // Low priority should be suppressed.
        $this->pushService->notify(3, 'social_trending', ['title' => 'T', 'url' => '#']); // priority: low
        $count = $this->pdo->query("SELECT COUNT(*) FROM notification_queue WHERE user_id = 3 AND template_slug = 'social_trending'")->fetchColumn();
        if ($count == 0) {
            echo "✅ Quiet hours enforced for low priority.\n";
        } else {
            echo "❌ Quiet hours failed for low priority.\n";
        }

        // High priority should pass through
        $this->pushService->notify(3, 'asset_volatility', ['name' => 'B', 'symbol' => 'C', 'type' => 'D', 'change' => '1', 'url' => '#']); // priority: high
        $count = $this->pdo->query("SELECT COUNT(*) FROM notification_queue WHERE user_id = 3 AND template_slug = 'asset_volatility'")->fetchColumn();
        if ($count == 1) {
            echo "✅ Quiet hours allowed high priority through.\n";
        } else {
            echo "❌ Quiet hours blocked high priority.\n";
        }
    }

    private function testEngagementOptimization() {
        echo "Testing Engagement Optimization (Smart Scheduling)....\n";

        // Set user 1 last updated to a specific time
        $this->pdo->exec("UPDATE users SET updated_at = '2024-01-01 14:00:00' WHERE id = 1");

        // Notify with low priority
        $this->pushService->notify(1, 'social_trending', ['title' => 'T', 'url' => '#']);

        $scheduled_at = $this->pdo->query("SELECT scheduled_at FROM notification_queue WHERE user_id = 1 AND template_slug = 'social_trending'")->fetchColumn();

        if (strpos($scheduled_at, '14:00:00') !== false) {
             echo "✅ Engagement optimization scheduled correctly: $scheduled_at\n";
        } else {
             echo "❌ Engagement optimization failed: $scheduled_at\n";
        }
    }

    private function testQueueProcessing() {
        echo "Testing Queue Processing...\n";

        // Process items
        $processed = $this->pushService->processQueue(100);

        // User 2: asset_volatility, blog_new_post (email, in-app)
        // User 3: asset_volatility (webpush, email, in-app)
        // User 1 has scheduled item

        if ($processed > 0) {
            echo "✅ Processed $processed items from queue.\n";
        } else {
            echo "❌ Failed to process items from queue.\n";
        }

        // Check if sent statuses updated
        $pending = $this->pdo->query("SELECT COUNT(*) FROM notification_queue WHERE status = 'pending' AND scheduled_at IS NULL")->fetchColumn();
        if ($pending == 0) {
            echo "✅ All immediate queue items marked as sent/failed.\n";
        } else {
            echo "❌ Items left pending.\n";
        }

        // Check mock email sent
        if (count(MockMail::$sent) > 0) {
             echo "✅ Mock email triggered successfully (" . count(MockMail::$sent) . " sent).\n";
        } else {
             echo "❌ No emails sent.\n";
        }

        // Check in-app notifications created
        $inapp = $this->pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
        if ($inapp > 0) {
             echo "✅ In-app notifications generated ($inapp created).\n";
        } else {
             echo "❌ In-app notifications generation failed.\n";
        }
    }

    private function testHeavyLoad() {
        echo "\nTesting Heavy Load (1,000 users, 1,000 queued items)...\n";

        // 1. Insert 1000 users
        $stmt_user = $this->pdo->prepare("INSERT INTO users (id, name, email) VALUES (?, ?, ?)");
        $stmt_settings = $this->pdo->prepare("INSERT INTO notification_settings (user_id, categories, channels, frequency_limit, timezone) VALUES (?, '[\"market\",\"social\",\"blog\"]', '[\"email\",\"in-app\"]', 100, 'UTC')");

        $this->pdo->beginTransaction();
        for ($i = 1000; $i < 2000; $i++) {
            $stmt_user->execute([$i, "LoadUser $i", "load$i@example.com"]);
            $stmt_settings->execute([$i]);
        }
        $this->pdo->commit();
        echo "   - 1000 users inserted.\n";

        // 2. Queue 1000 notifications using trigger engine logic
        // Need to preload settings to prevent N+1 during queueing in heavy load!
        $this->pushService->preloadUserSettings(range(1000, 1999));

        $start_time = microtime(true);
        $this->pdo->beginTransaction();
        for ($i = 1000; $i < 2000; $i++) {
            $this->pushService->notify($i, 'asset_volatility', ['name' => 'LoadCoin', 'symbol' => 'LOD', 'type' => 'افزایش', 'change' => '10', 'url' => '#']);
        }
        $this->pdo->commit();
        $queue_time = microtime(true) - $start_time;

        $queued = $this->pdo->query("SELECT COUNT(*) FROM notification_queue WHERE user_id >= 1000")->fetchColumn();
        echo sprintf("   - Queued %d notifications in %.3f seconds.\n", $queued, $queue_time);

        if ($queued != 1000) {
            echo "❌ Failed to queue all notifications under load.\n";
            return;
        }

        // 3. Process the queue
        echo "   - Processing queue in batches of 250...\n";
        $process_start = microtime(true);
        $total_processed = 0;

        // Process 4 batches of 250
        for ($b = 0; $b < 4; $b++) {
            $processed = $this->pushService->processQueue(250);
            $total_processed += $processed;
        }
        $process_time = microtime(true) - $process_start;

        echo sprintf("   - Processed %d notifications in %.3f seconds.\n", $total_processed, $process_time);

        if ($total_processed == 1000) {
            echo "✅ Heavy Load Test Passed Successfully.\n";
        } else {
            echo "❌ Heavy Load Test Failed. Only processed $total_processed.\n";
        }
    }
}

$test = new PushNotificationTest($pdo);
$test->run();
