<?php
/**
 * Dashboard API Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/navasan_service.php';

$navasan = new NavasanService($pdo);

// Check if we need to sync (e.g. every 10 minutes)
$last_sync = get_setting('last_sync_time', 0);
if (time() - $last_sync > 600) {
    if ($navasan->syncPrices()) {
        set_setting('last_sync_time', time());
    }
}

$items = $navasan->getDashboardData();

// Reconstruct the JSON structure expected by the frontend
$response = [
    'meta' => [
        'date' => date('Y-m-d H:i:s'), // Will be formatted by JS
        'site_title' => get_setting('site_title', 'طلا آنلاین')
    ],
    'summary' => [
        'gold' => [
            'current' => 0, 'change' => 0, 'change_percent' => 0, 'high' => 0, 'low' => 0
        ],
        'silver' => [
            'current' => 0, 'change' => 0, 'change_percent' => 0, 'high' => 0, 'low' => 0
        ]
    ],
    'platforms' => [],
    'coins' => []
];

// Fetch Gold & Silver for summary
foreach ($items as $item) {
    if ($item['symbol'] == '18ayar') {
        $response['summary']['gold'] = [
            'current' => (float)$item['price'],
            'change' => (float)$item['change'],
            'change_percent' => (float)$item['change_percent'],
            'high' => (float)$item['high'],
            'low' => (float)$item['low']
        ];
    }
    if ($item['symbol'] == 'silver') {
        $response['summary']['silver'] = [
            'current' => (float)$item['price'],
            'change' => (float)$item['change'],
            'change_percent' => (float)$item['change_percent'],
            'high' => (float)$item['high'],
            'low' => (float)$item['low']
        ];
    }

    // Categorize
    if ($item['category'] == 'gold' || $item['category'] == 'currency' || $item['category'] == 'coin') {
        $response['coins'][] = [
            'name' => $item['name'],
            'en_name' => $item['en_name'],
            'logo' => $item['logo'],
            'price' => (float)$item['price'],
            'change_percent' => (float)$item['change_percent']
        ];
    }
}

// Fetch Platforms
$stmt = $pdo->query("SELECT * FROM platforms ORDER BY sort_order ASC");
$platforms = $stmt->fetchAll();
foreach ($platforms as $p) {
    $response['platforms'][] = [
        'name' => $p['name'],
        'en_name' => $p['en_name'],
        'logo' => $p['logo'],
        'buy_price' => (float)$p['buy_price'],
        'sell_price' => (float)$p['sell_price'],
        'fee' => (float)$p['fee'],
        'status' => $p['status'],
        'link' => $p['link']
    ];
}

echo json_encode($response);
