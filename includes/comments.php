<?php
/**
 * Advanced Comment System Logic
 */

require_once __DIR__ . '/mail.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/push_service.php';
require_once __DIR__ . '/trigger_engine.php';

class Comments {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetch a single comment with full data
     */
    public function getComment($id, $user_id = null) {
        if (!$this->pdo) return null;

        $sql = "SELECT c.id, c.user_id, c.target_id, c.target_type, c.content, c.parent_id, c.created_at, c.reply_to_user_id, c.guest_name, c.type, c.image_url, c.status,
                u.name as user_name, u.avatar as user_avatar, u.username as user_username, u.level as user_level, u.role as user_role,
                ru.username as reply_to_username,
                rc.content as reply_to_content
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users ru ON c.reply_to_user_id = ru.id
                LEFT JOIN comments rc ON c.reply_to_id = rc.id
                WHERE c.id = ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $c = $stmt->fetch();

        if (!$c) return null;

        $stats = $this->loadReactionStats([$c['id']]);
        $c['likes'] = $stats[$c['id']]['likes'] ?? 0;
        $c['dislikes'] = $stats[$c['id']]['dislikes'] ?? 0;
        $c['hearts'] = $stats[$c['id']]['hearts'] ?? 0;
        $c['fires'] = $stats[$c['id']]['fires'] ?? 0;

        $user_reactions = $this->loadUserReactions($user_id, [$c['id']]);
        $c['user_reaction'] = $user_reactions[$c['id']] ?? null;
        $c['replies'] = [];

        // Fetch mentioned users for this single comment
        $userMap = $this->loadMentionedUsers([$c]);
        $c['content_html'] = $this->parseMentions($c['content'], $userMap);
        $c['mentioned_users'] = $this->extractMentionedUsers($c['content'], $userMap);
        $c['content_edit'] = $this->convertPlaceholdersToMentions($c['content'], $userMap, true);

        $c['created_at_fa'] = jalali_date($c['created_at']);
        $edit_limit = (int)get_setting('comments_edit_time_limit', '300');
        $c['can_edit'] = ($user_id && $c['user_id'] == $user_id && (time() - strtotime($c['created_at'])) < $edit_limit);

        return $c;
    }

    public function getComments($target_id, $target_type, $user_id = null, $page = 1, $per_page = null, $filter_type = 'all', $sort = 'newest') {
        if (!$this->pdo) return ['comments' => [], 'total_pages' => 0, 'total_count' => 0];

        if ($per_page === null) {
            if ($target_type === 'user_profile') {
                $per_page = 10;
            } else {
                $per_page = (int)get_setting('comments_per_page', '20');
            }
        }

        if ($target_type === 'user_profile') {
            $where = "c.user_id = ? AND c.status = 'approved'";
            $params = [(int)$target_id];
        } else {
            $where = "c.target_id = ? AND c.target_type = ? AND c.status = 'approved' AND c.parent_id IS NULL";
            $params = [(string)$target_id, (string)$target_type];
        }

        if ($filter_type !== 'all') {
            $where .= " AND c.type = ?";
            $params[] = $filter_type;
        }

        // Only count top-level comments for pagination
        $count_sql = "SELECT COUNT(*) FROM comments c WHERE $where";
        $stmt = $this->pdo->prepare($count_sql);
        $stmt->execute($params);
        $total_top_level = $stmt->fetchColumn();
        $total_pages = ceil($total_top_level / $per_page);

        $offset = ($page - 1) * $per_page;

        $order_by = "c.created_at DESC";
        if ($sort === 'popular') {
            $order_by = "c.likes_count DESC, c.created_at DESC";
        } elseif ($sort === 'most_replies') {
            $order_by = "(SELECT COUNT(*) FROM comments WHERE parent_id = c.id AND status = 'approved') DESC, c.created_at DESC";
        }

        // 1. Get top-level comments for the current page
        $sql = "SELECT c.id, c.user_id, c.target_id, c.target_type, c.content, c.parent_id, c.created_at, c.reply_to_user_id, c.guest_name, c.type, c.image_url, c.status,
                u.name as user_name, u.avatar as user_avatar, u.username as user_username, u.level as user_level, u.role as user_role,
                ru.username as reply_to_username,
                rc.content as reply_to_content
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users ru ON c.reply_to_user_id = ru.id
                LEFT JOIN comments rc ON c.reply_to_id = rc.id
                WHERE $where
                ORDER BY $order_by
                LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        $idx = 1;
        foreach ($params as $p) {
            $stmt->bindValue($idx++, $p);
        }
        $stmt->bindValue($idx++, (int)$per_page, PDO::PARAM_INT);
        $stmt->bindValue($idx++, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $top_level_comments = $stmt->fetchAll();

        if (empty($top_level_comments)) {
            return ['comments' => [], 'total_pages' => $total_pages, 'total_count' => $total_top_level];
        }

        $top_level_ids = array_column($top_level_comments, 'id');
        $id_placeholders = implode(',', array_fill(0, count($top_level_ids), '?'));

        // 2. Fetch first 3 replies for each top-level comment in bulk
        $replies_sql = "SELECT * FROM (
            SELECT c.id, c.user_id, c.target_id, c.target_type, c.content, c.parent_id, c.created_at, c.reply_to_user_id, c.guest_name, c.type, c.image_url, c.status,
                u.name as user_name, u.avatar as user_avatar, u.username as user_username, u.level as user_level, u.role as user_role,
                ru.username as reply_to_username,
                rc.content as reply_to_content,
                ROW_NUMBER() OVER (PARTITION BY c.parent_id ORDER BY c.likes_count DESC, c.created_at ASC) as rn
                FROM comments c
                LEFT JOIN users u ON c.user_id = u.id
                LEFT JOIN users ru ON c.reply_to_user_id = ru.id
                LEFT JOIN comments rc ON c.reply_to_id = rc.id
                WHERE c.parent_id IN ($id_placeholders) AND c.status = 'approved'
        ) as t WHERE rn <= 3";

        $stmt = $this->pdo->prepare($replies_sql);
        $stmt->execute($top_level_ids);
        $all_replies = $stmt->fetchAll();

        // 3. Bulk fetch stats, user reactions and reply counts
        $all_visible_ids = array_merge($top_level_ids, array_column($all_replies, 'id'));
        $stats = $this->loadReactionStats($all_visible_ids);
        $user_reactions = $this->loadUserReactions($user_id, $all_visible_ids);
        $reply_counts = $this->loadReplyCounts($top_level_ids);

        $replies_by_parent = [];
        $edit_limit = (int)get_setting('comments_edit_time_limit', '300');

        foreach ($all_replies as $r) {
            $r['likes'] = $stats[$r['id']]['likes'] ?? 0;
            $r['dislikes'] = $stats[$r['id']]['dislikes'] ?? 0;
            $r['hearts'] = $stats[$r['id']]['hearts'] ?? 0;
            $r['fires'] = $stats[$r['id']]['fires'] ?? 0;
            $r['user_reaction'] = $user_reactions[$r['id']] ?? null;
            $r['created_at_fa'] = jalali_date($r['created_at']);
            $r['can_edit'] = ($user_id && $r['user_id'] == $user_id && (time() - strtotime($r['created_at'])) < $edit_limit);
            $replies_by_parent[$r['parent_id']][] = $r;
        }

        foreach ($top_level_comments as &$c) {
            $c['likes'] = $stats[$c['id']]['likes'] ?? 0;
            $c['dislikes'] = $stats[$c['id']]['dislikes'] ?? 0;
            $c['hearts'] = $stats[$c['id']]['hearts'] ?? 0;
            $c['fires'] = $stats[$c['id']]['fires'] ?? 0;
            $c['user_reaction'] = $user_reactions[$c['id']] ?? null;
            $c['created_at_fa'] = jalali_date($c['created_at']);
            $c['can_edit'] = ($user_id && $c['user_id'] == $user_id && (time() - strtotime($c['created_at'])) < $edit_limit);
            $c['total_replies'] = $reply_counts[$c['id']] ?? 0;
            $c['replies'] = $replies_by_parent[$c['id']] ?? [];
        }

        // 4. Bulk fetch target info if in user profile
        if ($target_type === 'user_profile') {
            $this->loadTargetInfoForComments($top_level_comments);
        }

        // 4. Bulk parse mentions
        $userMap = $this->loadMentionedUsers($top_level_comments);
        foreach ($top_level_comments as &$c) {
            $c['content_html'] = $this->parseMentions($c['content'], $userMap);
            $c['mentioned_users'] = $this->extractMentionedUsers($c['content'], $userMap);
            $c['content_edit'] = $this->convertPlaceholdersToMentions($c['content'], $userMap, true);
            foreach ($c['replies'] as &$r) {
                $r['content_html'] = $this->parseMentions($r['content'], $userMap);
                $r['mentioned_users'] = $this->extractMentionedUsers($r['content'], $userMap);
                $r['content_edit'] = $this->convertPlaceholdersToMentions($r['content'], $userMap, true);
            }
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
    public function getReplies($parent_id, $offset = 0, $limit = 10, $user_id = null, $parse = true) {
        if (!$this->pdo) return [];

        $sql = "SELECT c.id, c.user_id, c.target_id, c.target_type, c.content, c.parent_id, c.created_at, c.reply_to_user_id, c.guest_name, c.type, c.image_url, c.status,
                u.name as user_name, u.avatar as user_avatar, u.username as user_username, u.level as user_level, u.role as user_role,
                ru.username as reply_to_username,
                rc.content as reply_to_content
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

        if (empty($replies)) return [];

        $commentIds = array_column($replies, 'id');
        $stats = $this->loadReactionStats($commentIds);
        $user_reactions = $this->loadUserReactions($user_id, $commentIds);

        if ($parse) {
            // Bulk parse mentions for replies
            $userMap = $this->loadMentionedUsers($replies);
            foreach ($replies as &$r) {
                $r['content_html'] = $this->parseMentions($r['content'], $userMap);
                $r['mentioned_users'] = $this->extractMentionedUsers($r['content'], $userMap);
                $r['content_edit'] = $this->convertPlaceholdersToMentions($r['content'], $userMap, true);
            }
        }

        $edit_limit = (int)get_setting('comments_edit_time_limit', '300');
        foreach ($replies as &$r) {
            $r['likes'] = $stats[$r['id']]['likes'] ?? 0;
            $r['dislikes'] = $stats[$r['id']]['dislikes'] ?? 0;
            $r['hearts'] = $stats[$r['id']]['hearts'] ?? 0;
            $r['fires'] = $stats[$r['id']]['fires'] ?? 0;
            $r['user_reaction'] = $user_reactions[$r['id']] ?? null;
            $r['created_at_fa'] = jalali_date($r['created_at']);
            $r['can_edit'] = ($user_id && $r['user_id'] == $user_id && (time() - strtotime($r['created_at'])) < $edit_limit);
        }

        return $replies;
    }

    /**
     * Bulk load reply counts for a list of parent comment IDs
     */
    public function loadReplyCounts($commentIds) {
        if (empty($commentIds)) return [];
        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        $sql = "SELECT parent_id, COUNT(*) as total FROM comments WHERE parent_id IN ($placeholders) AND status = 'approved' GROUP BY parent_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($commentIds);
        $counts = [];
        while ($row = $stmt->fetch()) {
            $counts[$row['parent_id']] = (int)$row['total'];
        }
        return $counts;
    }

    /**
     * Bulk load target info for a list of comments
     */
    public function loadTargetInfoForComments(&$comments) {
        $targets = [];
        foreach ($comments as $c) {
            $targets[$c['target_type']][] = $c['target_id'];
        }

        $infoMap = [];

        foreach ($targets as $type => $ids) {
            $ids = array_values(array_unique($ids));
            if (empty($ids)) continue;
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            try {
                if ($type === 'post') {
                    $stmt = $this->pdo->prepare("SELECT p.id, p.title, p.slug, c.slug as cat_slug FROM blog_posts p LEFT JOIN blog_categories c ON p.category_id = c.id WHERE p.id IN ($placeholders)");
                    $stmt->execute($ids);
                    while ($res = $stmt->fetch()) {
                        $infoMap["post_{$res['id']}"] = ['title' => $res['title'], 'url' => '/blog/' . ($res['cat_slug'] ?: 'uncategorized') . '/' . $res['slug']];
                    }
                } elseif ($type === 'item') {
                    $stmt = $this->pdo->prepare("SELECT name, symbol, category FROM items WHERE symbol IN ($placeholders)");
                    $stmt->execute($ids);
                    while ($res = $stmt->fetch()) {
                        $infoMap["item_{$res['symbol']}"] = ['title' => $res['name'], 'url' => '/' . $res['category'] . '/' . $res['symbol']];
                    }
                } elseif ($type === 'category') {
                    $stmt = $this->pdo->prepare("SELECT name, slug FROM categories WHERE slug IN ($placeholders)");
                    $stmt->execute($ids);
                    while ($res = $stmt->fetch()) {
                        $infoMap["category_{$res['slug']}"] = ['title' => $res['name'], 'url' => '/' . $res['slug']];
                    }
                }
            } catch (Exception $e) {}
        }

        foreach ($comments as &$c) {
            $c['target_info'] = $infoMap["{$c['target_type']}_{$c['target_id']}"] ?? null;
        }
    }

    /**
     * Fetch all comments by a specific user (limited to recent 50)
     */
    public function getUserComments($user_id, $viewer_id = null, $limit = 50) {
        if (!$this->pdo) return [];

        $sql = "SELECT c.id, c.user_id, c.target_id, c.target_type, c.content, c.parent_id, c.created_at, c.reply_to_user_id, c.guest_name, c.type, c.image_url, c.status,
                u.name as user_name, u.avatar as user_avatar, u.username as user_username, u.level as user_level, u.role as user_role,
                ru.username as reply_to_username,
                rc.content as reply_to_content
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

        if (empty($comments)) return [];

        $commentIds = array_column($comments, 'id');
        $stats = $this->loadReactionStats($commentIds);
        $user_reactions = $this->loadUserReactions($viewer_id, $commentIds);

        // Bulk parse mentions for user profile feed
        $userMap = $this->loadMentionedUsers($comments);
        $edit_limit = (int)get_setting('comments_edit_time_limit', '300');
        foreach ($comments as &$c) {
            $c['likes'] = $stats[$c['id']]['likes'] ?? 0;
            $c['dislikes'] = $stats[$c['id']]['dislikes'] ?? 0;
            $c['hearts'] = $stats[$c['id']]['hearts'] ?? 0;
            $c['fires'] = $stats[$c['id']]['fires'] ?? 0;
            $c['user_reaction'] = $user_reactions[$c['id']] ?? null;
            $c['replies'] = [];
            $c['content_html'] = $this->parseMentions($c['content'], $userMap);
            $c['mentioned_users'] = $this->extractMentionedUsers($c['content'], $userMap);
            $c['content_edit'] = $this->convertPlaceholdersToMentions($c['content'], $userMap, true);
            $c['created_at_fa'] = jalali_date($c['created_at']);
            $c['can_edit'] = ($viewer_id && $c['user_id'] == $viewer_id && (time() - strtotime($c['created_at'])) < $edit_limit);
        }

        $this->loadTargetInfoForComments($comments);

        return $comments;
    }

    /**
     * Get target info by comment ID
     */
    public function getTargetInfoByCommentId($comment_id) {
        if (!$this->pdo) return null;
        $stmt = $this->pdo->prepare("SELECT target_id, target_type FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $c = $stmt->fetch();
        if ($c) {
            return $this->getTargetInfo($c['target_id'], $c['target_type']);
        }
        return null;
    }

    /**
     * Bulk resolve target info for multiple comment IDs
     */
    public function bulkGetTargetInfoByCommentIds($comment_ids) {
        if (empty($comment_ids) || !$this->pdo) return [];

        $comment_ids = array_values(array_unique($comment_ids));
        $placeholders = implode(',', array_fill(0, count($comment_ids), '?'));

        $sql = "SELECT id, target_id, target_type FROM comments WHERE id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($comment_ids);
        $comment_targets = $stmt->fetchAll();

        if (empty($comment_targets)) return [];

        // Prepare for batch target info lookup
        $temp_comments = [];
        foreach ($comment_targets as $ct) {
            $temp_comments[] = [
                'target_id' => $ct['target_id'],
                'target_type' => $ct['target_type']
            ];
        }

        $this->loadTargetInfoForComments($temp_comments);

        $resultMap = [];
        foreach ($comment_targets as $i => $ct) {
            if (isset($temp_comments[$i]['target_info'])) {
                $resultMap[$ct['id']] = $temp_comments[$i]['target_info'];
            }
        }

        return $resultMap;
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
     * Fetch a full thread (parent + all replies) for a specific comment ID
     */
    public function getThread($comment_id, $user_id = null) {
        if (!$this->pdo) return null;

        // 1. Find the root parent ID
        $stmt = $this->pdo->prepare("SELECT id, parent_id, target_id, target_type FROM comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        $curr = $stmt->fetch();
        if (!$curr) return null;

        $root_id = $curr['parent_id'] ?: $curr['id'];

        // 2. Fetch the root comment
        $parent = $this->getComment($root_id, $user_id);
        if (!$parent) return null;

        // 3. Fetch all replies to this parent
        $parent['replies'] = $this->getReplies($root_id, 0, 100, $user_id, true);
        $parent['target_info'] = $this->getTargetInfo($parent['target_id'], $parent['target_type']);

        return $parent;
    }

    /**
     * Add a new comment
     */
    public function addComment($user_id, $target_id, $target_type, $content, $parent_id = null, $reply_to_user_id = null, $reply_to_id = null, $mentions = [], $guest_name = null, $guest_email = null, $type = 'comment', $image_url = null) {
        if (!$this->pdo) return false;

        if ($target_type === 'post') {
            $type = 'comment';
            $image_url = null;
        }

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

        // Explicitly append mentions if provided
        if (!empty($mentions) && is_array($mentions)) {
            foreach ($mentions as $uid) {
                if (is_numeric($uid) && !str_contains($content, "[user:$uid]")) {
                    $content .= " [user:$uid]";
                }
            }
        }

        // Sanitize content
        $content = $this->sanitizeHTML($content);

        // Convert @mentions to [user:ID] placeholders before storing (legacy support for manual typing)
        $stored_content = $this->convertMentionsToPlaceholders($content);

        if ($user_id) {
            $default_status = get_setting('comments_default_status', 'approved');
        } else {
            $default_status = get_setting('comments_guest_default_status', 'pending');
        }

        $stmt = $this->pdo->prepare("INSERT INTO comments (user_id, target_id, target_type, content, parent_id, reply_to_id, reply_to_user_id, status, guest_name, guest_email, type, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([$user_id, $target_id, $target_type, $stored_content, $parent_id, $reply_to_id, $reply_to_user_id, $default_status, $guest_name, $guest_email, $type, $image_url]);

        if ($success) {
            $comment_id = $this->pdo->lastInsertId();

            if ($user_id) {
                // Reward user
                $this->rewardUser($user_id, 10); // 10 points for comment

                try {
                    // Performance optimization: Pre-fetch sender name once for all notifications
                    $stmt_sender = $this->pdo->prepare("SELECT name FROM users WHERE id = ?");
                    $stmt_sender->execute([$user_id]);
                    $sender_name = $stmt_sender->fetchColumn();

                    // Check for mentions (uses placeholders to notify)
                    $this->handleMentions($stored_content, $comment_id, $user_id, $sender_name);

                    // Notify parent author and reply target
                    if ($parent_id || ($reply_to_user_id && $reply_to_user_id != $user_id)) {
                        if ($parent_id) {
                            $this->notifyReply($parent_id, $comment_id, $sender_name);
                        }
                        if ($reply_to_user_id && $reply_to_user_id != $user_id) {
                            $this->sendNotificationEmail(['id' => $reply_to_user_id], 'reply', $comment_id, $sender_name);
                            $notif = new Notifications($this->pdo);
                            $notif->create($reply_to_user_id, $user_id, 'reply', $comment_id);
                        }

                        // Consolidated Push Notifications via Engine
                        $pushService = new PushService($this->pdo);
                        $triggerEngine = new TriggerEngine($this->pdo, $pushService);
                        $triggerEngine->handleCommentInteraction($comment_id, $parent_id, $sender_name, $reply_to_user_id);
                    }
                } catch (\Throwable $e) {
                    error_log("Comment Notification Error: " . $e->getMessage());
                }
            }

            try {
                // Trending Discussion Trigger (Velocity Check)
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM comments WHERE target_id = ? AND target_type = ? AND created_at > datetime('now', '-1 hour')");
                if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                    $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM comments WHERE target_id = ? AND target_type = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
                }
                $stmt->execute([$target_id, $target_type]);
                $recent_count = $stmt->fetchColumn();

                if ($recent_count == 20) { // Threshold for "Trending"
                    $pushService = new PushService($this->pdo);
                    $triggerEngine = new TriggerEngine($this->pdo, $pushService);
                    $info = $this->getTargetInfo($target_id, $target_type);

                    $triggerEngine->handleTrendingDiscussion(
                        $target_id,
                        $target_type,
                        $info['title'] ?? 'یک بحث داغ',
                        ($info['url'] ?? '/')
                    );
                }
            } catch (\Throwable $e) {
                error_log("Comment Trending Trigger Error: " . $e->getMessage());
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

                // Influencer Detection Trigger
                if (in_array($reaction_type, ['like', 'heart', 'fire'])) {
                    $pushService = new PushService($this->pdo);
                    $triggerEngine = new TriggerEngine($this->pdo, $pushService);
                    $triggerEngine->handleInfluencerEngagement($user_id, $author_id, $comment_id);
                }
            }
        }

        // Sync likes_count for sorting performance
        $stmt = $this->pdo->prepare("UPDATE comments SET likes_count = (SELECT COUNT(*) FROM comment_reactions WHERE comment_id = ? AND reaction_type = 'like') WHERE id = ?");
        $stmt->execute([$comment_id, $comment_id]);

        // Milestone Notification (Engagement Strategy)
        if ($reaction_type === 'like' && $author_id && $author_id != $user_id) {
            $stmt = $this->pdo->prepare("SELECT likes_count FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $count = $stmt->fetchColumn();

            $milestones = [10, 50, 100, 500, 1000];
            if (in_array($count, $milestones)) {
                $pushService = new PushService($this->pdo);
                $triggerEngine = new TriggerEngine($this->pdo, $pushService);
                $triggerEngine->handleMilestone($author_id, $comment_id, $count);

                $notif = new Notifications($this->pdo);
                $notif->create($author_id, 0, 'milestone', $comment_id);
            }
        }

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
     * Delete a comment (within limit)
     */
    public function deleteComment($user_id, $comment_id) {
        if (!$this->pdo) return false;

        $stmt = $this->pdo->prepare("SELECT created_at FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $user_id]);
        $created_at = $stmt->fetchColumn();

        $edit_limit = (int)get_setting('comments_edit_time_limit', '300');

        if ($created_at && (time() - strtotime($created_at)) < $edit_limit) {
            $stmt = $this->pdo->prepare("UPDATE comments SET status = 'deleted', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            return $stmt->execute([$comment_id]);
        }
        return false;
    }

    /**
     * Update/Edit a comment (within 5 minutes)
     */
    public function updateComment($user_id, $comment_id, $content, $mentions = [], $type = null) {
        if (!$this->pdo) return false;

        $stmt = $this->pdo->prepare("SELECT created_at, target_type FROM comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$comment_id, $user_id]);
        $res = $stmt->fetch();
        if (!$res) return false;

        $created_at = $res['created_at'];
        $target_type = $res['target_type'];

        $edit_limit = (int)get_setting('comments_edit_time_limit', '300');

        if ($created_at && (time() - strtotime($created_at)) < $edit_limit) {
            // Explicitly append mentions if provided
            if (!empty($mentions) && is_array($mentions)) {
                foreach ($mentions as $uid) {
                    if (is_numeric($uid) && !str_contains($content, "[user:$uid]")) {
                        $content .= " [user:$uid]";
                    }
                }
            }

            $content = $this->sanitizeHTML($content);
            $stored_content = $this->convertMentionsToPlaceholders($content);

            if ($type && $target_type !== 'post') {
                $stmt = $this->pdo->prepare("UPDATE comments SET content = ?, type = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                return $stmt->execute([$stored_content, $type, $comment_id]);
            } else {
                $stmt = $this->pdo->prepare("UPDATE comments SET content = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                return $stmt->execute([$stored_content, $comment_id]);
            }
        }
        return false;
    }

    /**
     * Sanitize HTML content to prevent XSS while allowing specific tags
     */
    public function sanitizeHTML($html) {
        if (empty($html)) return '';

        $allowed_tags = '<p><strong><em><b><i><blockquote><ul><ol><li><br>';
        $html = strip_tags($html, $allowed_tags);

        // Remove attributes and inline styles using regex
        $html = preg_replace('/<([a-z1-6]+)\s+[^>]*>/i', '<$1>', $html);

        // Ensure illegal nesting like <ul> inside <p> is fixed server-side
        // We look for <ul>, <ol>, <blockquote> inside <p> and close the <p> before them
        // Using a negative lookahead to ensure we don't match across existing </p> tags
        $html = preg_replace('/<p>((?:(?!<\/p>).)*?)<(ul|ol|blockquote)>/is', '<p>$1</p><$2>', $html);
        // And re-open <p> after them if needed
        $html = preg_replace('/<\/(ul|ol|blockquote)>((?:(?!<p>).)*?)<\/p>/is', '</$1><p>$2</p>', $html);

        // Final cleanup of empty paragraphs
        $html = preg_replace('/<p>\s*<\/p>/i', '', $html);

        return trim($html);
    }

    /**
     * Parse [user:ID] placeholders in content and convert to links
     */
    public function parseMentions($content, $userMap = []) {
        // Content is already sanitized before being stored in DB
        return preg_replace_callback('/\[user:(\d+)\]/', function($matches) use ($userMap) {
            $userId = $matches[1];
            if (isset($userMap[$userId])) {
                $username = $userMap[$userId]['username'];
                $name = $userMap[$userId]['name'];
                // ID-based routing: /profile/ID/username
                return '<a href="/profile/' . $userId . '/' . urlencode($username) . '" class="mention" title="' . htmlspecialchars($name) . '">@' . htmlspecialchars($username) . '</a>';
            }
            return '<span class="mention-deleted text-gray-400">@user:' . $userId . '</span>';
        }, $content);
    }

    /**
     * Convert [user:ID] placeholders back to @username (for editing)
     */
    public function convertPlaceholdersToMentions($content, $userMap = [], $strip = false) {
        $content = preg_replace_callback('/\[user:(\d+)\]/', function($matches) use ($userMap, $strip) {
            if ($strip) return '';
            $userId = $matches[1];
            if (isset($userMap[$userId])) {
                return '@' . $userMap[$userId]['username'];
            }

            $stmt = $this->pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $username = $stmt->fetchColumn();
            return $username ? '@' . $username : '[user:' . $userId . ']';
        }, $content);

        if ($strip) {
            $content = trim($content);
        }
        return $content;
    }

    /**
     * Extract mentioned users from content
     */
    private function extractMentionedUsers($content, $userMap) {
        preg_match_all('/\[user:(\d+)\]/', $content, $matches);
        $userIds = array_values(array_unique($matches[1]));
        $mentions = [];
        foreach ($userIds as $uid) {
            if (isset($userMap[$uid])) {
                $mentions[] = $userMap[$uid];
            }
        }
        return $mentions;
    }

    /**
     * Convert @username to [user:ID] placeholders
     */
    private function convertMentionsToPlaceholders($content) {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
        $usernames = array_values(array_unique($matches[1]));

        foreach ($usernames as $username) {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
            $stmt->execute([$username]);
            $uid = $stmt->fetchColumn();
            if ($uid) {
                // Use word boundary to avoid partial matches
                $content = preg_replace('/@' . preg_quote($username, '/') . '\b/i', "[user:$uid]", $content);
            }
        }
        return $content;
    }

    /**
     * Bulk load users mentioned in a list of comments
     */
    public function loadMentionedUsers($comments) {
        $userIds = [];
        foreach ($comments as $c) {
            preg_match_all('/\[user:(\d+)\]/', $c['content'], $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $uid) $userIds[] = (int)$uid;
            }
            if (!empty($c['replies'])) {
                foreach ($c['replies'] as $r) {
                    preg_match_all('/\[user:(\d+)\]/', $r['content'], $matches);
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $uid) $userIds[] = (int)$uid;
                    }
                }
            }
        }

        $userIds = array_values(array_unique($userIds));
        if (empty($userIds)) return [];

        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->pdo->prepare("SELECT id, username, name FROM users WHERE id IN ($placeholders)");
        $stmt->execute($userIds);

        $map = [];
        while ($row = $stmt->fetch()) {
            $map[$row['id']] = $row;
        }
        return $map;
    }

    /**
     * Bulk load reaction stats for a list of comment IDs
     */
    public function loadReactionStats($commentIds) {
        if (empty($commentIds)) return [];

        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        $sql = "SELECT comment_id,
                       SUM(CASE WHEN reaction_type = 'like' THEN 1 ELSE 0 END) as likes,
                       SUM(CASE WHEN reaction_type = 'dislike' THEN 1 ELSE 0 END) as dislikes,
                       SUM(CASE WHEN reaction_type = 'heart' THEN 1 ELSE 0 END) as hearts,
                       SUM(CASE WHEN reaction_type = 'fire' THEN 1 ELSE 0 END) as fires
                FROM comment_reactions
                WHERE comment_id IN ($placeholders)
                GROUP BY comment_id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($commentIds);

        $stats = [];
        while ($row = $stmt->fetch()) {
            $stats[$row['comment_id']] = [
                'likes' => (int)$row['likes'],
                'dislikes' => (int)$row['dislikes'],
                'hearts' => (int)$row['hearts'],
                'fires' => (int)$row['fires']
            ];
        }
        return $stats;
    }

    /**
     * Bulk load user reactions for a list of comment IDs
     */
    public function loadUserReactions($user_id, $commentIds) {
        if (!$user_id || empty($commentIds)) return [];

        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        $sql = "SELECT comment_id, reaction_type FROM comment_reactions WHERE user_id = ? AND comment_id IN ($placeholders)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge([$user_id], $commentIds));

        $reactions = [];
        while ($row = $stmt->fetch()) {
            $reactions[$row['comment_id']] = $row['reaction_type'];
        }
        return $reactions;
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
     * Handle mentions and queue notifications (Optimized: Batch user lookups)
     */
    private function handleMentions($content, $comment_id, $sender_id, $sender_name) {
        $notif = new Notifications($this->pdo);

        // 1. Extract potential mention targets
        preg_match_all('/\[user:(\d+)\]/', $content, $matches_id);
        $userIds = array_values(array_unique($matches_id[1]));

        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches_user);
        $usernames = array_values(array_unique($matches_user[1]));

        if (empty($userIds) && empty($usernames)) return;

        // 2. Batch fetch all target users in a single query
        $where_clauses = [];
        $params = [];
        if (!empty($userIds)) {
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $where_clauses[] = "id IN ($placeholders)";
            $params = array_merge($params, $userIds);
        }
        if (!empty($usernames)) {
            $placeholders = implode(',', array_fill(0, count($usernames), '?'));
            $where_clauses[] = "LOWER(username) IN ($placeholders)";
            foreach ($usernames as $u) $params[] = strtolower($u);
        }

        $stmt = $this->pdo->prepare("SELECT id, email, name FROM users WHERE " . implode(" OR ", $where_clauses));
        $stmt->execute($params);
        $target_users = $stmt->fetchAll();

        // 3. Process notifications
        $pushService = new PushService($this->pdo);
        foreach ($target_users as $user) {
            if ($user['id'] != $sender_id) {
                $this->sendNotificationEmail($user, 'mention', $comment_id, $sender_name);
                $notif->create($user['id'], $sender_id, 'mention', $comment_id);

                $pushService->notify($user['id'], 'social_mention', [
                    'sender_name' => $sender_name,
                    'url' => get_site_url() . "/thread/$comment_id"
                ]);
            }
        }
    }

    /**
     * Notify author of parent comment about a reply
     */
    private function notifyReply($parent_id, $comment_id, $sender_name) {
        $stmt = $this->pdo->prepare("SELECT u.id, u.email, u.name, c.user_id as sender_id
                                     FROM comments pc
                                     JOIN users u ON pc.user_id = u.id
                                     JOIN comments c ON c.id = ?
                                     WHERE pc.id = ?");
        $stmt->execute([$comment_id, $parent_id]);
        $user = $stmt->fetch();

        if ($user && $user['id'] != $user['sender_id']) {
            $this->sendNotificationEmail($user, 'reply', $comment_id, $sender_name);
            $notif = new Notifications($this->pdo);
            $notif->create($user['id'], $user['sender_id'], 'reply', $comment_id);
        }
    }

    /**
     * Send notification email (Optimized: Accepts pre-fetched sender name)
     */
    private function sendNotificationEmail($user, $type, $comment_id, $sender_name) {
        // Fetch full recipient data if only ID is provided (e.g. from direct reply)
        if (!isset($user['email']) || empty($user['email'])) {
            $stmt = $this->pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
            if (!$user || empty($user['email'])) return;
        }

        $subject = ($type === 'mention') ? "از شما در یک نظر نام برده شد" : "به نظر شما پاسخ داده شد";
        $message = ($type === 'mention')
            ? "سلام {$user['name']} عزیز،<br><br>کاربر <strong>{$sender_name}</strong> در یک نظر از شما نام برده است.<br><br>برای مشاهده نظر می‌توانید به سایت مراجعه کنید."
            : "سلام {$user['name']} عزیز،<br><br>کاربر <strong>{$sender_name}</strong> به نظر شما پاسخ داده است.<br><br>برای مشاهده پاسخ می‌توانید به سایت مراجعه کنید.";

        Mail::queueRaw($user['email'], $subject, Mail::getProfessionalLayout($message));
    }
}
