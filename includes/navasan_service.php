<?php
/**
 * Navasan API Service
 */

require_once __DIR__ . '/db.php';

class NavasanService {
    private $api_key;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->api_key = get_setting('api_key');
    }

    /**
     * Fetch latest prices from Navasan API and update cache
     */
    public function syncPrices() {
        if (empty($this->api_key)) {
            return false;
        }

        $url = "http://api.navasan.tech/latest/?api_key=" . $this->api_key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return false;

        $data = json_decode($response, true);
        if (!$data) return false;

        foreach ($data as $symbol => $info) {
            if (isset($info['value'])) {
                $stmt = $this->pdo->prepare("INSERT INTO prices_cache (symbol, price, change_val, change_percent, updated_at)
                    VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
                    ON DUPLICATE KEY UPDATE
                    price = VALUES(price),
                    change_val = VALUES(change_val),
                    change_percent = VALUES(change_percent),
                    updated_at = CURRENT_TIMESTAMP");

                // Navasan change is relative to yesterday, we might need to calculate percent if not provided
                // Looking at docs, change is a number. Percent might not be there.
                $change = $info['change'] ?? 0;
                $value = (float)$info['value'];
                $percent = ($value > 0) ? round(($change / ($value - $change)) * 100, 2) : 0;

                $stmt->execute([
                    $symbol,
                    $info['value'],
                    $change,
                    $percent
                ]);
            }
        }

        // Record today's price in history and track high/low
        $today = date('Y-m-d');
        foreach ($data as $symbol => $info) {
            if (isset($info['value'])) {
                $price = $info['value'];
                $stmt = $this->pdo->prepare("INSERT INTO prices_history (symbol, price, high, low, date)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    price = VALUES(price),
                    high = GREATEST(high, VALUES(price)),
                    low = LEAST(low, VALUES(price))");
                $stmt->execute([$symbol, $price, $price, $price, $today]);
            }
        }

        return true;
    }

    /**
     * Get items merged with latest prices and overrides
     */
    public function getDashboardData() {
        $today = date('Y-m-d');

        // Fetch all managed items
        $stmt = $this->pdo->query("SELECT i.*, p.price as api_price, p.change_val, p.change_percent, p.updated_at
                                   FROM items i
                                   LEFT JOIN prices_cache p ON i.symbol = p.symbol
                                   ORDER BY i.sort_order ASC");
        $items = $stmt->fetchAll();

        // Pre-fetch today's high/low for all symbols
        $stmt = $this->pdo->prepare("SELECT symbol, high, low FROM prices_history WHERE date = ?");
        $stmt->execute([$today]);
        $stats = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

        $processed = [];
        foreach ($items as $item) {
            $symbol = $item['symbol'];
            $display_price = $item['api_price'];
            $is_overridden = false;

            if ($item['is_manual'] && !empty($item['manual_price'])) {
                $display_price = $item['manual_price'];
                $is_overridden = true;
            }

            $processed[] = [
                'symbol' => $item['symbol'],
                'name' => $item['name'],
                'en_name' => $item['en_name'],
                'logo' => $item['logo'],
                'description' => $item['description'],
                'category' => $item['category'],
                'price' => $display_price,
                'change' => $item['change_val'] ?? 0,
                'change_percent' => $item['change_percent'] ?? 0,
                'high' => $stats[$symbol]['high'] ?? $display_price,
                'low' => $stats[$symbol]['low'] ?? $display_price,
                'updated_at' => $item['updated_at'],
                'is_overridden' => $is_overridden
            ];
        }

        return $processed;
    }

    /**
     * Get API usage statistics from Navasan
     */
    public function getUsage() {
        if (empty($this->api_key)) return null;

        $url = "http://api.navasan.tech/usage/?api_key=" . $this->api_key;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return null;
        return json_decode($response, true);
    }
}
