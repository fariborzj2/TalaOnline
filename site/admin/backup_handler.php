<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../../includes/db.php';
check_login();

$action = $_GET['action'] ?? '';

if ($action === 'export') {
    export_database($pdo);
} elseif ($action === 'import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_import($pdo);
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

function handle_import($pdo) {
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        header('Location: settings.php?error=upload');
        exit;
    }

    $sql = file_get_contents($_FILES['backup_file']['tmp_name']);

    try {
        $pdo->beginTransaction();
        // Simple splitter - might need something more robust for complex SQL,
        // but for this app it should be fine.
        $queries = array_filter(array_map('trim', explode(";\n", $sql)));
        foreach ($queries as $query) {
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }
        $pdo->commit();
        header('Location: settings.php?message=backup_imported');
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: settings.php?error=' . urlencode($e->getMessage()));
    }
    exit;
}
