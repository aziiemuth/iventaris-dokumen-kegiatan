<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$file = '1776830136_801_ttd.png'; // Known existing file
$path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $file;
$thumb_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'thumbs';

echo "Path: $path<br>\n";
echo "Exists: " . (file_exists($path) ? 'Yes' : 'No') . "<br>\n";

echo "Scan uploads dir:<br>\n";
$files = scandir(__DIR__ . DIRECTORY_SEPARATOR . 'uploads');
foreach ($files as $f) {
    if ($f === $file)
        echo "<b>MATCH: $f</b><br>\n";
    else
        echo "$f<br>\n";
}

if (file_exists($path)) {
    try {
        $src_img = @imagecreatefrompng($path);
        if (!$src_img) {
            echo "Failed to imagecreatefrompng<br>";
            $info = getimagesize($path);
            print_r($info);
        } else {
            echo "Success loading image. Size: " . imagesx($src_img) . "x" . imagesy($src_img) . "<br>";
            imagedestroy($src_img);
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage();
    }
}

if (!is_dir($thumb_dir)) {
    echo "Creating thumb dir...<br>";
    if (mkdir($thumb_dir, 0777, true)) {
        echo "Created successfully.<br>";
    } else {
        echo "Failed to create directory.<br>";
    }
} else {
    echo "Thumb dir exists.<br>";
}
?>