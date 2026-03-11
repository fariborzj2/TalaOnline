<?php
/**
 * Unified Push & Notification Service
 */

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushService {
    private $pdo;
    private $webPush;

    public function __construct($pdo) {
        $this->pdo = $pdo;
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

        $publicKey = get_setting('webpush_public_key');
        $privateKey = get_setting('webpush_private_key');
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
        $category = $options['category'] ?? $template['slug'];
        if (!empty($categories) && !in_array($category, $categories)) {
            return true; // Preference enforced: don't queue
        }

        // Logic for frequency capping, quiet hours, etc. could go here

        try {
            $stmt = $this->pdo->prepare("INSERT INTO notification_queue (user_id, template_slug, data, channels, priority, scheduled_at)
                                       VALUES (?, ?, ?, ?, ?, ?)");

            $channels = $options['channels'] ?? $template['channels'];
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

    private function getTemplate($slug) {
        $stmt = $this->pdo->prepare("SELECT * FROM notification_templates WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    private function getUserSettings($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM notification_settings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $settings = $stmt->fetch();

        if (!$settings) {
            return [
                'categories' => json_encode(['market', 'social', 'blog']),
                'frequency_limit' => 5,
                'timezone' => 'Asia/Tehran'
            ];
        }
        return $settings;
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
