<?php
/**
 * Prices History API Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

$symbol = $_GET['symbol'] ?? null;

$response = [];

if ($symbol) {
    // Fetch specific symbol history
    $response[$symbol] = [];
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT price, date FROM prices_history WHERE symbol = ? ORDER BY date ASC");
        $stmt->execute([$symbol]);
        $history = $stmt->fetchAll();

        foreach ($history as $row) {
            $response[$symbol][] = [
                'date' => $row['date'],
                'price' => (float)$row['price']
            ];
        }
    }
} else {
    // Default backward compatibility for gold and silver
    $response = [
        'gold' => [],
        'silver' => []
    ];

    if ($pdo) {
        // Fetch Gold (18ayar) history
        $stmt = $pdo->prepare("SELECT price, date FROM prices_history WHERE symbol = ? ORDER BY date ASC");
        $stmt->execute(['18ayar']);
        $gold_history = $stmt->fetchAll();

        foreach ($gold_history as $row) {
            $response['gold'][] = [
                'date' => $row['date'],
                'price' => (float)$row['price']
            ];
        }

        // Fetch Silver history
        $stmt->execute(['silver']);
        $silver_history = $stmt->fetchAll();

        foreach ($silver_history as $row) {
            $response['silver'][] = [
                'date' => $row['date'],
                'price' => (float)$row['price']
            ];
        }
    }
}

// Mock data for demonstration if DB is empty
if ($pdo) {
    $itemCount = $pdo->query("SELECT COUNT(*) FROM prices_history")->fetchColumn();
} else {
    $itemCount = 0;
}

if ($itemCount == 0) {
    $symbols = $symbol ? [$symbol] : ['gold', 'silver', '18ayar', 'rob', 'sekeh'];
    foreach ($symbols as $s) {
        if (empty($response[$s])) {
            $basePrice = 1000000;
            if ($s == '18ayar' || $s == 'gold') $basePrice = 19000000;
            if ($s == 'sekeh') $basePrice = 190000000;
            if ($s == 'rob') $basePrice = 50000000;

            for ($i = 30; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $price = $basePrice + rand(-500000, 500000);
                $response[$s][] = ['date' => $date, 'price' => $price];
            }
        }
    }
}

echo json_encode($response);
