<?php
/**
 * Navasan API Service
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/push_service.php';
require_once __DIR__ . '/trigger_engine.php';

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

        $today = date('Y-m-d');
        $cache_values = [];
        $cache_params = [];
        $history_values = [];
        $history_params = [];

        foreach ($data as $symbol => $info) {
            if (isset($info['value'])) {
                $value = (float)$info['value'];
                $change = (float)($info['change'] ?? 0);
                $percent = ($value > 0) ? round(($change / ($value - $change)) * 100, 2) : 0;

                $cache_values[] = "(?, ?, ?, ?, CURRENT_TIMESTAMP)";
                array_push($cache_params, $symbol, $value, $change, $percent);

                $history_values[] = "(?, ?, ?, ?, ?)";
                array_push($history_params, $symbol, $value, $value, $value, $today);
            }
        }

        if (!empty($cache_values)) {
            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            if ($driver === 'sqlite') {
                $cache_sql = "INSERT INTO prices_cache (symbol, price, change_val, change_percent, updated_at) VALUES " . implode(',', $cache_values);
                $cache_sql .= " ON CONFLICT(symbol) DO UPDATE SET price = excluded.price, change_val = excluded.change_val, change_percent = excluded.change_percent, updated_at = CURRENT_TIMESTAMP";
                $this->pdo->prepare($cache_sql)->execute($cache_params);

                $history_sql = "INSERT INTO prices_history (symbol, price, high, low, date) VALUES " . implode(',', $history_values);
                $history_sql .= " ON CONFLICT(symbol, date) DO UPDATE SET price = excluded.price, high = MAX(high, excluded.price), low = MIN(low, excluded.price)";
                $this->pdo->prepare($history_sql)->execute($history_params);
            } else {
                $cache_sql = "INSERT INTO prices_cache (symbol, price, change_val, change_percent, updated_at) VALUES " . implode(',', $cache_values);
                $cache_sql .= " ON DUPLICATE KEY UPDATE price = VALUES(price), change_val = VALUES(change_val), change_percent = VALUES(change_percent), updated_at = CURRENT_TIMESTAMP";
                $this->pdo->prepare($cache_sql)->execute($cache_params);

                $history_sql = "INSERT INTO prices_history (symbol, price, high, low, date) VALUES " . implode(',', $history_values);
                $history_sql .= " ON DUPLICATE KEY UPDATE price = VALUES(price), high = GREATEST(high, VALUES(price)), low = LEAST(low, VALUES(price))";
                $this->pdo->prepare($history_sql)->execute($history_params);
            }

            // Trigger Engine for Market Events
            $pushService = new PushService($this->pdo);
            $triggerEngine = new TriggerEngine($this->pdo, $pushService);

            // Get current High/Low stats for technical break trigger
            $symbols = array_keys($data);
            $placeholders = implode(',', array_fill(0, count($symbols), '?'));
            $stmt = $this->pdo->prepare("SELECT symbol, high, low FROM prices_history WHERE date = ? AND symbol IN ($placeholders)");
            $stmt->execute(array_merge([$today], $symbols));
            $stats = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

            foreach ($data as $symbol => $info) {
                if (isset($info['value'])) {
                    $value = (float)$info['value'];
                    $change = (float)($info['change'] ?? 0);
                    $percent = ($value > 0) ? round(($change / ($value - $change)) * 100, 2) : 0;

                    // 1. Volatility Trigger
                    if (abs($percent) >= 5) {
                        $triggerEngine->handleVolatilitySpike($symbol, $percent);
                    }

                    // 2. Technical Break Trigger
                    if (isset($stats[$symbol])) {
                        $triggerEngine->handleTechnicalBreak($symbol, $value, (float)$stats[$symbol]['high'], (float)$stats[$symbol]['low']);
                    }

                    // 3. Market Anomaly Trigger
                    $triggerEngine->handleMarketAnomaly($symbol, $value);
                }
            }
        }

        return true;
    }

    /**
     * Get items merged with latest prices and overrides (Optimized fetching)
     */
    public function getItems($filters = []) {
        $today = date('Y-m-d');
        $where = ["i.is_active = 1"];
        $params = [];

        if (!empty($filters['category'])) {
            $where[] = "i.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['symbols'])) {
            $symbols = is_array($filters['symbols']) ? $filters['symbols'] : [$filters['symbols']];
            $placeholders = implode(',', array_fill(0, count($symbols), '?'));
            $where[] = "i.symbol IN ($placeholders)";
            $params = array_merge($params, $symbols);
        }

        $where_sql = implode(" AND ", $where);
        $sql = "SELECT i.*, p.price as api_price, p.change_val, p.change_percent, p.updated_at
                FROM items i
                LEFT JOIN prices_cache p ON i.symbol = p.symbol
                WHERE $where_sql
                ORDER BY i.sort_order ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        if (empty($items)) return [];

        $symbols = array_column($items, 'symbol');
        $placeholders = implode(',', array_fill(0, count($symbols), '?'));

        // Pre-fetch today's high/low for the selected symbols
        $stmt = $this->pdo->prepare("SELECT symbol, high, low FROM prices_history WHERE date = ? AND symbol IN ($placeholders)");
        $stmt->execute(array_merge([$today], $symbols));
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
                'slug' => $item['slug'] ?? $item['symbol'],
                'name' => $item['name'],
                'en_name' => $item['en_name'],
                'logo' => $item['logo'],
                'description' => $item['description'],
                'category' => $item['category'],
                'price' => $display_price,
                'change' => $item['change_val'] ?? 0,
                'change_amount' => $item['change_val'] ?? 0,
                'change_percent' => $item['change_percent'] ?? 0,
                'high' => $stats[$symbol]['high'] ?? $display_price,
                'low' => $stats[$symbol]['low'] ?? $display_price,
                'updated_at' => $item['updated_at'],
                'is_overridden' => $is_overridden,
                'show_in_summary' => (int)($item['show_in_summary'] ?? 0),
                'show_chart' => (int)($item['show_chart'] ?? 0)
            ];
        }

        return $processed;
    }

    /**
     * Compatibility wrapper for getDashboardData
     */
    public function getDashboardData() {
        return $this->getItems();
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
