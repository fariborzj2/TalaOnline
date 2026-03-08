<?php
/**
 * Notifications Logic
 */

class Notifications {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new notification
     */
    public function create($user_id, $sender_id, $type, $target_id) {
        if (!$this->pdo || $user_id == $sender_id) return false;

        try {
            $stmt = $this->pdo->prepare("INSERT INTO notifications (user_id, sender_id, type, target_id) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$user_id, $sender_id, $type, (string)$target_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get user notifications
     */
    public function getNotifications($user_id, $limit = 20, $offset = 0, $type = null) {
        if (!$this->pdo) return [];

        try {
            $params = [$user_id];
            $sql = "SELECT n.*, u.name as sender_name, u.avatar as sender_avatar, u.username as sender_username
                    FROM notifications n
                    LEFT JOIN users u ON n.sender_id = u.id
                    WHERE n.user_id = ? AND n.status != 'archived'";

            if ($type) {
                $sql .= " AND n.type = ?";
                $params[] = $type;
            }

            $sql .= " ORDER BY n.created_at DESC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;

            $stmt = $this->pdo->prepare($sql);
            foreach ($params as $i => $val) {
                $stmt->bindValue($i + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Count unread notifications
     */
    public function countUnread($user_id) {
        if (!$this->pdo) return 0;

        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user_id]);
            return (int)$stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($user_id) {
        if (!$this->pdo) return false;

        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1, status = 'read', read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND status = 'unread'");
            return $stmt->execute([$user_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead($user_id, $notification_id) {
        if (!$this->pdo) return false;

        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1, status = 'read', read_at = CURRENT_TIMESTAMP WHERE id = ? AND user_id = ? AND status = 'unread'");
            return $stmt->execute([$notification_id, $user_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Archive a single notification
     */
    public function archive($user_id, $notification_id) {
        if (!$this->pdo) return false;

        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET status = 'archived' WHERE id = ? AND user_id = ?");
            return $stmt->execute([$notification_id, $user_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Archive all notifications for a user
     */
    public function archiveAll($user_id) {
        if (!$this->pdo) return false;

        try {
            $stmt = $this->pdo->prepare("UPDATE notifications SET status = 'archived' WHERE user_id = ?");
            return $stmt->execute([$user_id]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Cleanup old notifications
     */
    public function cleanup() {
        if (!$this->pdo) return false;
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        // Retention Policy:
        // - Read notifications (non-essential): 30 days
        // - Unread notifications (non-essential): 90 days
        // - Essential types (system, security, transaction): never auto-deleted

        $non_essential_types = "('mention', 'reply', 'follow')";

        try {
            if ($driver === 'sqlite') {
                // Delete Read
                $this->pdo->exec("DELETE FROM notifications
                                WHERE status = 'read'
                                AND type IN $non_essential_types
                                AND read_at < datetime('now', '-30 days')");

                // Delete Unread (Expired)
                $this->pdo->exec("DELETE FROM notifications
                                WHERE status = 'unread'
                                AND type IN $non_essential_types
                                AND created_at < datetime('now', '-90 days')");
            } else {
                // Delete Read
                $this->pdo->exec("DELETE FROM notifications
                                WHERE status = 'read'
                                AND type IN $non_essential_types
                                AND read_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

                // Delete Unread
                $this->pdo->exec("DELETE FROM notifications
                                WHERE status = 'unread'
                                AND type IN $non_essential_types
                                AND created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
