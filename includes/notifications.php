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
    public function getNotifications($user_id, $limit = 20, $offset = 0) {
        if (!$this->pdo) return [];

        try {
            $sql = "SELECT n.*, u.name as sender_name, u.avatar as sender_avatar, u.username as sender_username
                    FROM notifications n
                    LEFT JOIN users u ON n.sender_id = u.id
                    WHERE n.user_id = ?
                    ORDER BY n.created_at DESC
                    LIMIT ? OFFSET ?";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
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
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
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
            $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            return $stmt->execute([$notification_id, $user_id]);
        } catch (Exception $e) {
            return false;
        }
    }
}
