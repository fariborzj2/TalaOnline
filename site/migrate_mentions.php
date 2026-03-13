<?php
/**
 * Migration Script: Convert @username mentions to [user:ID] placeholders
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once __DIR__ . '/../includes/db.php';

if (!$pdo) {
    die("Database connection failed.\n");
}

echo "Starting migration of mentions...\n";

try {
    $stmt = $pdo->query("SELECT id, content FROM comments");
    $comments = $stmt->fetchAll();
    $total = count($comments);
    $updated = 0;

    // Pass 1: Collect all unique usernames
    $all_usernames = [];
    foreach ($comments as $comment) {
        $content = $comment['content'];

        // Match standard @username mentions
        if (preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches)) {
            foreach ($matches[1] as $u) {
                $all_usernames[strtolower($u)] = $u;
            }
        }

        // Match HTML link mentions
        if (preg_match_all('/<a[^>]+href="\/profile\/([a-zA-Z0-9_]+)"[^>]*>.*?<\/a>/i', $content, $matches)) {
            foreach ($matches[1] as $u) {
                $all_usernames[strtolower($u)] = $u;
            }
        }
    }

    // Pass 2: Bulk fetch user IDs
    $user_map = [];
    if (!empty($all_usernames)) {
        $unique_usernames = array_keys($all_usernames);
        $chunks = array_chunk($unique_usernames, 100);

        foreach ($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $stmt_users = $pdo->prepare("SELECT id, LOWER(username) as username_lower FROM users WHERE LOWER(username) IN ($placeholders)");
            $stmt_users->execute($chunk);

            while ($row = $stmt_users->fetch(PDO::FETCH_ASSOC)) {
                $user_map[$row['username_lower']] = $row['id'];
            }
        }
    }

    // Pass 3: Process and update comments
    foreach ($comments as $comment) {
        $content = $comment['content'];
        $original_content = $content;

        // 1. Handle standard @username mentions
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
        $usernames = array_unique($matches[1]);

        foreach ($usernames as $username) {
            $lower_u = strtolower($username);
            if (isset($user_map[$lower_u])) {
                $userId = $user_map[$lower_u];
                // Replace @username with [user:ID]
                $content = preg_replace('/@' . preg_quote($username, '/') . '\b/i', "[user:$userId]", $content);
            }
        }

        // 2. Handle potential static HTML links if any existed (as mentioned in the request)
        // Format: <a href="/profile/username" class="mention">@username</a>
        preg_match_all('/<a[^>]+href="\/profile\/([a-zA-Z0-9_]+)"[^>]*>.*?<\/a>/i', $content, $matches);
        if (!empty($matches[1])) {
            foreach (array_unique($matches[1]) as $username) {
                $lower_u = strtolower($username);
                if (isset($user_map[$lower_u])) {
                    $userId = $user_map[$lower_u];
                    // Replace the whole tag with [user:ID]
                    $pattern = '/<a[^>]+href="\/profile\/' . preg_quote($username, '/') . '"[^>]*>.*?<\/a>/i';
                    $content = preg_replace($pattern, "[user:$userId]", $content);
                }
            }
        }

        if ($content !== $original_content) {
            $up_stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
            $up_stmt->execute([$content, $comment['id']]);
            $updated++;
        }
    }

    echo "Migration completed.\n";
    echo "Total comments processed: $total\n";
    echo "Comments updated: $updated\n";

} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
