<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
check_login();

$action = $_GET['action'] ?? '';

if ($action === 'export') {
    export_database($pdo);
} elseif ($action === 'init_import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    init_import();
} elseif ($action === 'execute_step' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    execute_step($pdo);
}

function export_database($pdo) {
    $tables = [];
    $result = $pdo->query("SHOW TABLES");
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sql = "-- Database Backup\n";
    $sql .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        // Drop table
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";

        // Create table
        $res = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $sql .= $res['Create Table'] . ";\n\n";

        // Insert data
        $res = $pdo->query("SELECT * FROM `$table` ");
        while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
            $keys = array_map(function($k) { return "`$k`"; }, array_keys($row));
            $values = array_map(function($v) use ($pdo) {
                if ($v === null) return 'NULL';
                return $pdo->quote($v);
            }, array_values($row));

            $sql .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
        $sql .= "\n";
    }

    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i') . '.sql"');
    echo $sql;
    exit;
}

function init_import() {
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'خطا در آپلود فایل']);
        exit;
    }

    $sql = file_get_contents($_FILES['backup_file']['tmp_name']);
    // Split by semicolon followed by newline to be relatively safe with our own exports
    $queries = array_filter(array_map('trim', explode(";\n", $sql)));

    $_SESSION['import_queries'] = $queries;
    $_SESSION['import_total'] = count($queries);
    $_SESSION['import_current'] = 0;

    echo json_encode([
        'success' => true,
        'total' => $_SESSION['import_total']
    ]);
    exit;
}

function execute_step($pdo) {
    if (!isset($_SESSION['import_queries'])) {
        echo json_encode(['success' => false, 'error' => 'جلسه کاری یافت نشد']);
        exit;
    }

    $batch_size = 50;
    $queries = $_SESSION['import_queries'];
    $current = $_SESSION['import_current'];
    $total = $_SESSION['import_total'];

    $batch = array_slice($queries, $current, $batch_size);

    try {
        foreach ($batch as $query) {
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }

        $new_current = $current + count($batch);
        $_SESSION['import_current'] = $new_current;

        $done = ($new_current >= $total);
        if ($done) {
            unset($_SESSION['import_queries']);
            unset($_SESSION['import_total']);
            unset($_SESSION['import_current']);
        }

        echo json_encode([
            'success' => true,
            'current' => $new_current,
            'total' => $total,
            'done' => $done
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
