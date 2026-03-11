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
        $stmt = $this->pdo->prepare("SELECT id, name FROM users WHERE updated_at < datetime('now', '-3 days') AND updated_at > datetime('now', '-4 days')");
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT id, name FROM users WHERE updated_at < DATE_SUB(NOW(), INTERVAL 3 DAY) AND updated_at > DATE_SUB(NOW(), INTERVAL 4 DAY)");
        }
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
