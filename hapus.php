<?php
include 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Security: Check ownership if not admin
$check = mysqli_query($koneksi, "SELECT judul_dokumen, user_id FROM documents WHERE id = $id");
$doc = mysqli_fetch_assoc($check);

if (!$doc) {
    header("Location: dashboard.php");
    exit;
}

if ($user_role === 'user' && $doc['user_id'] != $user_id) {
    header("Location: dashboard.php?msg=access_denied");
    exit;
}

// Proceed to delete physical files
$target_dir = __DIR__ . '/uploads/';
$atts = mysqli_query($koneksi, "SELECT * FROM attachments WHERE document_id = $id");

while ($att = mysqli_fetch_assoc($atts)) {
    $file_path = $target_dir . $att['nama_file'];
    if (file_exists($file_path) && is_file($file_path)) {
        unlink($file_path);
    }
}

// Audit Log
audit_log('Delete', null, "Menghapus dokumen dan lampirannya: " . $doc['judul_dokumen']);

// Database delete (cascade manually if FK not set, but we handle it manually here)
mysqli_query($koneksi, "DELETE FROM attachments WHERE document_id = $id");
mysqli_query($koneksi, "DELETE FROM documents WHERE id = $id");

header("Location: dashboard.php?msg=deleted");
exit;
?>