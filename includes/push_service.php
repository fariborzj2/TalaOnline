<?php
/**
 * Unified Push & Notification Service
 */

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushService {
    private $pdo;
    private $webPush;
    private $userSettingsCache = [];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Preload notification settings for a list of users to prevent N+1 queries.
     */
    public function preloadUserSettings(array $user_ids) {
        $user_ids = array_unique(array_filter($user_ids));

        // Find which ones we don't have in cache yet
        $to_fetch = [];
        foreach ($user_ids as $uid) {
            if (!array_key_exists($uid, $this->userSettingsCache)) {
                $to_fetch[] = $uid;
            }
        }

        if (empty($to_fetch)) return;

        $placeholders = implode(',', array_fill(0, count($to_fetch), '?'));
        $stmt = $this->pdo->prepare("SELECT * FROM notification_settings WHERE user_id IN ($placeholders)");
        $stmt->execute($to_fetch);

        $fetched = [];
        while ($row = $stmt->fetch()) {
            $fetched[$row['user_id']] = $row;
            $this->userSettingsCache[$row['user_id']] = $row;
        }

        // Mark missing ones as "default" (null/false, or default structure) so we don't fetch them again
        foreach ($to_fetch as $uid) {
            if (!isset($fetched[$uid])) {
                $this->userSettingsCache[$uid] = false;
            }
        }
    }

    /**
     * Get WebPush instance lazily
     */
    private function getWebPush() {
        if ($this->webPush) return $this->webPush;

        if (!class_exists('Minishlink\WebPush\WebPush')) {
            error_log("WebPush class not found. Ensure composer dependencies are installed.");
            return null;
        }

        $publicKey = $this->normalizeKey(get_setting('webpush_public_key'));
        $privateKey = $this->normalizeKey(get_setting('webpush_private_key'), true);
        $subject = get_setting('webpush_subject', 'mailto:admin@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

        if (!$publicKey || !$privateKey) return null;

        $auth = [
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ];

        $this->webPush = new WebPush($auth);
        return $this->webPush;
    }

    /**
     * Subscribe a user to Web Push
     */
    public function subscribe($user_id, $subscription_data) {
        if (!$this->pdo) return false;

        try {
            if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                $stmt = $this->pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, content_encoding)
                                           VALUES (?, ?, ?, ?, ?)
                                           ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), updated_at = CURRENT_TIMESTAMP");
            } else {
                $stmt = $this->pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, content_encoding)
                                           VALUES (?, ?, ?, ?, ?)
                                           ON CONFLICT(endpoint) DO UPDATE SET user_id = excluded.user_id, updated_at = CURRENT_TIMESTAMP");
            }

            return $stmt->execute([
                $user_id,
                $subscription_data['endpoint'],
                $subscription_data['keys']['p256dh'],
                $subscription_data['keys']['auth'],
                $subscription_data['contentEncoding'] ?? 'aes128gcm'
            ]);
        } catch (Exception $e) {
            error_log("Push Subscribe Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Queue a notification for a user
     */
    public function notify($user_id, $template_slug, $data = [], $options = []) {
        if (!$this->pdo) return false;

        // Check user settings
        $settings = $this->getUserSettings($user_id);
        $template = $this->getTemplate($template_slug);

        if (!$template) return false;

        // Check if category is enabled for user
        $categories = json_decode($settings['categories'] ?? '[]', true);

        $category = $options['category'] ?? null;
        if (!$category) {
            $slug = $template['slug'];
            if (str_starts_with($slug, 'social_') || in_array($slug, ['interest_clustering_suggest', 'influencer_engagement'])) {
                $category = 'social';
            } elseif (str_starts_with($slug, 'blog_') || $slug === 'category_expert_update') {
                $category = 'blog';
            } else {
                $category = 'market';
            }
        }

        $ignore_limits = !empty($options['ignore_limits']);

        if (!$ignore_limits && !empty($categories) && !in_array($category, $categories)) {
            return true; // Preference enforced: don't queue
        }

        // Enforce user channel preferences
        $user_channels = json_decode($settings['channels'] ?? '[]', true);
        $template_channels = explode(',', $template['channels']);
        $final_channels = [];

        if (!empty($user_channels) && !$ignore_limits) {
            foreach ($template_channels as $tc) {
                $tc = trim($tc);
                if (in_array($tc, $user_channels)) {
                    $final_channels[] = $tc;
                }
            }
        } else {
            // Default to all template channels if user has no explicit settings or limits ignored
            $final_channels = $template_channels;
        }

        if (empty($final_channels)) {
            return true; // All channels disabled by user
        }

        // Frequency Capping Logic
        $limit = (int)($settings['frequency_limit'] ?? 5);
        if ($limit > 0 && !$ignore_limits) {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notification_queue WHERE user_id = ? AND created_at > datetime('now', '-24 hours')");
            if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notification_queue WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            }
            $stmt->execute([$user_id]);
            if ($stmt->fetchColumn() >= $limit) {
                return true; // Cap reached
            }
        }

        // Engagement Optimization: Predict best hour to send (based on last activity)
        if (!$ignore_limits && !isset($options['scheduled_at']) && ($options['priority'] ?? $template['priority']) === 'low') {
            $stmt = $this->pdo->prepare("SELECT strftime('%H:%M', updated_at) FROM users WHERE id = ?");
            if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                $stmt = $this->pdo->prepare("SELECT DATE_FORMAT(updated_at, '%H:%i') FROM users WHERE id = ?");
            }
            $stmt->execute([$user_id]);
            $active_hour = $stmt->fetchColumn();

            if ($active_hour) {
                $target = new DateTime('today ' . $active_hour, new DateTimeZone($settings['timezone'] ?? 'Asia/Tehran'));
                if ($target < new DateTime('now', new DateTimeZone($settings['timezone'] ?? 'Asia/Tehran'))) {
                    $target->modify('+1 day');
                }
                $options['scheduled_at'] = $target->format('Y-m-d H:i:s');
            }
        }

        // Quiet Hours Logic
        if (!$ignore_limits && !empty($settings['quiet_hours_start']) && !empty($settings['quiet_hours_end'])) {
            $now = new DateTime('now', new DateTimeZone($settings['timezone'] ?? 'UTC'));
            $current_time = $now->format('H:i');

            $is_quiet = false;
            if ($settings['quiet_hours_start'] < $settings['quiet_hours_end']) {
                $is_quiet = ($current_time >= $settings['quiet_hours_start'] && $current_time <= $settings['quiet_hours_end']);
            } else {
                // Spans across midnight
                $is_quiet = ($current_time >= $settings['quiet_hours_start'] || $current_time <= $settings['quiet_hours_end']);
            }

            if ($is_quiet && ($options['priority'] ?? $template['priority']) !== 'high') {
                return true; // Suppress non-high priority during quiet hours
            }
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO notification_queue (user_id, template_slug, data, channels, priority, scheduled_at)
                                       VALUES (?, ?, ?, ?, ?, ?)");

            $channels = $options['channels'] ?? implode(',', $final_channels);
            $priority = $options['priority'] ?? $template['priority'];
            $scheduled_at = $options['scheduled_at'] ?? null;

            return $stmt->execute([
                $user_id,
                $template_slug,
                json_encode($data),
                $channels,
                $priority,
                $scheduled_at
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Process the notification queue
     */
    public function processQueue($limit = 50) {
        if (!$this->pdo) return 0;

        $stmt = $this->pdo->prepare("SELECT * FROM notification_queue
                                   WHERE status = 'pending'
                                   AND (scheduled_at IS NULL OR scheduled_at <= CURRENT_TIMESTAMP)
                                   ORDER BY priority = 'high' DESC, created_at ASC
                                   LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        $processed = 0;
        foreach ($items as $item) {
            if ($this->sendImmediately($item)) {
                $processed++;
            }
        }
        return $processed;
    }

    /**
     * Send a single notification immediately
     */
    private function sendImmediately($queue_item) {
        $user_id = $queue_item['user_id'];
        $template = $this->getTemplate($queue_item['template_slug']);
        $data = json_decode($queue_item['data'], true);
        $channels = explode(',', $queue_item['channels']);

        $success = true;
        $errors = [];

        // Prepare content
        $title = $this->renderString($template['title'], $data);
        $body = $this->renderString($template['body'], $data);
        $action_url = $this->renderString($template['action_url'], $data);
        $icon = $template['icon'] ?: get_setting('site_logo_url');

        foreach ($channels as $channel) {
            $channel = trim($channel);
            try {
                if ($channel === 'webpush') {
                    $this->sendWebPush($user_id, $title, $body, $action_url, $icon);
                } elseif ($channel === 'email') {
                    $this->sendEmail($user_id, $title, $body, $action_url);
                } elseif ($channel === 'in-app') {
                    $this->sendInApp($user_id, $template['slug'], $queue_item['id']);
                }

                $this->logAnalytics($queue_item['id'], $channel, 'sent');
            } catch (Exception $e) {
                $success = false;
                $errors[] = "$channel: " . $e->getMessage();
                $this->logAnalytics($queue_item['id'], $channel, 'failed', ['error' => $e->getMessage()]);
            }
        }

        $status = $success ? 'sent' : 'failed';
        $attempts = $queue_item['attempts'] + 1;
        $last_error = !empty($errors) ? implode(' | ', $errors) : null;

        $stmt = $this->pdo->prepare("UPDATE notification_queue SET status = ?, attempts = ?, last_error = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$status, $attempts, $last_error, $queue_item['id']]);

        return $success;
    }

    private function sendWebPush($user_id, $title, $body, $action_url, $icon) {
        $webPush = $this->getWebPush();
        if (!$webPush) return false;

        $stmt = $this->pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $subscriptions = $stmt->fetchAll();

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $action_url,
            'icon' => $icon
        ]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'publicKey' => $sub['p256dh'],
                'authToken' => $sub['auth'],
                'contentEncoding' => $sub['content_encoding'],
            ]);

            $webPush->queueNotification($subscription, $payload);
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                if ($report->isSubscriptionExpired()) {
                    // Cleanup expired subscription
                    $stmt = $this->pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
                    $stmt->execute([$report->getEndpoint()]);
                }
            }
        }
        return true;
    }

    private function sendEmail($user_id, $title, $body, $action_url) {
        $stmt = $this->pdo->prepare("SELECT email, name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && $user['email']) {
            $content = "{$body}<br><br><a href='{$action_url}' style='display:inline-block; padding:10px 20px; background:#e29b21; color:#fff; text-decoration:none; border-radius:5px;'>مشاهده جزئیات</a>";
            return Mail::queueRaw($user['email'], $title, Mail::getProfessionalLayout($content));
        }
        return false;
    }

    private function sendInApp($user_id, $type, $target_id) {
        $notif = new Notifications($this->pdo);
        return $notif->create($user_id, 0, $type, $target_id);
    }

    private $templateCache = [];

    private function getTemplate($slug) {
        if (isset($this->templateCache[$slug])) {
            return $this->templateCache[$slug];
        }

        // Performance optimization:
        // This computation previously executed on every render.
        // Memoization prevents recomputation when inputs remain unchanged.
        $stmt = $this->pdo->prepare("SELECT * FROM notification_templates WHERE slug = ?");
        $stmt->execute([$slug]);
        $this->templateCache[$slug] = $stmt->fetch();
        return $this->templateCache[$slug];
    }

    private function getUserSettings($user_id) {
        if (!array_key_exists($user_id, $this->userSettingsCache)) {
            $stmt = $this->pdo->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $this->userSettingsCache[$user_id] = $stmt->fetch();
        }

        $settings = $this->userSettingsCache[$user_id];

        if (!$settings) {
            return [
                'categories' => json_encode(['market', 'social', 'blog']),
                'frequency_limit' => 5,
                'timezone' => 'Asia/Tehran'
            ];
        }
        return $settings;
    }

    /**
     * Normalize VAPID keys to raw URL-safe Base64 format if provided in SPKI/PKCS8/PEM
     */
    private function normalizeKey($key, $isPrivate = false) {
        if (empty($key)) return '';

        // Remove PEM headers/footers and whitespaces
        $key = preg_replace('/-----BEGIN.*?-----|-----END.*?-----|[\s\n\r"\'=]/', '', $key);

        // Try to decode as standard base64 (since we removed = we use a helper or add them back)
        $padding = strlen($key) % 4;
        $decoded = base64_decode($padding ? $key . str_repeat('=', 4 - $padding) : $key);

        if (!$decoded) return $key;

        $raw = $decoded;

        // Public key SPKI (91 bytes) -> Raw (65 bytes)
        if (!$isPrivate && strlen($decoded) === 91 && ord($decoded[0]) === 0x30) {
            $raw = substr($decoded, -65);
        }

        // Private key PKCS8 (typical 118-122 bytes) -> Raw (32 bytes)
        if ($isPrivate && (strlen($decoded) >= 118 && strlen($decoded) <= 122) && ord($decoded[0]) === 0x30) {
             // ECDSA Private Key usually contains the 32-byte secret at offset 7
             $raw = substr($decoded, 7, 32);
        }

        // Return URL-safe Base64
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($raw));
    }

    private function renderString($string, $data) {
        foreach ($data as $key => $value) {
            $string = str_replace('{' . $key . '}', $value, $string);
        }
        return $string;
    }

    private function logAnalytics($notification_id, $channel, $event_type, $metadata = []) {
        $stmt = $this->pdo->prepare("INSERT INTO notification_analytics (notification_id, channel, event_type, metadata) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$notification_id, $channel, $event_type, json_encode($metadata)]);
    }
}
