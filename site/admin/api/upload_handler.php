<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../../includes/db.php';

if (!is_logged_in()) {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

reset($_FILES);
$temp = current($_FILES);

if (is_uploaded_file($temp['tmp_name'])) {
    // Sanitize input
    if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
        header("HTTP/1.1 400 Invalid file name.");
        return;
    }

    // Verify extension
    if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "png", "jpeg", "webp"))) {
        header("HTTP/1.1 400 Invalid extension.");
        return;
    }

    $uploaded_path = handle_upload($temp, 'uploads/editor/');

    if ($uploaded_path) {
        echo json_encode(array('location' => '../../' . $uploaded_path));
    } else {
        header("HTTP/1.1 500 Server Error");
    }
} else {
    header("HTTP/1.1 500 Server Error");
}
