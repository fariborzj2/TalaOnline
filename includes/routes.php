<?php
/**
 * Application Routes
 */

require_once __DIR__ . '/navasan_service.php';

$router->add('/', function() {
    global $pdo;
    $items = [];
    if ($pdo) {
        $navasan = new NavasanService($pdo);
        $items = $navasan->getDashboardData();
    } else {
        // Mock data for verification if DB is not available
        $items = [
            ['symbol' => '18ayar', 'price' => 19853180, 'change_percent' => 3.04, 'category' => 'gold', 'name' => 'طلای ۱۸ عیار'],
            ['symbol' => 'silver', 'price' => 1234567, 'change_percent' => -0.5, 'category' => 'silver', 'name' => 'نقره ۹۹۹'],
            ['symbol' => 'sekeh', 'price' => 193000000, 'change_percent' => 1.54, 'category' => 'coin', 'name' => 'سکه امامی'],
            ['symbol' => 'rob', 'price' => 56500000, 'change_percent' => 3.15, 'category' => 'coin', 'name' => 'ربع سکه'],
        ];
    }

    $categories_data = [];
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
            $categories_data = $stmt->fetchAll();
        } catch (Exception $e) {}
    } else {
        $categories_data = [
            ['slug' => 'gold', 'name' => 'طلا و جواهرات', 'en_name' => 'gold market', 'icon' => 'coins'],
            ['slug' => 'coin', 'name' => 'مسکوکات طلا', 'en_name' => 'gold coins', 'icon' => 'circle-dollar-sign'],
        ];
    }

    $gold_data = null;
    $silver_data = null;

    // Group items by category
    $grouped_items = [];
    foreach ($categories_data as $cat) {
        $grouped_items[$cat['slug']] = [
            'info' => $cat,
            'items' => []
        ];
    }

    foreach ($items as $item) {
        if ($item['symbol'] == '18ayar') $gold_data = $item;
        if ($item['symbol'] == 'silver') $silver_data = $item;

        if (isset($grouped_items[$item['category']])) {
            $grouped_items[$item['category']]['items'][] = $item;
        }
    }

    $platforms = [];
    $summary_items = [];
    $chart_items = [];
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM platforms WHERE is_active = 1 ORDER BY sort_order ASC");
            $platforms = $stmt->fetchAll();

            // Check for items marked as show_in_summary
            $stmt = $pdo->query("SELECT symbol FROM items WHERE show_in_summary = 1 AND is_active = 1 ORDER BY sort_order ASC LIMIT 4");
            $summary_symbols = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Check for items marked as show_chart
            $stmt = $pdo->query("SELECT symbol FROM items WHERE show_chart = 1 AND is_active = 1 ORDER BY sort_order ASC");
            $chart_symbols = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($items as $item) {
                if (in_array($item['symbol'], $summary_symbols)) {
                    $summary_items[] = $item;
                }
                if (in_array($item['symbol'], $chart_symbols)) {
                    $chart_items[] = $item;
                }
            }
        } catch (Exception $e) {}
    } else {
        // Mock chart items
        foreach ($items as $item) {
            if (in_array($item['symbol'], ['18ayar', 'sekeh'])) {
                $chart_items[] = $item;
            }
        }
    }

    // Fallback if no items marked for summary
    if (empty($summary_items)) {
        if ($gold_data) $summary_items[] = $gold_data;
        if ($silver_data) $summary_items[] = $silver_data;
    }

    if (empty($platforms)) {
        $platforms = [
            ['name' => 'گرمی', 'logo' => 'assets/images/platforms/gerami.png', 'buy_price' => 19849500, 'sell_price' => 19749500, 'fee' => 0.5, 'status' => 'active', 'link' => '#', 'en_name' => 'Gerami'],
            ['name' => 'میلی', 'logo' => 'assets/images/platforms/milli.png', 'buy_price' => 19849500, 'sell_price' => 19749500, 'fee' => 0.3, 'status' => 'active', 'link' => '#', 'en_name' => 'Milli'],
        ];
    }

    return View::renderPage('home', [
        'site_title' => get_setting('site_title', 'طلا آنلاین'),
        'site_description' => get_setting('site_description', 'مرجع تخصصی قیمت لحظه‌ای طلا، سکه و ارز.'),
        'site_keywords' => get_setting('site_keywords', 'قیمت طلا, قیمت سکه'),
        'gold_data' => $gold_data,
        'silver_data' => $silver_data,
        'summary_items' => $summary_items,
        'chart_items' => $chart_items,
        'grouped_items' => $grouped_items,
        'platforms' => $platforms
    ]);
});

$router->add('/item/:symbol', function($params) {
    return View::renderPage('item_details', [
        'symbol' => $params['symbol'],
        'page_title' => 'جزئیات ' . $params['symbol']
    ]);
});
