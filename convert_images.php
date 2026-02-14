<?php
$dir = 'site/assets/images/gold/';
$files = ['gold.png', 'nim.png', 'rob.png'];

foreach ($files as $file) {
    $input = $dir . $file;
    $output = $dir . pathinfo($file, PATHINFO_FILENAME) . '.webp';

    if (file_exists($input)) {
        $img = imagecreatefrompng($input);
        if ($img) {
            imagepalettetotruecolor($img);
            imagealphablending($img, true);
            imagesavealpha($img, true);
            imagewebp($img, $output, 85);
            imagedestroy($img);
            echo "Converted $file to webp\n";
        }
    }
}
?>
