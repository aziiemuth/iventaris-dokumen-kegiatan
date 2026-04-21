<?php
include 'config/koneksi.php';

// Atur Timezone ke WIB sesuai lokasi user
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$search = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, trim($_GET['q'])) : '';
$folder_id = isset($_GET['folder']) ? (int) $_GET['folder'] : null;

$is_search_mode = !empty($search);
$user_filter = "";

if ($is_search_mode) {
    $q = "SELECT d.id, d.judul_dokumen FROM documents d WHERE (d.judul_dokumen LIKE '%$search%' OR d.id IN (SELECT document_id FROM attachments WHERE nama_asli LIKE '%$search%' OR keterangan LIKE '%$search%')) $user_filter";
    $title = "Foto_Pencarian_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $search);
} else {
    if ($folder_id) {
        $q = "SELECT d.id, d.judul_dokumen FROM documents d WHERE d.folder_id = $folder_id $user_filter";
        $fq = mysqli_query($koneksi, "SELECT nama_folder FROM folders WHERE id = $folder_id");
        $fol = mysqli_fetch_assoc($fq);
        $name = $fol ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $fol['nama_folder']) : 'Kategori';
        $title = "Foto_Kategori_" . $name;
    } else {
        $q = "SELECT d.id, d.judul_dokumen FROM documents d WHERE d.folder_id IS NULL $user_filter";
        $title = "Foto_Tanpa_Kategori";
    }
}
$files_data = mysqli_query($koneksi, $q);

if (!class_exists('ZipArchive')) {
    die("Extension php_zip belum aktif. Silakan aktifkan di php.ini");
}

$zip = new ZipArchive();
$temp_file = tempnam(sys_get_temp_dir(), 'zip');
if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die("Gagal membuat file .zip");
}

$added_files = 0;
if (mysqli_num_rows($files_data) > 0) {
    while ($d = mysqli_fetch_assoc($files_data)) {
        $doc_id = $d['id'];
        $judul = !empty($d['judul_dokumen']) ? $d['judul_dokumen'] : 'Tanpa Judul';
        // Bersihkan nama judul agar aman menjadi nama folder di dalam zip
        $folder_zip = preg_replace('/[^a-zA-Z0-9_ \-]/', '_', $judul);
        
        $q_atts = mysqli_query($koneksi, "SELECT nama_asli, nama_file FROM attachments WHERE document_id = $doc_id");

        while ($a = mysqli_fetch_assoc($q_atts)) {
            $ext = strtolower(pathinfo($a['nama_file'], PATHINFO_EXTENSION));
            $is_img = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif']);

            if ($is_img) {
                $f_path = __DIR__ . '/uploads/' . $a['nama_file'];
                if (file_exists($f_path)) {
                    // Masukkan ke dalam folder berdasarkan judul dokumen
                    $zip->addFile($f_path, $folder_zip . '/' . $doc_id . '_' . $a['nama_asli']);
                    $added_files++;
                }
            }
        }
    }
}

// Jika tidak ada foto yang dimasukkan ke dalam zip
if ($added_files == 0) {
    $zip->addFromString('INFO.txt', 'Tidak ada file foto dalam kategori atau pencarian ini.');
}

$zip->close();

header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=\"" . $title . "_" . date('Ymd_His') . ".zip\"");
header("Content-Length: " . filesize($temp_file));
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
readfile($temp_file);
unlink($temp_file);
exit;
?>
