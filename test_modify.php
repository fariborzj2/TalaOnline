<?php
$content = file_get_contents('includes/push_service.php');

$search = <<<SEARCH
        if (!\$this->pdo) return false;

        // Deduplication Mechanism (5-minute window)
        \$time_window = floor(time() / 300) * 300;
        \$dedup_hash = hash('sha256', \$user_id . '_' . \$template_slug . '_' . json_encode(\$data) . '_' . \$time_window);

        // Clean up old hashes occasionally
        if (rand(1, 100) === 1) {
            \$this->pdo->exec("DELETE FROM notification_deduplication WHERE expires_at <= CURRENT_TIMESTAMP");
        }

        \$stmt = \$this->pdo->prepare("SELECT 1 FROM notification_deduplication WHERE hash = ? AND expires_at > CURRENT_TIMESTAMP");
        \$stmt->execute([\$dedup_hash]);
        if (\$stmt->fetchColumn()) {
            return true; // Silently drop duplicate
        }

        \$expires_at = date('Y-m-d H:i:s', time() + 300);
        \$stmt = \$this->pdo->prepare("INSERT INTO notification_deduplication (hash, expires_at) VALUES (?, ?)");
        try {
            \$stmt->execute([\$dedup_hash, \$expires_at]);
        } catch (PDOException \$e) {
            return true; // Another worker just inserted it. Drop duplicate.
        }
SEARCH;

$replace = <<<REPLACE
        if (!\$this->pdo) return false;

        // Inject sender_id if provided into data to ensure deduplication hash matches final stored data
        if (isset(\$options['sender_id'])) {
            \$data['_sender_id'] = \$options['sender_id'];
        }

        // Deduplication Mechanism (5-minute window)
        \$time_window = floor(time() / 300) * 300;
        \$dedup_hash = hash('sha256', \$user_id . '_' . \$template_slug . '_' . json_encode(\$data) . '_' . \$time_window);

        // Clean up old hashes occasionally
        if (rand(1, 100) === 1) {
            \$this->pdo->exec("DELETE FROM notification_deduplication WHERE expires_at <= CURRENT_TIMESTAMP");
        }

        \$stmt = \$this->pdo->prepare("SELECT 1 FROM notification_deduplication WHERE hash = ? AND expires_at > CURRENT_TIMESTAMP");
        \$stmt->execute([\$dedup_hash]);
        if (\$stmt->fetchColumn()) {
            return true; // Silently drop duplicate
        }

        \$expires_at = date('Y-m-d H:i:s', time() + 300);
        \$stmt = \$this->pdo->prepare("INSERT INTO notification_deduplication (hash, expires_at) VALUES (?, ?)");
        try {
            \$stmt->execute([\$dedup_hash, \$expires_at]);
        } catch (PDOException \$e) {
            return true; // Another worker just inserted it. Drop duplicate.
        }
REPLACE;

$content = str_replace($search, $replace, $content);

$search2 = <<<SEARCH
        try {
            // Preserve sender_id in data if provided
            if (isset(\$options['sender_id'])) {
                \$data['_sender_id'] = \$options['sender_id'];
            }

            \$priority = \$options['priority'] ?? \$template['priority'];
SEARCH;

$replace2 = <<<REPLACE
        try {
            \$priority = \$options['priority'] ?? \$template['priority'];
REPLACE;

$content = str_replace($search2, $replace2, $content);
file_put_contents('includes/push_service.php', $content);
