<?php
// Script untuk mengompres dan menampilkan gambar thumbnail
// Berguna untuk menghemat bandwidth dan mempercepat loading dashboard
if (!isset($_GET['file'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$file = basename($_GET['file']);
$path = __DIR__ . '/uploads/' . $file;
$thumb_dir = __DIR__ . '/uploads/thumbs/';
$thumb_path = $thumb_dir . $file;

if (!file_exists($path)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

if (!is_dir($thumb_dir)) {
    mkdir($thumb_dir, 0777, true);
}

// Jika thumbnail sudah ada, kirim langsung
if (file_exists($thumb_path)) {
    // Cache headers
    header("Cache-Control: public, max-age=86400");
    header("Expires: " . gmdate('D, d M Y H:i:s', time() + 86400) . " GMT");
    $ext = strtolower(pathinfo($thumb_path, PATHINFO_EXTENSION));
    $mime = 'image/jpeg';
    if ($ext == 'png')
        $mime = 'image/png';
    elseif ($ext == 'gif')
        $mime = 'image/gif';
    elseif ($ext == 'webp')
        $mime = 'image/webp';
    header('Content-Type: ' . $mime);
    readfile($thumb_path);
    exit;
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$src_img = null;

if (in_array($ext, ['jpg', 'jpeg'])) {
    $src_img = @imagecreatefromjpeg($path);
} elseif ($ext == 'png') {
    $src_img = @imagecreatefrompng($path);
} elseif ($ext == 'gif') {
    $src_img = @imagecreatefromgif($path);
} elseif ($ext == 'webp') {
    $src_img = @imagecreatefromwebp($path);
}

if (!$src_img) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$width = imagesx($src_img);
$height = imagesy($src_img);
$thumb_width = 150;
$thumb_height = floor($height * ($thumb_width / $width));
$dst_img = imagecreatetruecolor($thumb_width, $thumb_height);

// Pertahankan transparansi (untuk PNG/WebP)
if ($ext == 'png' || $ext == 'webp' || $ext == 'gif') {
    imagealphablending($dst_img, false);
    imagesavealpha($dst_img, true);
    $transparent = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
    imagefilledrectangle($dst_img, 0, 0, $thumb_width, $thumb_height, $transparent);
}

imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);

// Simpan dan tampilkan
header("Cache-Control: public, max-age=86400");
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 86400) . " GMT");

if ($ext == 'png') {
    header('Content-Type: image/png');
    imagepng($dst_img, $thumb_path, 8);
    imagepng($dst_img);
} elseif ($ext == 'gif') {
    header('Content-Type: image/gif');
    imagegif($dst_img, $thumb_path);
    imagegif($dst_img);
} elseif ($ext == 'webp') {
    header('Content-Type: image/webp');
    imagewebp($dst_img, $thumb_path, 80);
    imagewebp($dst_img, null, 80);
} else {
    header('Content-Type: image/jpeg');
    imagejpeg($dst_img, $thumb_path, 80);
    imagejpeg($dst_img, null, 80);
}

imagedestroy($src_img);
imagedestroy($dst_img);
?>