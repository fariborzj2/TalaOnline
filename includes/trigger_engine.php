<?php
/**
 * Trigger Engine for Notifications
 */

require_once __DIR__ . '/helpers.php';

class TriggerEngine {
    private $pdo;
    private $pushService;

    public function __construct($pdo, $pushService) {
        $this->pdo = $pdo;
        $this->pushService = $pushService;
    }

    /**
     * Trigger: Asset Volatility Spike
     */
    public function handleVolatilitySpike($symbol, $change_percent) {
        if (abs($change_percent) < 5) return;

        $stmt = $this->pdo->prepare("SELECT name FROM items WHERE symbol = ?");
        $stmt->execute([$symbol]);
        $name = $stmt->fetchColumn();

        // In a real system, we'd find users who "follow" or "track" this asset.
        // For now, let's assume we notify active users interested in 'market'.
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_verified = 1 LIMIT 100"); // Example
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $type = $change_percent > 0 ? 'افزایش' : 'کاهش';

        foreach ($users as $user_id) {
            $this->pushService->notify($user_id, 'asset_volatility', [
                'symbol' => $symbol,
                'name' => $name,
                'change' => abs($change_percent),
                'type' => $type,
                'url' => get_site_url() . "/market/$symbol"
            ]);
        }
    }

    /**
     * Trigger: Deep Interaction (Social)
     */
    public function handleCommentInteraction($comment_id, $parent_id, $sender_name) {
        // This is partially handled in Comments class already, but we can extend it here for push
        $stmt = $this->pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->execute([$parent_id]);
        $target_user_id = $stmt->fetchColumn();

        if ($target_user_id) {
            $this->pushService->notify($target_user_id, 'social_reply', [
                'sender_name' => $sender_name,
                'url' => get_site_url() . "/thread/$comment_id" // Placeholder URL
            ]);
        }
    }

    /**
     * Trigger: Breaking Economic Impact (Blog)
     */
    public function handleNewBlogPost($post_id, $title, $category_name) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_verified = 1 LIMIT 100");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $user_id) {
            $this->pushService->notify($user_id, 'blog_new_post', [
                'title' => $title,
                'category' => $category_name,
                'url' => get_site_url() . "/blog/post/$post_id"
            ]);
        }
    }

    /**
     * Trigger: Predictive Re-hook (Churn Prevention)
     */
    public function handleChurnPrevention() {
        // Logic to find users who haven't logged in for 3 days
        // We also check notification_queue to ensure we don't spam them with the same template too often
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $sql = "SELECT u.id, u.name FROM users u
                    LEFT JOIN notification_queue n ON u.id = n.user_id AND n.template_slug = 'rehook_market_recap' AND n.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    WHERE u.updated_at < DATE_SUB(NOW(), INTERVAL 3 DAY)
                    AND u.updated_at > DATE_SUB(NOW(), INTERVAL 10 DAY)
                    AND n.id IS NULL";
        } else {
            $sql = "SELECT u.id, u.name FROM users u
                    LEFT JOIN notification_queue n ON u.id = n.user_id AND n.template_slug = 'rehook_market_recap' AND n.created_at > datetime('now', '-7 days')
                    WHERE u.updated_at < datetime('now', '-3 days')
                    AND u.updated_at > datetime('now', '-10 days')
                    AND n.id IS NULL";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll();

        foreach ($users as $user) {
            $this->pushService->notify($user['id'], 'rehook_market_recap', [
                'name' => $user['name'],
                'url' => get_site_url() . "/dashboard"
            ]);
        }
    }
}
