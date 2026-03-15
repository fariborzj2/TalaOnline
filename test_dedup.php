<?php
$data = ['sender_name' => 'UserB', 'url' => '/post/43'];
$json1 = json_encode($data);

$data['_sender_id'] = 2;
$json2 = json_encode($data);

echo "json1: $json1\n";
echo "json2: $json2\n";
