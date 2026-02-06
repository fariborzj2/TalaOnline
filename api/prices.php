<?php
/**
 * Prices History API Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

$response = [
    'gold' => [],
    'silver' => []
];

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

// If database is empty, fall back to static data for initial experience
if (empty($response['gold']) && file_exists(__DIR__ . '/../data/prices.json')) {
    $static_data = json_decode(file_get_contents(__DIR__ . '/../data/prices.json'), true);
    if ($static_data) {
        $response = $static_data;
    }
}

echo json_encode($response);
