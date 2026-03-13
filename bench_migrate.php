<?php
require_once __DIR__ . '/includes/db.php';

// Setup an in-memory SQLite database or a test table for the benchmark.
// We'll use the existing DB but create dummy users and comments to test.
// To not pollute the main DB, let's create a temporary connection to an in-memory SQLite DB
// and load the necessary schema.

$pdo = new PDO("sqlite::memory:");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Schema
$pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT)");
$pdo->exec("CREATE TABLE comments (id INTEGER PRIMARY KEY, content TEXT)");

// Insert dummy users
$stmt = $pdo->prepare("INSERT INTO users (username) VALUES (?)");
$usernames = [];
for ($i = 1; $i <= 500; $i++) {
    $username = "user_$i";
    $stmt->execute([$username]);
    $usernames[] = $username;
}

// Insert dummy comments
$stmt = $pdo->prepare("INSERT INTO comments (content) VALUES (?)");
for ($i = 1; $i <= 1000; $i++) {
    // Each comment mentions 3 random users
    $mentions = [];
    for ($j = 0; $j < 3; $j++) {
        $mentions[] = "@" . $usernames[array_rand($usernames)];
    }
    // and one HTML mention
    $html_user = $usernames[array_rand($usernames)];
    $mentions[] = "<a href=\"/profile/$html_user\">@$html_user</a>";

    $content = "This is a comment mentioning " . implode(", ", $mentions);
    $stmt->execute([$content]);
}

function run_migration($pdo, $is_optimized = false) {
    $start_time = microtime(true);

    // We can't use query counting easily with PDO without extending it,
    // so we'll just measure time for now.

    if (!$is_optimized) {
        // Original logic
        $stmt = $pdo->query("SELECT id, content FROM comments");
        $comments = $stmt->fetchAll();
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
                    $content = preg_replace('/@' . preg_quote($username, '/') . '\b/i', "[user:$userId]", $content);
                }
            }

            // 2. Handle HTML links
            preg_match_all('/<a[^>]+href="\/profile\/([a-zA-Z0-9_]+)"[^>]*>.*?<\/a>/i', $content, $matches);
            if (!empty($matches[1])) {
                foreach (array_unique($matches[1]) as $username) {
                    $u_stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?)");
                    $u_stmt->execute([$username]);
                    $userId = $u_stmt->fetchColumn();

                    if ($userId) {
                        $pattern = '/<a[^>]+href="\/profile\/' . preg_quote($username, '/') . '"[^>]*>.*?<\/a>/i';
                        $content = preg_replace($pattern, "[user:$userId]", $content);
                    }
                }
            }

            if ($content !== $original_content) {
                // To avoid modifying DB in benchmark if we don't want to, we could skip this
                // but let's just do it to measure full overhead.
                // $up_stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
                // $up_stmt->execute([$content, $comment['id']]);
                $updated++;
            }
        }
    } else {
        // Optimized logic
        $stmt = $pdo->query("SELECT id, content FROM comments");
        $comments = $stmt->fetchAll();
        $updated = 0;

        // Pass 1: Collect all usernames
        $all_usernames = [];
        foreach ($comments as $comment) {
            $content = $comment['content'];
            preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $u) {
                    $all_usernames[strtolower($u)] = $u;
                }
            }
            preg_match_all('/<a[^>]+href="\/profile\/([a-zA-Z0-9_]+)"[^>]*>.*?<\/a>/i', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $u) {
                    $all_usernames[strtolower($u)] = $u;
                }
            }
        }

        // Pass 2: Fetch all user IDs
        $user_map = [];
        if (!empty($all_usernames)) {
            $chunks = array_chunk(array_keys($all_usernames), 100);
            foreach ($chunks as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $stmt = $pdo->prepare("SELECT id, LOWER(username) as username_lower FROM users WHERE LOWER(username) IN ($placeholders)");
                $stmt->execute($chunk);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $user_map[$row['username_lower']] = $row['id'];
                }
            }
        }

        // Pass 3: Replace in comments
        foreach ($comments as $comment) {
            $content = $comment['content'];
            $original_content = $content;

            preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches);
            $usernames = array_unique($matches[1]);
            foreach ($usernames as $username) {
                $lower_u = strtolower($username);
                if (isset($user_map[$lower_u])) {
                    $userId = $user_map[$lower_u];
                    $content = preg_replace('/@' . preg_quote($username, '/') . '\b/i', "[user:$userId]", $content);
                }
            }

            preg_match_all('/<a[^>]+href="\/profile\/([a-zA-Z0-9_]+)"[^>]*>.*?<\/a>/i', $content, $matches);
            if (!empty($matches[1])) {
                foreach (array_unique($matches[1]) as $username) {
                    $lower_u = strtolower($username);
                    if (isset($user_map[$lower_u])) {
                        $userId = $user_map[$lower_u];
                        $pattern = '/<a[^>]+href="\/profile\/' . preg_quote($username, '/') . '"[^>]*>.*?<\/a>/i';
                        $content = preg_replace($pattern, "[user:$userId]", $content);
                    }
                }
            }

            if ($content !== $original_content) {
                // $up_stmt = $pdo->prepare("UPDATE comments SET content = ? WHERE id = ?");
                // $up_stmt->execute([$content, $comment['id']]);
                $updated++;
            }
        }
    }

    $end_time = microtime(true);
    return $end_time - $start_time;
}

$time_original = run_migration($pdo, false);
$time_optimized = run_migration($pdo, true);

echo "Original logic: " . number_format($time_original, 4) . " seconds\n";
echo "Optimized logic: " . number_format($time_optimized, 4) . " seconds\n";
echo "Improvement: " . number_format((($time_original - $time_optimized) / $time_original) * 100, 2) . "%\n";
