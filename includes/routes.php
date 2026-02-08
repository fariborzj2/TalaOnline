<?php

$router->add('/', function() {
    global $pdo, $navasan;

    $items = $navasan->getDashboardData();
    $site_title = get_setting('site_title', 'طلا آنلاین');
    $site_description = get_setting('site_description', 'مرجع تخصصی قیمت لحظه‌ای طلا، سکه و ارز. مقایسه بهترین پلتفرم‌های خرید و فروش طلا در ایران.');
    $site_keywords = get_setting('site_keywords', 'قیمت طلا, قیمت سکه, دلار تهران, خرید طلا, مقایسه قیمت طلا');

    // Organize data
    $gold_data = null;
    $silver_data = null;
    $coins = [];

    foreach ($items as $item) {
        if ($item['symbol'] == '18ayar') $gold_data = $item;
        if ($item['symbol'] == 'silver') $silver_data = $item;

        if (in_array($item['category'], ['gold', 'currency', 'coin'])) {
            $coins[] = $item;
        }
    }

    // Platforms
    $stmt = $pdo->query("SELECT * FROM platforms ORDER BY sort_order ASC");
    $platforms = $stmt->fetchAll();

    $data = [
        'site_title' => $site_title,
        'site_description' => $site_description,
        'site_keywords' => $site_keywords,
        'gold_data' => $gold_data,
        'silver_data' => $silver_data,
        'coins' => $coins,
        'platforms' => $platforms
    ];

    return View::renderPage('home', $data);
});

// Example of a dynamic route
$router->add('/item/:symbol', function($params) {
    return View::renderPage('item_details', [
        'symbol' => $params['symbol'],
        'site_title' => 'جزئیات ' . $params['symbol']
    ]);
});
