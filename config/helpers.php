<?php
/**
 * Dapatkan path utama direktori upload.
 * Akan membuat folder jika belum ada.
 */
function get_upload_path($folder_id, $koneksi) {
    $base_dir = 'D:\Inventaris Dokumen';
    
    // Pastikan base directory ada
    if (!is_dir($base_dir)) {
        @mkdir($base_dir, 0777, true);
    }

    $kategori = "Tanpa Kategori";
    if (!empty($folder_id) && $folder_id !== "NULL") {
        $folder_id = (int) $folder_id;
        $q = mysqli_query($koneksi, "SELECT nama_folder FROM folders WHERE id = $folder_id");
        if ($f = mysqli_fetch_assoc($q)) {
            $kategori = $f['nama_folder'];
        }
    }

    // Bersihkan karakter aneh untuk nama folder windows (Sanitize)
    $kategori = preg_replace('/[<>:"\/\\\\|?*]/', '_', $kategori);
    
    $target_dir = $base_dir . DIRECTORY_SEPARATOR . $kategori . DIRECTORY_SEPARATOR;
    
    // Pastikan sub directory kategori ada
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0777, true);
    }
    
    return $target_dir;
}
?>
