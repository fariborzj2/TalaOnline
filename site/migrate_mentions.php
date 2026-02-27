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

    foreach ($comments as $comment) {
        $content = $comment['content'];
        $original_content = $content;

        // 1. Handle standard @username mentions
        preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
        $usernames = array_unique($matches[1]);

        foreach ($usernames as $username) {
            $u_stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
            $u_stmt->execute([$username]);
            $userId = $u_stmt->fetchColumn();

            if ($userId) {
                // Replace @username with [user:ID]
                $content = preg_replace('/@' . preg_quote($username, '/') . '\b/i', "[user:$userId]", $content);
            }
        }

        // 2. Handle potential static HTML links if any existed (as mentioned in the request)
        // Format: <a href="/profile/username" class="mention">@username</a>
        preg_match_all('/<a[^>]+href="\/profile\/([a-zA-Z0-9_]+)"[^>]*>.*?<\/a>/i', $content, $matches);
        if (!empty($matches[1])) {
            foreach (array_unique($matches[1]) as $username) {
                $u_stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
                $u_stmt->execute([$username]);
                $userId = $u_stmt->fetchColumn();

                if ($userId) {
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
