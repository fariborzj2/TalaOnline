<?php
/**
 * Advanced Comment System Logic
 */

require_once __DIR__ . '/mail.php';

class Comments {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetch comments for a specific target with pagination
     */
    /**
     * Fetch a single comment with full data
     */
    public function getComment($id, $user_id = null) {
        if (!$this->pdo) return null;

        $sql = "SELECT c.*, u.name as user_name, u.avatar as user_avatar, u.username, u.level as user_level, u.role as user_role,
                ru.username as reply_to_username, ru.name as reply_to_name,
                rc.content as reply_to_content,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'like') as likes,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'dislike') as dislikes,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'heart') as hearts,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'fire') as fires
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users ru ON c.reply_to_user_id = ru.id
                LEFT JOIN comments rc ON c.reply_to_id = rc.id
                WHERE c.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $c = $stmt->fetch();

        if (!$c) return null;

        $user_reaction = null;
        if ($user_id) {
            $stmt = $this->pdo->prepare("SELECT reaction_type FROM comment_reactions WHERE user_id = ? AND comment_id = ?");
            $stmt->execute([$user_id, $id]);
            $user_reaction = $stmt->fetchColumn();
        }

        $c['user_reaction'] = $user_reaction;
        $c['replies'] = [];
        $c['content_html'] = $this->parseMentions($c['content']);
        $c['created_at_fa'] = jalali_date($c['created_at']);
        $c['can_edit'] = ($user_id && $c['user_id'] == $user_id && (time() - strtotime($c['created_at'])) < 300);

        return $c;
    }

    public function getComments($target_id, $target_type, $user_id = null, $page = 1, $per_page = 20) {
        if (!$this->pdo) return ['comments' => [], 'total_pages' => 0, 'total_count' => 0];

        // Only count top-level comments for pagination
        $count_sql = "SELECT COUNT(*) FROM comments WHERE target_id = ? AND target_type = ? AND status = 'approved' AND parent_id IS NULL";
        $stmt = $this->pdo->prepare($count_sql);
        $stmt->execute([$target_id, $target_type]);
        $total_top_level = $stmt->fetchColumn();
        $total_pages = ceil($total_top_level / $per_page);

        $offset = ($page - 1) * $per_page;

        // 1. Get top-level comments for the current page
        $sql = "SELECT c.*, u.name as user_name, u.avatar as user_avatar, u.username, u.level as user_level, u.role as user_role,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'like') as likes,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'dislike') as dislikes,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'heart') as hearts,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'fire') as fires
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.target_id = ? AND c.target_type = ? AND c.status = 'approved' AND c.parent_id IS NULL
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, (string)$target_id);
        $stmt->bindValue(2, (string)$target_type);
        $stmt->bindValue(3, (int)$per_page, PDO::PARAM_INT);
        $stmt->bindValue(4, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $top_level_comments = $stmt->fetchAll();

        if (empty($top_level_comments)) {
            return ['comments' => [], 'total_pages' => $total_pages, 'total_count' => $total_top_level];
        }

        // 2. Fetch first 3 replies for each top-level comment
        $user_reactions = [];
        if ($user_id) {
            $stmt = $this->pdo->prepare("SELECT comment_id, reaction_type FROM comment_reactions WHERE user_id = ?");
            $stmt->execute([$user_id]);
            while ($row = $stmt->fetch()) {
                $user_reactions[$row['comment_id']] = $row['reaction_type'];
            }
        }

        foreach ($top_level_comments as &$c) {
            $c['user_reaction'] = $user_reactions[$c['id']] ?? null;
            $c['content_html'] = $this->parseMentions($c['content']);
            $c['created_at_fa'] = jalali_date($c['created_at']);
            $c['can_edit'] = ($user_id && $c['user_id'] == $user_id && (time() - strtotime($c['created_at'])) < 300);

            // Fetch total reply count
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM comments WHERE parent_id = ? AND status = 'approved'");
            $stmt->execute([$c['id']]);
            $c['total_replies'] = (int)$stmt->fetchColumn();

            // Fetch first 3 replies
            $c['replies'] = $this->getReplies($c['id'], 0, 3, $user_id);
        }

        return [
            'comments' => $top_level_comments,
            'total_pages' => $total_pages,
            'total_count' => $total_top_level,
            'current_page' => $page
        ];
    }

    /**
     * Fetch replies for a specific comment
     */
    public function getReplies($parent_id, $offset = 0, $limit = 10, $user_id = null) {
        if (!$this->pdo) return [];

        $sql = "SELECT c.*, u.name as user_name, u.avatar as user_avatar, u.username, u.level as user_level, u.role as user_role,
                ru.username as reply_to_username, ru.name as reply_to_name,
                rc.content as reply_to_content,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'like') as likes,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'dislike') as dislikes,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'heart') as hearts,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'fire') as fires
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users ru ON c.reply_to_user_id = ru.id
                LEFT JOIN comments rc ON c.reply_to_id = rc.id
                WHERE c.parent_id = ? AND c.status = 'approved'
                ORDER BY c.likes_count DESC, c.created_at ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $parent_id);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $replies = $stmt->fetchAll();

        $user_reactions = [];
        if ($user_id) {
            $stmt = $this->pdo->prepare("SELECT comment_id, reaction_type FROM comment_reactions WHERE user_id = ?");
            $stmt->execute([$user_id]);
            while ($row = $stmt->fetch()) {
                $user_reactions[$row['comment_id']] = $row['reaction_type'];
            }
        }

        foreach ($replies as &$r) {
            $r['user_reaction'] = $user_reactions[$r['id']] ?? null;
            $r['content_html'] = $this->parseMentions($r['content']);
            $r['created_at_fa'] = jalali_date($r['created_at']);
            $r['can_edit'] = ($user_id && $r['user_id'] == $user_id && (time() - strtotime($r['created_at'])) < 300);
        }

        return $replies;
    }

    /**
     * Fetch all comments by a specific user (limited to recent 50)
     */
    public function getUserComments($user_id, $viewer_id = null, $limit = 50) {
        if (!$this->pdo) return [];

        $sql = "SELECT c.*, u.name as user_name, u.avatar as user_avatar, u.username, u.level as user_level, u.role as user_role,
                ru.username as reply_to_username, ru.name as reply_to_name,
                rc.content as reply_to_content,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'like') as likes,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'dislike') as dislikes,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'heart') as hearts,
                (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = c.id AND reaction_type = 'fire') as fires
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users ru ON c.reply_to_user_id = ru.id
                LEFT JOIN comments rc ON c.reply_to_id = rc.id
                WHERE c.user_id = :user_id AND c.status = 'approved'
                ORDER BY c.created_at DESC
                LIMIT :limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        $comments = $stmt->fetchAll();

        $user_reactions = [];
        if ($viewer_id) {
            $stmt = $this->pdo->prepare("SELECT comment_id, reaction_type FROM comment_reactions WHERE user_id = ?");
            $stmt->execute([$viewer_id]);
            while ($row = $stmt->fetch()) {
                $user_reactions[$row['comment_id']] = $row['reaction_type'];
            }
        }

        foreach ($comments as &$c) {
            $c['user_reaction'] = $user_reactions[$c['id']] ?? null;
            $c['replies'] = [];
            $c['content_html'] = $this->parseMentions($c['content']);
            $c['created_at_fa'] = jalali_date($c['created_at']);
            $c['can_edit'] = ($viewer_id && $c['user_id'] == $viewer_id && (time() - strtotime($c['created_at'])) < 300);
            $c['target_info'] = $this->getTargetInfo($c['target_id'], $c['target_type']);
        }

        return $comments;
    }

    /**
     * Get target info (title and URL) for a comment
     */
    public function getTargetInfo($target_id, $target_type) {
        if (!$this->pdo) return null;

        try {
            if ($target_type === 'post') {
                $stmt = $this->pdo->prepare("SELECT p.title, p.slug, c.slug as cat_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.id = ?");
                $stmt->execute([$target_id]);
                $res = $stmt->fetch();
                if ($res) return ['title' => $res['title'], 'url' => '/blog/' . ($res['cat_slug'] ?: 'uncategorized') . '/' . $res['slug']];
            } elseif ($target_type === 'item') {
                $stmt = $this->pdo->prepare("SELECT name, symbol, category FROM items WHERE symbol = ?");
                $stmt->execute([$target_id]);
                $res = $stmt->fetch();
                if ($res) return ['title' => $res['name'], 'url' => '/' . $res['category'] . '/' . $res['symbol']];
            } elseif ($target_type === 'category') {
                $stmt = $this->pdo->prepare("SELECT name, slug FROM categories WHERE slug = ?");
                $stmt->execute([$target_id]);
                $res = $stmt->fetch();
                if ($res) return ['title' => $res['name'], 'url' => '/' . $res['slug']];
            }
        } catch (Exception $e) {}

        return null;
    }

    /**
     * Add a new comment
     */
    public function addComment($user_id, $target_id, $target_type, $content, $parent_id = null, $reply_to_user_id = null, $reply_to_id = null) {
        if (!$this->pdo) return false;

        // Validation: Enforce depth limit 1 at application layer
        if ($parent_id) {
            $stmt = $this->pdo->prepare("SELECT parent_id, user_id FROM comments WHERE id = ?");
            $stmt->execute([$parent_id]);
            $parent = $stmt->fetch();

            if (!$parent) return false; // Invalid parent

            if ($parent['parent_id'] !== null) {
                // The intended parent is already a reply.
                // We MUST re-point to the actual top-level parent.
                $reply_to_id = $parent_id;
                $reply_to_user_id = $parent['user_id'];
                $parent_id = $parent['parent_id'];
            } else {
                // It's a direct reply to a main comment
                $reply_to_id = $parent_id;
            }
        }

        $stmt = $this->pdo->prepare("INSERT INTO comments (user_id, target_id, target_type, content, parent_id, reply_to_id, reply_to_user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([$user_id, $target_id, $target_type, $content, $parent_id, $reply_to_id, $reply_to_user_id]);

        if ($success) {
            $comment_id = $this->pdo->lastInsertId();

            // Reward user
            $this->rewardUser($user_id, 10); // 10 points for comment

            // Check for mentions
            $this->handleMentions($content, $comment_id, $user_id);

            // Notify parent author
            if ($parent_id) {
                $this->notifyReply($parent_id, $comment_id);
            }
            // Also notify if it's a direct reply to a user in a thread
            if ($reply_to_user_id && $reply_to_user_id != $user_id) {
                $this->sendNotificationEmail(['id' => $reply_to_user_id], 'reply', $comment_id, $user_id);
            }

            return $comment_id;
        }
        return false;
    }

    /**
     * Handle emoji reactions
     */
    public function react($user_id, $comment_id, $reaction_type) {
        if (!$this->pdo) return false;

        // 1. Fetch current reaction to handle point reversal
        $stmt = $this->pdo->prepare("SELECT reaction_type FROM comment_reactions WHERE user_id = ? AND comment_id = ?");
        $stmt->execute([$user_id, $comment_id]);
        $old_reaction = $stmt->fetchColumn();

        // 2. Fetch author to update their points
        $stmt = $this->pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $author_id = $stmt->fetchColumn();

        // Remove existing reaction if any
        $stmt = $this->pdo->prepare("DELETE FROM comment_reactions WHERE user_id = ? AND comment_id = ?");
        $stmt->execute([$user_id, $comment_id]);

        // Deduct points from old reaction if author is different
        if ($old_reaction && $author_id && $author_id != $user_id) {
            $old_points = in_array($old_reaction, ['like', 'heart', 'fire']) ? 5 : -2;
            $this->rewardUser($author_id, -$old_points);
        }

        if ($reaction_type) {
            $stmt = $this->pdo->prepare("INSERT INTO comment_reactions (user_id, comment_id, reaction_type) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $comment_id, $reaction_type]);

            // Reward author for new reaction
            if ($author_id && $author_id != $user_id) {
                $new_points = in_array($reaction_type, ['like', 'heart', 'fire']) ? 5 : -2;
                $this->rewardUser($author_id, $new_points);
            }
        }

        // Sync likes_count for sorting performance
        $stmt = $this->pdo->prepare("UPDATE comments SET likes_count = (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = ? AND reaction_type = 'like') WHERE id = ?");
        $stmt->execute([$comment_id, $comment_id]);

        return true;
    }

    /**
     * Report a comment
     */
    public function report($user_id, $comment_id, $reason) {
        if (!$this->pdo) return false;
        $stmt = $this->pdo->prepare("INSERT INTO comment_reports (user_id, comment_id, reason) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $comment_id, $reason]);
    }

    /**
     * Update/Edit a comment (within 5 minutes)
     */
    public function updateComment($user_id, $comment_id, $content) {
        if (!$this->pdo) return false;

        $stmt = $this->pdo->prepare("SELECT created_at FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $user_id]);
        $created_at = $stmt->fetchColumn();

        if ($created_at && (time() - strtotime($created_at)) < 300) {
            $stmt = $this->pdo->prepare("UPDATE comments SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$content, $comment_id]);
        }
        return false;
    }

    /**
     * Parse @mentions in content and convert to links
     */
    public function parseMentions($content) {
        return preg_replace('/@([a-zA-Z0-9_]+)/', '<a href="/profile/$1" class="mention">@$1</a>', htmlspecialchars($content));
    }

    /**
     * Reward user with points and update level
     */
    private function rewardUser($user_id, $points) {
        $stmt = $this->pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $stmt->execute([$points, $user_id]);

        // Recalculate level
        $stmt = $this->pdo->prepare("SELECT points, created_at FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user) {
            $days_member = floor((time() - strtotime($user['created_at'])) / 86400);
            $total_points = $user['points'] + $days_member;

            $level = 1;
            if ($total_points >= 5000) $level = 5;
            elseif ($total_points >= 1500) $level = 4;
            elseif ($total_points >= 500) $level = 3;
            elseif ($total_points >= 100) $level = 2;

            $stmt = $this->pdo->prepare("UPDATE users SET level = ? WHERE id = ?");
            $stmt->execute([$level, $user_id]);
        }
    }

    /**
     * Handle @mentions and queue notifications
     */
    private function handleMentions($content, $comment_id, $sender_id) {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
        $usernames = array_unique($matches[1]);

        foreach ($usernames as $username) {
            $stmt = $this->pdo->prepare("SELECT id, email, name FROM users WHERE LOWER(username) = LOWER(?)");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && $user['id'] != $sender_id) {
                $this->sendNotificationEmail($user, 'mention', $comment_id, $sender_id);
            }
        }
    }

    /**
     * Notify author of parent comment about a reply
     */
    private function notifyReply($parent_id, $comment_id) {
        $stmt = $this->pdo->prepare("SELECT u.id, u.email, u.name, c.user_id as sender_id
                                     FROM comments pc
                                     JOIN users u ON pc.user_id = u.id
                                     JOIN comments c ON c.id = ?
                                     WHERE pc.id = ?");
        $stmt->execute([$comment_id, $parent_id]);
        $user = $stmt->fetch();

        if ($user && $user['id'] != $user['sender_id']) {
            $this->sendNotificationEmail($user, 'reply', $comment_id, $user['sender_id']);
        }
    }

    /**
     * Send notification email
     */
    private function sendNotificationEmail($user, $type, $comment_id, $sender_id) {
        // Fetch full user data if only ID is provided
        if (!isset($user['email'])) {
            $stmt = $this->pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
            if (!$user) return;
        }

        $stmt = $this->pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$sender_id]);
        $sender_name = $stmt->fetchColumn();

        $subject = ($type === 'mention') ? "از شما در یک نظر نام برده شد" : "به نظر شما پاسخ داده شد";
        $message = ($type === 'mention')
            ? "سلام {$user['name']} عزیز،<br><br>کاربر <strong>{$sender_name}</strong> در یک نظر از شما نام برده است.<br><br>برای مشاهده نظر می‌توانید به سایت مراجعه کنید."
            : "سلام {$user['name']} عزیز،<br><br>کاربر <strong>{$sender_name}</strong> به نظر شما پاسخ داده است.<br><br>برای مشاهده پاسخ می‌توانید به سایت مراجعه کنید.";

        Mail::queueRaw($user['email'], $subject, Mail::getProfessionalLayout($message));
    }
}
