# Inventaris Dokumen Kegiatan

Sistem Pengarsipan & Grouping Dokumen Instansi berbasis Web. Aplikasi ini memudahkan pengelolaan dokumen kegiatan dengan fitur pengelompokan (kategori), pelacakan lokasi (geotagging), dan riwayat aktivitas (audit logs).

## Fitur Utama

- **Otentikasi Multi-Level**: Login untuk Admin dan User.
- **Manajemen Kategori**: Pengelompokan dokumen berdasarkan kategori kegiatan (khusus Admin).
- **Upload Multi-File**: Satu entry dokumen dapat memiliki banyak lampiran (attachments).
- **Preview Gambar**: Pratinjau langsung untuk file gambar yang diunggah.
- **Geotagging**: Pelacakan lokasi dokumen melalui koordinat Latitude dan Longitude dengan integrasi Google Maps.
- **Pencarian Pintar**: Mencari dokumen berdasarkan Judul, Keterangan, Lokasi, atau Nama File lampiran.
- **Audit Logs**: Mencatat setiap aktivitas user (upload, edit, hapus) untuk keamanan data.
- **Antarmuka Modern**: UI responsif menggunakan CSS modern dan Feather Icons.

## Teknologi yang Digunakan

- **Backend**: PHP (Native)
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, Vanilla CSS, Javascript
- **Icons**: [Feather Icons](https://feathericons.com/)

## Persyaratan Sistem

- PHP versi 7.4 ke atas.
- MySQL/MariaDB.
- Web Server (Apache/Nginx) seperti XAMPP atau Laragon.

## Instalasi

1. **Clone project** ke direktori web server Anda (misal: `C:\xampp\htdocs\iventaris-dokumen-kegiatan`).
2. **Buat database** baru di phpMyAdmin dengan nama `iventaris_dokumen`.
3. **Impor schema database** menggunakan file `iventaris-dokumen.sql`.
4. **Konfigurasi koneksi database** di file `config/koneksi.php`:
   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $db   = "iventaris_dokumen";
   ```
5. **Akses aplikasi** melalui browser di `http://localhost/iventaris-dokumen-kegiatan`.

## Akun Default (Demo)

| Role  | Username | Password |
| ----- | -------- | -------- |
| Admin | admin    | admin    |
| User  | user     | user     |

## Struktur Folder

- `/assets`: File CSS, JS, dan ikon.
- `/config`: File konfigurasi koneksi database.
- `/uploads`: Folder penyimpanan lampiran dokumen.
- `dashboard.php`: Halaman utama aplikasi.
- `upload.php`: Halaman untuk mengunggah arsip baru.
- `folder.php`: Manajemen kategori kegiatan.
- `log_aktivitas.php`: Halaman audit logs.

---

Dikembangkan untuk memudahkan pengelolaan dokumentasi kegiatan instansi secara digital.
