<?php
// Script untuk mengompres dan menampilkan gambar thumbnail
// Berguna untuk menghemat bandwidth dan mempercepat loading dashboard
error_reporting(0);
@ini_set('display_errors', 0);

if (!isset($_GET['file'])) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$file = basename($_GET['file']);
$uploads_dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$path = $uploads_dir . DIRECTORY_SEPARATOR . $file;
$thumb_dir = $uploads_dir . DIRECTORY_SEPARATOR . 'thumbs';
$thumb_path = $thumb_dir . DIRECTORY_SEPARATOR . $file;

if (!file_exists($path)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Fungsi untuk mengirim file asli sebagai fallback
function send_original($file_path)
{
    if (ob_get_length())
        ob_clean();
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    $mime = isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream';

    header("Content-Type: " . $mime);
    header("Content-Length: " . filesize($file_path));
    header("Cache-Control: public, max-age=86400");
    readfile($file_path);
    exit;
}

if (!is_dir($thumb_dir)) {
    @mkdir($thumb_dir, 0777, true);
}

// Jika thumbnail sudah ada, kirim langsung
if (file_exists($thumb_path)) {
    if (ob_get_length())
        ob_clean();
    header("Cache-Control: public, max-age=86400");
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

// Jika bukan gambar yang bisa diproses GD, kirim aslinya
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
    send_original($path);
}

// Coba buat thumbnail
$src_img = null;
try {
    if (in_array($ext, ['jpg', 'jpeg'])) {
        $src_img = @imagecreatefromjpeg($path);
    } elseif ($ext == 'png') {
        $src_img = @imagecreatefrompng($path);
    } elseif ($ext == 'gif') {
        $src_img = @imagecreatefromgif($path);
    } elseif ($ext == 'webp') {
        $src_img = @imagecreatefromwebp($path);
    }
} catch (Exception $e) {
    $src_img = null;
}

if (!$src_img) {
    send_original($path);
}

$width = imagesx($src_img);
$height = imagesy($src_img);

// Minimal size check - jika sangat kecil tidak perlu thumb
if ($width <= 200 || $height <= 200) {
    imagedestroy($src_img);
    send_original($path);
}

$thumb_width = 200;
$thumb_height = floor($height * ($thumb_width / $width));
$dst_img = @imagecreatetruecolor($thumb_width, $thumb_height);

if (!$dst_img) {
    imagedestroy($src_img);
    send_original($path);
}

// Pertahankan transparansi
if ($ext == 'png' || $ext == 'webp' || $ext == 'gif') {
    imagealphablending($dst_img, false);
    imagesavealpha($dst_img, true);
    $transparent = imagecolorallocatealpha($dst_img, 255, 255, 255, 127);
    imagefilledrectangle($dst_img, 0, 0, $thumb_width, $thumb_height, $transparent);
}

imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);

// Simpan untuk next cache
if ($ext == 'png') {
    @imagepng($dst_img, $thumb_path, 7);
} elseif ($ext == 'gif') {
    @imagegif($dst_img, $thumb_path);
} elseif ($ext == 'webp') {
    @imagewebp($dst_img, $thumb_path, 70);
} else {
    @imagejpeg($dst_img, $thumb_path, 75);
}

// Tampilkan sekarang
if (ob_get_length())
    ob_clean();

$mime_out = 'image/jpeg';
if ($ext == 'png')
    $mime_out = 'image/png';
elseif ($ext == 'gif')
    $mime_out = 'image/gif';
elseif ($ext == 'webp')
    $mime_out = 'image/webp';

header("Content-Type: " . $mime_out);
header("Cache-Control: public, max-age=86400");

if ($ext == 'png')
    @imagepng($dst_img);
elseif ($ext == 'gif')
    @imagegif($dst_img);
elseif ($ext == 'webp')
    @imagewebp($dst_img, null, 70);
else
    @imagejpeg($dst_img, null, 75);

imagedestroy($src_img);
imagedestroy($dst_img);
exit;
?>