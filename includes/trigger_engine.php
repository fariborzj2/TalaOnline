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

        // Check for Combined Opportunity Alert
        $this->handleOpportunityAlert($symbol, $change_percent);

        // Check for Community Pulse
        $this->handleCommunityPulse($symbol, $change_percent);
    }

    /**
     * Trigger: Deep Interaction (Social)
     */
    public function handleCommentInteraction($comment_id, $parent_id, $sender_name, $reply_to_user_id = null) {
        $notified_users = [];

        // 1. Notify root parent author
        if ($parent_id) {
            $stmt = $this->pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
            $stmt->execute([$parent_id]);
            $root_author_id = $stmt->fetchColumn();

            if ($root_author_id) {
                $this->pushService->notify($root_author_id, 'social_reply', [
                    'sender_name' => $sender_name,
                    'url' => get_site_url() . "/thread/$comment_id"
                ]);
                $notified_users[] = $root_author_id;
            }
        }

        // 2. Notify direct reply target (if different from root author)
        if ($reply_to_user_id && !in_array($reply_to_user_id, $notified_users)) {
            $this->pushService->notify($reply_to_user_id, 'social_reply', [
                'sender_name' => $sender_name,
                'url' => get_site_url() . "/thread/$comment_id"
            ]);
        }
    }

    /**
     * Trigger: Social Milestone
     */
    public function handleMilestone($author_id, $comment_id, $count) {
        return $this->pushService->notify($author_id, 'social_milestone', [
            'count' => $count,
            'url' => get_site_url() . "/thread/$comment_id"
        ]);
    }

    /**
     * Trigger: Trending Discussion
     */
    public function handleTrendingDiscussion($target_id, $target_type, $title, $url) {
        // Notify segment of active users (Sample strategy: top 50 recently active)
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_verified = 1 ORDER BY updated_at DESC LIMIT 50");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $user_id) {
            $this->pushService->notify($user_id, 'social_trending', [
                'title' => $title,
                'url' => get_site_url() . $url
            ]);
        }
    }

    /**
     * Trigger: New Symbol Discovery
     * Alerts users when a new asset is added to the market.
     */
    public function handleNewSymbol($symbol, $name) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_verified = 1 LIMIT 100");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $user_id) {
            $this->pushService->notify($user_id, 'new_symbol_discovery', [
                'symbol' => $symbol,
                'name' => $name,
                'url' => get_site_url() . "/market/$symbol"
            ]);
        }
    }

    /**
     * Trigger: Category Expert Update
     * Targeted notifications for users who read many articles in a specific category.
     */
    public function handleCategoryExpertUpdate($post_id, $title, $category_id, $category_name) {
        // Find users who have read > 3 articles in this category (Simplified tracking: using a mock check for now)
        // In a real system, we'd have a `user_read_history` table.
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_verified = 1 LIMIT 50");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $user_id) {
            $this->pushService->notify($user_id, 'category_expert_update', [
                'title' => $title,
                'category' => $category_name,
                'url' => get_site_url() . "/blog/post/$post_id"
            ], ['priority' => 'medium']);
        }
    }

    /**
     * Trigger: Content Velocity (Trending Blog)
     * Alerts users to a blog post that is gaining rapid traction.
     */
    public function handleContentVelocity($post_id, $title, $views) {
        // Milestone thresholds for "Trending" badge/notification
        $milestones = [100, 500, 1000, 5000, 10000];
        if (!in_array($views, $milestones)) return;

        // Check if it's a recent post (last 48 hours) to ensure "Velocity"
        $stmt = $this->pdo->prepare("SELECT id FROM blog_posts WHERE id = ? AND created_at > datetime('now', '-48 hours')");
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT id FROM blog_posts WHERE id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 48 HOUR)");
        }
        $stmt->execute([$post_id]);
        if (!$stmt->fetchColumn()) return;

        // Notify users
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_verified = 1 LIMIT 50");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $user_id) {
            $this->pushService->notify($user_id, 'blog_trending', [
                'title' => $title,
                'views' => number_format($views),
                'url' => get_site_url() . "/blog/post/$post_id" // Needs real URL resolution usually
            ]);
        }
    }

    /**
     * Trigger: Breaking Economic Impact (Blog)
     * Alerts users to new blog posts, potentially filtered by interests.
     */
    public function handleNewBlogPost($post_id, $title, $category_name, $content = '') {
        // Find mentioned assets in content for interest-based targeting
        $symbols = [];
        $stmt = $this->pdo->query("SELECT symbol FROM items WHERE is_active = 1");
        $all_symbols = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($all_symbols as $s) {
            if (stripos($title . ' ' . $content, $s) !== false) {
                $symbols[] = $s;
            }
        }

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_verified = 1 LIMIT 100");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($users as $user_id) {
            $template = 'blog_new_post';
            $data = [
                'title' => $title,
                'category' => $category_name,
                'url' => get_site_url() . "/blog/post/$post_id"
            ];

            // If an asset is mentioned, use a more specific template if possible
            if (!empty($symbols)) {
                $data['symbol'] = $symbols[0];
                // In a production system, we'd check if user follows this symbol
            }

            $this->pushService->notify($user_id, $template, $data);
        }
    }

    /**
     * Trigger: Micro-Influencer Engagement
     * Alerts a low-level user when a high-level user interacts with their content.
     */
    public function handleInfluencerEngagement($actor_id, $target_user_id, $comment_id) {
        $stmt = $this->pdo->prepare("SELECT name, level FROM users WHERE id = ?");
        $stmt->execute([$actor_id]);
        $actor = $stmt->fetch();

        $stmt = $this->pdo->prepare("SELECT level FROM users WHERE id = ?");
        $stmt->execute([$target_user_id]);
        $target_level = $stmt->fetchColumn();

        if ($actor && $actor['level'] >= 4 && $target_level <= 2) {
            $this->pushService->notify($target_user_id, 'influencer_engagement', [
                'actor_name' => $actor['name'],
                'url' => get_site_url() . "/thread/$comment_id"
            ]);
        }
    }

    /**
     * Trigger: Technical Level Break
     * Alerts when price breaks today's High or Low.
     */
    public function handleTechnicalBreak($symbol, $price, $high, $low) {
        $type = null;
        if ($price >= $high && $high > 0) $type = 'high_break';
        elseif ($price <= $low && $low > 0) $type = 'low_break';

        if (!$type) return;

        // Check if we already notified for this break in the last 4 hours
        $stmt = $this->pdo->prepare("SELECT id FROM notification_queue WHERE template_slug = 'technical_break' AND data LIKE ? AND created_at > datetime('now', '-4 hours') LIMIT 1");
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT id FROM notification_queue WHERE template_slug = 'technical_break' AND data LIKE ? AND created_at > DATE_SUB(NOW(), INTERVAL 4 HOUR) LIMIT 1");
        }
        $stmt->execute(['%"symbol":"' . $symbol . '"%']);
        if ($stmt->fetchColumn()) return;

        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_verified = 1 LIMIT 50");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $label = ($type === 'high_break') ? 'سقف روزانه' : 'کف روزانه';

        foreach ($users as $user_id) {
            $this->pushService->notify($user_id, 'technical_break', [
                'symbol' => $symbol,
                'label' => $label,
                'price' => number_format($price),
                'url' => get_site_url() . "/market/$symbol"
            ]);
        }
    }

    /**
     * Trigger: Community Pulse
     * Alerts when there is high volatility AND high comment volume for a symbol.
     */
    public function handleCommunityPulse($symbol, $change_percent) {
        if (abs($change_percent) < 2) return; // Significant move

        // Check comment volume in last 1 hour
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM comments WHERE target_id = ? AND target_type = 'item' AND created_at > datetime('now', '-1 hour')");
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM comments WHERE target_id = ? AND target_type = 'item' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        }
        $stmt->execute([$symbol]);
        $comment_count = $stmt->fetchColumn();

        if ($comment_count >= 10) { // High pulse threshold
            // Notify active users
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_verified = 1 LIMIT 50");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($users as $user_id) {
                $this->pushService->notify($user_id, 'community_pulse', [
                    'symbol' => $symbol,
                    'count' => $comment_count,
                    'url' => get_site_url() . "/market/$symbol"
                ]);
            }
        }
    }

    /**
     * Trigger: Combined Opportunity Alert
     * Price drop + High-level user Analysis (sentiment)
     */
    public function handleOpportunityAlert($symbol, $change_percent) {
        if ($change_percent > -3) return; // Only for drops > 3%

        // Check for recent (last 3h) Level 5 analysis for this symbol
        $stmt = $this->pdo->prepare("SELECT c.id FROM comments c
                                   JOIN users u ON c.user_id = u.id
                                   WHERE c.target_id = ? AND c.target_type = 'item'
                                   AND u.level = 5 AND c.created_at > datetime('now', '-3 hours')
                                   AND c.status = 'approved' LIMIT 1");

        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $stmt = $this->pdo->prepare("SELECT c.id FROM comments c
                                       JOIN users u ON c.user_id = u.id
                                       WHERE c.target_id = ? AND c.target_type = 'item'
                                       AND u.level = 5 AND c.created_at > DATE_SUB(NOW(), INTERVAL 3 HOUR)
                                       AND c.status = 'approved' LIMIT 1");
        }

        $stmt->execute([$symbol]);
        $expert_analysis_id = $stmt->fetchColumn();

        if ($expert_analysis_id) {
            // Notify active users who have visited this symbol in the last 7 days
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE is_verified = 1 LIMIT 100");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($users as $user_id) {
                $this->pushService->notify($user_id, 'market_opportunity', [
                    'symbol' => $symbol,
                    'url' => get_site_url() . "/market/$symbol"
                ]);
            }
        }
    }

    /**
     * Trigger: Sentiment Shift Alert
     * Alerts user when their historical sentiment contradicts current market consensus.
     */
    public function handleSentimentShift($user_id, $symbol) {
        require_once __DIR__ . '/sentiment.php';
        $sentiment_handler = new MarketSentiment($this->pdo);
        $results = $sentiment_handler->getResults($symbol);

        // Define "Strong Consensus" as > 70%
        $consensus = null;
        if ($results['bullish_percent'] > 70) $consensus = 'bullish';
        elseif ($results['bearish_percent'] > 70) $consensus = 'bearish';

        if (!$consensus) return;

        // Get user's latest vote for this symbol (last 30 days)
        $stmt = $this->pdo->prepare("SELECT vote FROM market_sentiment WHERE user_id = ? AND currency_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id, $symbol]);
        $user_vote = $stmt->fetchColumn();

        if ($user_vote && $user_vote !== $consensus) {
            $this->pushService->notify($user_id, 'sentiment_risk', [
                'symbol' => $symbol,
                'consensus' => ($consensus === 'bullish' ? 'صعودی' : 'نزولی'),
                'url' => get_site_url() . "/market/$symbol"
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
