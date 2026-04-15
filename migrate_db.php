<?php
include __DIR__ . '/config/koneksi.php';

echo "Memulai migrasi database...<br>";

// 1. Modifikasi tabel users
mysqli_query($koneksi, "ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'user') DEFAULT 'user'");
mysqli_query($koneksi, "DELETE FROM users WHERE username IN ('editor', 'viewer')");
mysqli_query($koneksi, "INSERT IGNORE INTO users (username, password, role) VALUES ('user', 'user', 'user')");
echo "Tabel users berhasil diperbarui.<br>";

// 2. Modifikasi tabel files
// Abaikan error jika kolom sudah ada
mysqli_query($koneksi, "ALTER TABLE files ADD COLUMN user_id INT NULL AFTER ukuran");
mysqli_query($koneksi, "ALTER TABLE files ADD COLUMN latitude VARCHAR(50) NULL AFTER user_id");
mysqli_query($koneksi, "ALTER TABLE files ADD COLUMN longitude VARCHAR(50) NULL AFTER latitude");
echo "Tabel files berhasil diperbarui.<br>";

// 3. Update data files yang lama
mysqli_query($koneksi, "UPDATE files SET user_id = (SELECT id FROM users WHERE username='admin' LIMIT 1) WHERE user_id IS NULL");

echo "Migrasi Selesai! <a href='login.php'>Kembali ke Login</a>";
?>