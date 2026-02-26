<?php
/**
 * Independent Market Sentiment Logic
 */

class MarketSentiment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Submit or update a vote for a currency today
     */
    public function vote($currency_id, $user_id, $ip_address, $vote) {
        if (!$this->pdo) return false;
        if (!in_array($vote, ['bullish', 'bearish'])) return false;

        // Check if user/IP already voted today
        $existing = $this->getUserVote($currency_id, $user_id, $ip_address);

        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $stmt = $this->pdo->prepare("UPDATE market_sentiment SET vote = ?, updated_at = ? WHERE id = ?");
            return $stmt->execute([$vote, $now, $existing['id']]);
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO market_sentiment (currency_id, user_id, ip_address, vote, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$currency_id, $user_id, $ip_address, $vote, $now, $now]);
        }
    }

    /**
     * Get aggregate results for a currency today
     */
    public function getResults($currency_id) {
        if (!$this->pdo) return ['total' => 0, 'bullish' => 0, 'bearish' => 0, 'bullish_percent' => 50, 'bearish_percent' => 50];

        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');

        $stmt = $this->pdo->prepare("SELECT
            COUNT(*) as total,
            SUM(CASE WHEN vote = 'bullish' THEN 1 ELSE 0 END) as bullish,
            SUM(CASE WHEN vote = 'bearish' THEN 1 ELSE 0 END) as bearish
            FROM market_sentiment
            WHERE currency_id = ? AND created_at >= ? AND created_at <= ?");
        $stmt->execute([$currency_id, $today_start, $today_end]);
        $res = $stmt->fetch();

        $total = (int)($res['total'] ?? 0);
        $bullish = (int)($res['bullish'] ?? 0);
        $bearish = (int)($res['bearish'] ?? 0);

        $bullish_percent = $total > 0 ? round(($bullish / $total) * 100) : 50;
        $bearish_percent = $total > 0 ? (100 - $bullish_percent) : 50;

        return [
            'total' => $total,
            'bullish' => $bullish,
            'bearish' => $bearish,
            'bullish_percent' => $bullish_percent,
            'bearish_percent' => $bearish_percent
        ];
    }

    /**
     * Get user's vote for today
     */
    public function getUserVote($currency_id, $user_id, $ip_address) {
        if (!$this->pdo) return null;

        $today_start = date('Y-m-d 00:00:00');
        $today_end = date('Y-m-d 23:59:59');

        $sql = "SELECT * FROM market_sentiment WHERE currency_id = ? AND created_at >= ? AND created_at <= ? AND ";
        $params = [$currency_id, $today_start, $today_end];

        if ($user_id) {
            $sql .= "(user_id = ? OR ip_address = ?)";
            $params[] = $user_id;
            $params[] = $ip_address;
        } else {
            $sql .= "ip_address = ?";
            $params[] = $ip_address;
        }
        $sql .= " ORDER BY id DESC LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
