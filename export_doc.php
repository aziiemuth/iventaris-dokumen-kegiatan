<?php
include 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'];
$user_id   = $_SESSION['user_id'];
$search    = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, trim($_GET['q'])) : '';
$folder_id = isset($_GET['folder']) ? (int) $_GET['folder'] : null;

$is_search_mode = !empty($search);
$user_filter = ($user_role === 'user') ? " AND d.user_id = " . (int)$user_id : "";

if ($is_search_mode) {
    $q = "SELECT d.*, fol.nama_folder as nama_folder_parent 
          FROM documents d 
          LEFT JOIN folders fol ON d.folder_id = fol.id 
          WHERE (d.judul_dokumen LIKE '%$search%' 
             OR d.keterangan LIKE '%$search%' 
             OR d.id IN (SELECT document_id FROM attachments WHERE nama_asli LIKE '%$search%'))
             $user_filter
          ORDER BY d.tanggal_upload DESC";
    $title = "Laporan Pencarian: " . $search;
} else {
    if ($folder_id) {
        $q = "SELECT d.*, fol.nama_folder as nama_folder_parent 
              FROM documents d 
              LEFT JOIN folders fol ON d.folder_id = fol.id 
              WHERE d.folder_id = $folder_id $user_filter ORDER BY d.tanggal_upload DESC";
        $fq = mysqli_query($koneksi, "SELECT nama_folder FROM folders WHERE id = $folder_id");
        $fol = mysqli_fetch_assoc($fq);
        $title = "Laporan Kategori: " . ($fol ? $fol['nama_folder'] : 'Kategori');
    } else {
        $q = "SELECT d.*, NULL as nama_folder_parent FROM documents d WHERE d.folder_id IS NULL $user_filter ORDER BY d.tanggal_upload DESC";
        $title = "Laporan Dokumen Tanpa Kategori";
    }
}
$files_data = mysqli_query($koneksi, $q);

header("Content-Type: application/vnd.ms-word");
header("Cache-Control: no-cache, must-revalidate");
header("Content-Disposition: attachment; filename=\"Laporan_Dokumen_".date('Ymd_His').".doc\"");

echo "<html>";
echo "<head><meta charset='UTF-8'><title>Laporan Dokumen</title>
<style>
    body { font-family: Arial, sans-serif; font-size: 11pt; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; word-wrap: break-word; }
    th, td { border: 1px solid #000; padding: 8px; text-align: left; vertical-align: top; overflow-wrap: break-word; }
    th { background-color: #f2f2f2; font-weight: bold; }
    h2 { text-align: center; font-size: 16pt; margin-bottom: 20px; }
</style>
</head>";
echo "<body>";
echo "<h2>" . htmlspecialchars($title) . "</h2>";
echo "<p>Dicetak pada: " . date("d M Y H:i") . "</p>";

echo "<table>";
echo "<tr>
        <th width='5%'>No</th>
        <th width='15%'>Judul Dokumen</th>
        <th width='20%'>Keterangan</th>
        <th width='12%'>Tanggal</th>
        <th width='13%'>Kategori / Lokasi</th>
        <th width='35%'>File Terlampir</th>
      </tr>";

if (mysqli_num_rows($files_data) > 0) {
    $no = 1;
    while ($d = mysqli_fetch_assoc($files_data)) {
        
        // Cari attachments
        $doc_id = $d['id'];
        $q_atts = mysqli_query($koneksi, "SELECT nama_asli, nama_file FROM attachments WHERE document_id = $doc_id");
        $attachments = [];
        while ($a = mysqli_fetch_assoc($q_atts)) {
            $ext = strtolower(pathinfo($a['nama_file'], PATHINFO_EXTENSION));
            $is_img = in_array($ext, ['jpg','jpeg','png','gif','webp','jfif']);
            
            $text = "- " . htmlspecialchars($a['nama_asli']);
            if ($is_img) {
                $file_path = __DIR__ . '/uploads/' . $a['nama_file'];
                if (file_exists($file_path)) {
                    $img_data = base64_encode(file_get_contents($file_path));
                    $mime = ($ext === 'jpg' || $ext === 'jfif') ? 'jpeg' : $ext;
                    
                    // MS Word membutuhkan width DAN height terdefinisi pasti
                    // agar tidak menggunakan 'tinggi asli' file secara tidak sengaja
                    $size_info = @getimagesize($file_path);
                    $new_w = 110;
                    $new_h = 110;
                    if ($size_info && $size_info[0] > 0) {
                        $orig_w = $size_info[0];
                        $orig_h = $size_info[1];
                        $new_h = intval(($orig_h / $orig_w) * $new_w);
                    }

                    $src = 'data:image/' . $mime . ';base64,' . $img_data;
                    $text .= "<br><img src='$src' width='$new_w' height='$new_h' style='border:1px solid #aaa;'><br>";
                }
            }
            $attachments[] = $text;
        }
        $file_list = count($attachments) > 0 ? implode("<br><br>", $attachments) : "Tidak ada file";

        $kategori = "";
        if ($is_search_mode && $d['nama_folder_parent']) {
            $kategori = htmlspecialchars($d['nama_folder_parent']);
        } elseif ($d['folder_id']) {
            $kategori = "Dalam folder ini";
        } else {
            $kategori = "-";
        }

        $lokasi = (!empty($d['latitude']) && !empty($d['longitude'])) ? "<br>Lat: {$d['latitude']}<br>Lng: {$d['longitude']}" : "";

        echo "<tr>";
        echo "<td>" . $no++ . "</td>";
        echo "<td><b>" . htmlspecialchars(!empty($d['judul_dokumen']) ? $d['judul_dokumen'] : 'Tanpa Judul') . "</b></td>";
        echo "<td>" . htmlspecialchars(!empty($d['keterangan']) ? $d['keterangan'] : '-') . "</td>";
        echo "<td>" . (!empty($d['tanggal_upload']) ? date('d M Y H:i', strtotime($d['tanggal_upload'])) : '-') . "</td>";
        echo "<td>Kategori: " . $kategori . $lokasi . "</td>";
        echo "<td>" . $file_list . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6' style='text-align:center;'>Pencarian / Kategori ini tidak memiliki dokumen.</td></tr>";
}
echo "</table>";
echo "</body></html>";
?>
