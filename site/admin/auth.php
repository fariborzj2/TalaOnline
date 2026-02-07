<?php
session_start();

function check_login() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
    return true;
}

function is_logged_in() {
    return isset($_SESSION['admin_id']);
}
?>
