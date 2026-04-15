<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$koneksi = mysqli_connect("localhost", "root", "", "iventaris_dokumen");

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

function audit_log($action, $document_id, $keterangan = "") {
    global $koneksi;
    if (isset($_SESSION['user_id'])) {
        $user_id = (int)$_SESSION['user_id'];
        $doc_id = $document_id ? (int)$document_id : "NULL";
        $keterangan = mysqli_real_escape_string($koneksi, $keterangan);
        $action = mysqli_real_escape_string($koneksi, $action);
        mysqli_query($koneksi, "INSERT INTO audit_logs (user_id, action, document_id, keterangan) VALUES ($user_id, '$action', $doc_id, '$keterangan')");
    }
}
?>