<?php
include __DIR__ . '/config/koneksi.php';

echo "Memulai migrasi multi-upload schema database...<br>";

// 1. Table documents
$query_documents = "
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul_dokumen VARCHAR(255),
    folder_id INT DEFAULT NULL,
    keterangan TEXT,
    user_id INT NULL,
    latitude VARCHAR(50) NULL,
    longitude VARCHAR(50) NULL,
    tanggal_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($koneksi, $query_documents) or die(mysqli_error($koneksi));

// 2. Table attachments
$query_attachments = "
CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    nama_file VARCHAR(255),
    nama_asli VARCHAR(255),
    ukuran INT,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
)";
mysqli_query($koneksi, $query_attachments) or die(mysqli_error($koneksi));

// 3. Move Data
$files_check = mysqli_query($koneksi, "SHOW TABLES LIKE 'files'");
if (mysqli_num_rows($files_check) > 0) {
    // transfer data to documents
    mysqli_query($koneksi, "INSERT INTO documents (id, judul_dokumen, folder_id, keterangan, user_id, latitude, longitude, tanggal_upload)
                            SELECT id, judul_dokumen, folder_id, keterangan, user_id, latitude, longitude, tanggal_upload FROM files") or die(mysqli_error($koneksi));

    // transfer fields to attachments
    mysqli_query($koneksi, "INSERT INTO attachments (document_id, nama_file, nama_asli, ukuran)
                            SELECT id, nama_file, nama_asli, ukuran FROM files") or die(mysqli_error($koneksi));

    // Rename old table
    mysqli_query($koneksi, "RENAME TABLE files TO files_old_backup") or die(mysqli_error($koneksi));
    echo "Data berhasil dipindahkan dan tabel lama dibackup.<br>";
} else {
    echo "Tabel files sudah tidak ada atau sudah di migrasi.<br>";
}

echo "Migrasi Selesai!";
?>