<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'test');
define('DB_USER', 'root');
define('DB_PASS', '');
class MockPDO {
    public function setAttribute($a, $b) {}
    public function query($q) {
        return new MockStmt();
    }
}
class MockStmt {
    public function fetchAll() { return []; }
    public function fetch() { return false; }
}
$pdo = new MockPDO();
function get_setting($k, $d) { return $d; }
