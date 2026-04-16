<?php
include __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// ── Handle POST: delete_attachment ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $post_action = $_POST['action'] ?? '';

    if ($post_action === 'delete_attachment') {
        $att_id = isset($_POST['att_id']) ? (int)$_POST['att_id'] : 0;
        $doc_id = isset($_POST['doc_id']) ? (int)$_POST['doc_id'] : 0;

        if ($att_id === 0) {
            echo json_encode(['status' => 'error', 'message' => 'ID lampiran tidak valid.']);
            exit;
        }

        // Ambil data lampiran & cek kepemilikan
        $q = mysqli_query($koneksi, "
            SELECT a.*, d.user_id FROM attachments a
            JOIN documents d ON a.document_id = d.id
            WHERE a.id = $att_id
        ");
        $att = mysqli_fetch_assoc($q);

        if (!$att) {
            echo json_encode(['status' => 'error', 'message' => 'Lampiran tidak ditemukan.']);
            exit;
        }

        if ($user_role === 'user' && $att['user_id'] != $user_id) {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
            exit;
        }

        // Hapus file fisik dari folder uploads/
        $file_path = __DIR__ . '/uploads/' . $att['nama_file'];
        if (file_exists($file_path) && is_file($file_path)) {
            unlink($file_path);
        }

        // Hapus record dari database
        mysqli_query($koneksi, "DELETE FROM attachments WHERE id = $att_id");

        // Catat audit log
        audit_log('Delete', $doc_id, "Menghapus lampiran: " . $att['nama_asli']);

        echo json_encode(['status' => 'success', 'message' => 'Lampiran berhasil dihapus.']);
        exit;
    }

    // Action POST tidak dikenal
    echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal.']);
    exit;
}
// ─────────────────────────────────────────────────────────────────────────────

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$action = isset($_GET['action']) && $_GET['action'] === 'download' ? 'Download' : 'View';

if ($id === 0) {
    die("File ID tidak valid.");
}

$query = mysqli_query($koneksi, "
    SELECT a.*, d.user_id, d.tanggal_upload, d.latitude, d.longitude
    FROM attachments a 
    JOIN documents d ON a.document_id = d.id 
    WHERE a.id = $id
");
$file = mysqli_fetch_assoc($query);

if (!$file) {
    die("File tidak ditemukan.");
}

$file_path = __DIR__ . '/uploads/' . $file['nama_file'];

if (!file_exists($file_path) || !is_file($file_path)) {
    die("File fisik tidak ditemukan.");
}

// Catat log
$keterangan = ($action === 'Download') ? "Mengunduh file: " : "Melihat file: ";
$keterangan .= $file['nama_asli'];
// Uncomment if audit_log function exists
audit_log($action, $id, $keterangan);

// Determine MIME type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

function get_address($lat, $lng) {
    if (empty($lat) || empty($lng)) return "Tidak diketahui";
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";
    $options = [
        "http" => [
            "header" => "User-Agent: InventarisDokumenApp/1.0\r\n",
            "timeout" => 3
        ]
    ];
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['display_name'])) {
            $parts = explode(', ', $data['display_name']);
            return implode(', ', array_slice($parts, 0, min(3, count($parts))));
        }
    }
    return "$lat, $lng"; // fallback
}

$alamat_lokasi = "Tidak diketahui";
$safe_lokasi = "";
if ($action === 'Download') {
    $alamat_lokasi = get_address($file['latitude'], $file['longitude']);
    $safe_lokasi = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $alamat_lokasi);
    $safe_lokasi = trim(preg_replace('/_+/', '_', $safe_lokasi), '_');
    $safe_lokasi = substr($safe_lokasi, 0, 60);
}

// Intercept for Watermarking Images if action is Download
if ($action === 'Download' && strpos($mime_type, 'image/') === 0 && function_exists('imagecreatefromjpeg')) {
    $img = null;
    if ($mime_type === 'image/jpeg' || $mime_type === 'image/jfif') {
        $img = @imagecreatefromjpeg($file_path);
    } elseif ($mime_type === 'image/png') {
        $img = @imagecreatefrompng($file_path);
    }
    
    if ($img !== false) {
        $width = imagesx($img);
        $height = imagesy($img);
        
        $tanggal_wm = "Tanggal: " . ($file['tanggal_upload'] ? date('d-m-Y H:i', strtotime($file['tanggal_upload'])) : '-');
        $lokasi_wm  = "Lokasi: " . $alamat_lokasi;
        $text1 = "Inventaris Dokumen Kegiatan";
        $text2 = "$tanggal_wm | $lokasi_wm";
        
        // Menghitung rasio scaling agar watermark proporsional (2.5% dari tinggi gambar)
        $target_font_height = max(15, intval($height * 0.025));
        $scale = $target_font_height / 15.0;
        
        $box_height_tmp = 45; // Tinggi dasar kotak sebelum di-scale
        $box_height_scaled = intval($box_height_tmp * $scale);
        
        // Gambar kotak hitam semi-transparan asli (agar full melintang)
        $box_color = imagecolorallocatealpha($img, 0, 0, 0, 60); 
        imagefilledrectangle($img, 0, $height - $box_height_scaled, $width, $height, $box_color);
        
        // Hitung lebar kanvas sementara untuk teks
        $char_width = 9; // GD Font 5 char width
        $req_tmp_width = max(strlen($text1), strlen($text2)) * $char_width + 30; 
        
        // Buat kanvas sementara transparan
        $tmp = imagecreatetruecolor($req_tmp_width, $box_height_tmp);
        imagealphablending($tmp, false);
        imagesavealpha($tmp, true);
        $transparent = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
        imagefilledrectangle($tmp, 0, 0, $req_tmp_width, $box_height_tmp, $transparent);
        imagealphablending($tmp, true); // Aktifkan blending untuk tesk
        
        // Tulis teks putih di kanvas sementara
        $text_color = imagecolorallocate($tmp, 255, 255, 255);
        imagestring($tmp, 5, 10, 5, $text1, $text_color);
        imagestring($tmp, 3, 10, 24, $text2, $text_color);
        
        // Copy & Scale teks ke gambar utama
        $dst_w = intval($req_tmp_width * $scale);
        $dst_h = $box_height_scaled;
        imagecopyresampled($img, $tmp, 10, $height - $box_height_scaled, 0, 0, $dst_w, $dst_h, $req_tmp_width, $box_height_tmp);
        imagedestroy($tmp);
        
        // Output file langsung
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . basename($file['nama_asli']) . '"');
        ob_clean();
        flush();
        
        if ($mime_type === 'image/jpeg' || $mime_type === 'image/jfif') {
            imagejpeg($img, null, 90);
        } else {
            imagepng($img);
        }
        imagedestroy($img);
        exit;
    }
}

header('Content-Type: ' . $mime_type);
header('Content-Length: ' . filesize($file_path));

if ($action === 'Download') {
    header('Content-Disposition: attachment; filename="' . basename($file['nama_asli']) . '"');
} else {
    // For view, inline display if browser supports it
    header('Content-Disposition: inline; filename="' . basename($file['nama_asli']) . '"');
}

// Clear output buffer and output file
ob_clean();
flush();
readfile($file_path);
exit;
?>