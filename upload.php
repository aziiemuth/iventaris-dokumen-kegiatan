<?php
ob_start();
include __DIR__ . '/config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$upload_message = "";
$folders_query = mysqli_query($koneksi, "SELECT * FROM folders ORDER BY nama_folder ASC");

if (isset($_POST['upload'])) {
    $judul_dokumen = mysqli_real_escape_string($koneksi, $_POST['judul_dokumen']);
    $folder_id = empty($_POST['folder_id']) ? "NULL" : (int) $_POST['folder_id'];
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    $latitude = mysqli_real_escape_string($koneksi, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($koneksi, $_POST['longitude']);

    $query_doc = "INSERT INTO documents (judul_dokumen, folder_id, keterangan, tanggal_upload, user_id, latitude, longitude) 
                  VALUES ('$judul_dokumen', $folder_id, '$keterangan', '$tanggal', {$_SESSION['user_id']}, '$latitude', '$longitude')";

    if (mysqli_query($koneksi, $query_doc)) {
        $document_id = mysqli_insert_id($koneksi);
        $target_dir = __DIR__ . '/uploads/';
        if (!is_dir($target_dir))
            mkdir($target_dir, 0777, true);

        $berhasil_upload = 0;
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip', 'rar', '7z'];

        foreach ($_FILES['files']['name'] as $key => $nama_asli) {
            $tmp = $_FILES['files']['tmp_name'][$key];
            $ukuran = $_FILES['files']['size'][$key];
            if (!empty($nama_asli)) {
                // Security Check: Ekstensi
                $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_extensions)) {
                    continue; // Skip file berbahaya/tidak dikenali
                }

                // Security Check: Sanitize nama file
                $nama_asli_bersih = preg_replace('/[^A-Za-z0-9.\-_]/', '_', $nama_asli);
                $nama_file = time() . '_' . rand(100, 999) . '_' . $nama_asli_bersih;

                if (move_uploaded_file($tmp, $target_dir . $nama_file)) {
                    $ket_file = isset($_POST['file_keterangan'][$key]) ? mysqli_real_escape_string($koneksi, $_POST['file_keterangan'][$key]) : '';
                    $q_att = "INSERT INTO attachments (document_id, nama_file, nama_asli, ukuran, keterangan) 
                              VALUES ($document_id, '$nama_file', '$nama_asli', '$ukuran', '$ket_file')";
                    mysqli_query($koneksi, $q_att);
                    $berhasil_upload++;
                }
            }
        }

        audit_log('Upload', $document_id, "Mengunggah dokumen beserta $berhasil_upload lampiran.");
        $redirect_url = ($folder_id !== "NULL") ? "dashboard.php?folder=" . $folder_id : "dashboard.php";
        header("Location: " . $redirect_url);
        exit;
    } else {
        $upload_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Kesalahan Database: " . mysqli_error($koneksi) . "</div>";
    }
}

$prefill_folder = isset($_GET['folder']) ? (int) $_GET['folder'] : "";
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Arsip – Inventaris Dokumen</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <?php $active_page = 'upload'; ?>
</head>

<body>

    <?php include 'config/sidebar.php'; ?>

    <!-- ========== MAIN WRAPPER ========== -->
    <div class="main-wrapper">

        <!-- Mobile Topbar (khusus HP) -->
        <header class="topbar mobile-topbar">
            <div class="topbar-left">
                <button class="hamburger-btn" onclick="openSidebar()" title="Buka menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
            </div>
            <div style="font-weight:700; color:var(--primary); font-size:1.1rem; margin-left:0.5rem;">Inventaris Dokumen
            </div>
        </header>

        <!-- Logout Modal -->
        <div class="modal-overlay" id="logout-modal" role="dialog" aria-modal="true">
            <div class="modal-box">
                <div class="modal-icon modal-icon-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                        stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                </div>
                <div class="modal-title">Konfirmasi Keluar</div>
                <div class="modal-desc">Yakin ingin keluar dari sistem?</div>
                <div class="modal-actions">
                    <button class="btn btn-outline" onclick="closeLogoutModal()">Batal</button>
                    <a href="logout.php" class="btn btn-danger">Ya, Keluar</a>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div style="max-width: 900px; margin: 0 auto;">
                <div class="card form-card fade-in">

                    <!-- Header -->
                    <div class="app-header">
                        <div>
                            <h2>
                                <i data-feather="upload-cloud"
                                    style="width:20px;height:20px;vertical-align:middle;margin-right:0.4rem;"></i>
                                Upload Arsip Baru
                            </h2>
                        </div>
                    </div>

                    <?php echo $upload_message; ?>

                    <form action="" method="POST" enctype="multipart/form-data" id="upload-form">

                        <!-- Judul -->
                        <div class="form-group">
                            <label for="judul_dokumen">
                                <i data-feather="file-text" style="width:14px;height:14px;color:var(--primary);"></i>
                                Judul / Nama Dokumen
                                <span class="label-hint">Wajib diisi</span>
                            </label>
                            <input type="text" name="judul_dokumen" id="judul_dokumen" class="form-control" required
                                autocomplete="off" placeholder="Contoh: Laporan Kegiatan Q1 2024 – Divisi IT">
                        </div>

                        <!-- Keterangan Dokumen -->
                        <div class="form-group">
                            <label for="keterangan">
                                <i data-feather="edit-3" style="width:14px;height:14px;color:var(--primary);"></i>
                                Keterangan
                                <span class="label-hint">Opsional</span>
                            </label>
                            <input type="text" name="keterangan" id="keterangan" class="form-control" autocomplete="off"
                                placeholder="Tambahkan keterangan singkat tentang dokumen ini...">
                        </div>

                        <!-- Kategori -->
                        <div class="form-group">
                            <label for="folder_id">
                                <i data-feather="folder" style="width:14px;height:14px;color:var(--primary);"></i>
                                Kategori Kegiatan
                                <span class="label-hint">Opsional</span>
                            </label>
                            <select name="folder_id" id="folder_id" class="form-control">
                                <option value="">– Tanpa Kategori –</option>
                                <?php while ($f = mysqli_fetch_assoc($folders_query)): ?>
                                    <option value="<?php echo $f['id']; ?>" <?php echo $prefill_folder == $f['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($f['nama_folder']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Tanggal (Realtime) -->
                        <div class="form-group">
                            <label for="tanggal">
                                <i data-feather="calendar" style="width:14px;height:14px;color:var(--primary);"></i>
                                Tanggal Kegiatan / Upload
                            </label>
                            <!-- Input datetime-local ini akan di-prefill dengan JS -->
                            <input type="datetime-local" name="tanggal" id="tanggal" class="form-control" required>
                        </div>

                        <!-- GPS Coordinates -->
                        <div class="form-group">
                            <div class="gps-card">
                                <label
                                    style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem;font-weight:600;font-size:0.875rem;">
                                    <i data-feather="navigation"
                                        style="width:15px;height:15px;color:var(--primary);"></i>
                                    Koordinat Lokasi GPS
                                    <span class="label-hint">Opsional – ambil otomatis</span>
                                </label>
                                <div style="display:flex;gap:0.75rem;margin-bottom:0.85rem;">
                                    <div style="flex:1;">
                                        <label
                                            style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--text-light);letter-spacing:0.05em;margin-bottom:0.3rem;">Latitude</label>
                                        <input type="text" name="latitude" id="latitude" class="form-control"
                                            placeholder="Otomatis…" readonly
                                            style="background:#f8fafc;font-size:0.875rem;">
                                    </div>
                                    <div style="flex:1;">
                                        <label
                                            style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--text-light);letter-spacing:0.05em;margin-bottom:0.3rem;">Longitude</label>
                                        <input type="text" name="longitude" id="longitude" class="form-control"
                                            placeholder="Otomatis…" readonly
                                            style="background:#f8fafc;font-size:0.875rem;">
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline w-full" id="btn-lokasi"
                                    onclick="getLocation()"
                                    title="Klik untuk mengambil koordinat GPS posisi Anda saat ini"
                                    style="border-color:var(--primary);color:var(--primary);">
                                    <i data-feather="map-pin" style="width:15px;height:15px;"></i>
                                    Ambil Lokasi GPS Saat Ini
                                </button>
                                <div id="lokasi-loading"
                                    style="display:none;text-align:center;margin-top:0.75rem;font-size:0.84rem;color:var(--text-muted);">
                                    <i data-feather="loader" style="width:14px;height:14px;vertical-align:middle;"
                                        class="spin"></i>
                                    Mengambil koordinat, pastikan GPS & izin lokasi aktif…
                                </div>
                                <div id="lokasi-preview" style="display:none;margin-top:0.6rem;text-align:center;">
                                    <a href="#" id="lokasi-link" target="_blank"
                                        style="font-size:0.83rem;color:var(--primary);font-weight:600;text-decoration:underline;">
                                        <i data-feather="external-link" style="width:12px;height:12px;"></i>
                                        Verifikasi di Google Maps
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- File Upload -->
                        <div class="form-group">
                            <label for="file">
                                <i data-feather="paperclip" style="width:14px;height:14px;color:var(--primary);"></i>
                                File Dokumen
                                <span class="label-hint">Opsional – bisa multi-file</span>
                            </label>

                            <!-- Tombol Akses Kamera -->
                            <div class="camera-btn-wrapper" style="margin-bottom: 0.8rem;">
                                <button type="button" onclick="openCameraModal()" class="btn btn-outline w-full"
                                    style="justify-content:center; border-color:var(--primary); color:var(--primary); background:var(--primary-light);">
                                    <i data-feather="camera" style="width:16px;height:16px;margin-right:0.3rem;"></i>
                                    Ambil Foto
                                </button>
                            </div>

                            <div class="drop-zone" id="drop-zone">
                                <i data-feather="upload"
                                    style="width:36px;height:36px;color:var(--border-color);display:block;margin:0 auto 0.75rem;"></i>
                                <p
                                    style="font-size:0.95rem;color:var(--text-muted);margin-bottom:0.2rem;font-weight:600;">
                                    Pilih File Dokumen
                                </p>
                                <input type="file" name="files[]" id="file" multiple
                                    style="position:absolute;inset:0;opacity:0;cursor:pointer;"
                                    onchange="handleFilesAdded(this.files)">
                                <p style="font-size:0.78rem;color:var(--text-light);">
                                    (Bisa pilih lebih dari satu file/foto)
                                </p>
                                <div id="file-list"
                                    style="margin-top:1rem;display:flex;flex-wrap:wrap;gap:10px;justify-content:center;">
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                            <a href="dashboard.php<?php echo $prefill_folder ? '?folder=' . $prefill_folder : ''; ?>"
                                class="btn btn-outline" style="flex:0 0 auto;"
                                title="Batalkan dan kembali ke dashboard">
                                <i data-feather="x" style="width:15px;height:15px;"></i>
                                Batal
                            </a>
                            <button type="button" id="btn-submit-trigger" class="btn btn-success w-full"
                                style="flex:1;padding:0.9rem;font-size:1rem;font-weight:700;"
                                title="Simpan dokumen dan semua lampiran ke sistem arsip" onclick="confirmUpload()">
                                <i data-feather="check-circle" style="width:18px;height:18px;"></i>
                                Simpan ke Arsip
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div><!-- /main-content -->
    </div><!-- /main-wrapper -->

    <!-- Upload Confirm Modal -->
    <div class="modal-overlay" id="upload-modal" role="dialog" aria-modal="true">
        <div class="modal-box">
            <div class="modal-icon" style="background:var(--primary-light);">
                <i data-feather="upload-cloud" style="width:28px;height:28px;color:var(--primary);"></i>
            </div>
            <div class="modal-title">Simpan ke Arsip?</div>
            <div class="modal-desc">Yakin ingin menyimpan dokumen ini?</div>
            <div class="modal-actions">
                <button class="btn btn-outline" onclick="closeUploadModal()">
                    <i data-feather="x" style="width:15px;height:15px;"></i>
                    Periksa Lagi
                </button>
                <button id="upload-confirm-btn" class="btn btn-primary" onclick="doUpload()">
                    <i data-feather="upload-cloud" style="width:15px;height:15px;"></i>
                    Ya, Simpan
                </button>
            </div>
        </div>
    </div>

    <!-- WebRTC Camera Modal -->
    <div id="webrtc-modal" role="dialog" aria-modal="true"
        style="display:none; position:fixed; inset:0; z-index:10000; background:#000;">

        <!-- Video penuh layar -->
        <video id="webrtc-video" autoplay playsinline
            style="width:100%; height:100%; object-fit:cover; display:block;"></video>

        <!-- Tombol overlay kiri atas: Balik Kamera -->
        <button type="button" id="btn-flip-cam" onclick="flipCamera()" title="Ganti kamera depan / belakang" style="position:absolute; top:1rem; left:1rem; display:none;
                   background:rgba(0,0,0,0.5); color:#fff; border:1.5px solid rgba(255,255,255,0.4);
                   border-radius:999px; padding:0.5rem 1rem; font-size:0.85rem; font-weight:600;
                   cursor:pointer; backdrop-filter:blur(6px); display:flex; align-items:center; gap:0.4rem;">
            <i data-feather="refresh-cw" style="width:14px;height:14px;"></i> Balik Kamera
        </button>

        <!-- Tombol overlay kanan atas: Tutup -->
        <button type="button" onclick="closeCameraModal()" style="position:absolute; top:1rem; right:1rem;
                   background:rgba(0,0,0,0.5); color:#fff; border:1.5px solid rgba(255,255,255,0.4);
                   border-radius:50%; width:42px; height:42px; cursor:pointer;
                   display:flex; align-items:center; justify-content:center;
                   backdrop-filter:blur(6px);">
            <i data-feather="x" style="width:20px;height:20px;"></i>
        </button>

        <!-- Tombol Ambil Foto bawah tengah -->
        <div style="position:absolute; bottom:2rem; left:50%; transform:translateX(-50%);">
            <button type="button" onclick="snapPhoto()" style="background:#fff; color:#0f172a; border:none; border-radius:999px;
                       padding:0.85rem 2.5rem; font-size:1.05rem; font-weight:700;
                       cursor:pointer; display:flex; align-items:center; gap:0.5rem;
                       box-shadow:0 4px 20px rgba(0,0,0,0.4);">
                <i data-feather="camera" style="width:20px;height:20px;"></i> Ambil Foto
            </button>
        </div>
    </div>
    <canvas id="webrtc-canvas" style="display:none;"></canvas>

    <script>
        feather.replace();

        /* Tanggal Realtime Auto-fill */
        function isiTanggalSekarang() {
            const inputTgl = document.getElementById('tanggal');
            if (inputTgl) {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');

                inputTgl.value = `${year}-${month}-${day}T${hours}:${minutes}`;
            }
        }

        /* GPS */
        const btnLokasi = document.getElementById('btn-lokasi');
        const lokasiLoad = document.getElementById('lokasi-loading');
        const inputLat = document.getElementById('latitude');
        const inputLng = document.getElementById('longitude');
        const lokasiPreview = document.getElementById('lokasi-preview');
        const lokasiLink = document.getElementById('lokasi-link');

        function getLocation() {
            if (!navigator.geolocation) { alert("Geolocation tidak didukung browser ini."); return; }
            btnLokasi.style.display = 'none';
            lokasiLoad.style.display = 'block';
            navigator.geolocation.getCurrentPosition(showPosition, showError, { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 });
        }

        function showPosition(pos) {
            inputLat.value = pos.coords.latitude;
            inputLng.value = pos.coords.longitude;
            lokasiLoad.style.display = 'none';
            btnLokasi.style.display = 'block';
            btnLokasi.className = 'btn w-full';
            btnLokasi.style.cssText = 'background:#16a34a; color:#ffffff; border-color:#15803d; box-shadow:0 2px 8px rgba(22,163,74,0.45); font-weight:700; letter-spacing:0.02em;';
            btnLokasi.innerHTML = '<i data-feather="check-circle" style="width:16px;height:16px;"></i> ✓ Lokasi Berhasil Diambil!';
            lokasiLink.href = `https://maps.google.com/?q=${pos.coords.latitude},${pos.coords.longitude}`;
            lokasiPreview.style.display = 'block';
            feather.replace();
        }

        function showError(err) {
            lokasiLoad.style.display = 'none';
            btnLokasi.style.display = 'block';
            const msgs = { 1: 'Izin lokasi ditolak. Aktifkan izin lokasi di browser Anda.', 2: 'Informasi lokasi tidak tersedia. Pastikan GPS aktif.', 3: 'Waktu tunggu GPS habis. Coba lagi.' };
            alert("Gagal: " + (msgs[err.code] || err.message));
        }

        /* File preview & Accumulation */
        let accumulatedFiles = new DataTransfer();
        let fileDescriptions = [];

        function updateDesc(index, val) {
            fileDescriptions[index] = val;
        }

        function handleFilesAdded(files) {
            const fileInput = document.getElementById('file');

            // Tambahkan file baru ke data transfer
            for (let i = 0; i < files.length; i++) {
                // Mencegah duplikasi file dengan mengecek namanya
                let isDuplicate = false;
                for (let j = 0; j < accumulatedFiles.files.length; j++) {
                    if (accumulatedFiles.files[j].name === files[i].name) {
                        isDuplicate = true;
                        break;
                    }
                }
                if (!isDuplicate) {
                    accumulatedFiles.items.add(files[i]);
                    fileDescriptions.push("");
                }
            }

            // Update input element
            fileInput.files = accumulatedFiles.files;

            // Render tampilan
            renderAccumulatedFiles();
        }

        function removeFile(index) {
            let dt = new DataTransfer();
            for (let i = 0; i < accumulatedFiles.files.length; i++) {
                if (i !== index) {
                    dt.items.add(accumulatedFiles.files[i]);
                }
            }
            fileDescriptions.splice(index, 1);
            accumulatedFiles = dt;
            document.getElementById('file').files = accumulatedFiles.files;
            renderAccumulatedFiles();
        }

        function renderAccumulatedFiles() {
            const list = document.getElementById('file-list');
            const files = accumulatedFiles.files;

            list.innerHTML = ''; // reset content

            if (files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    let f = files[i];
                    let name = f.name;
                    let size = f.size;
                    let isImage = f.type.startsWith('image/');

                    // Konversi ukuran file
                    if (size > 1048576) { size = (size / 1048576).toFixed(1) + ' MB'; }
                    else if (size > 1024) { size = (size / 1024).toFixed(1) + ' KB'; }
                    else { size = size + ' B'; }

                    let itemDiv = document.createElement('div');
                    itemDiv.style.cssText = "width:130px; padding:0.5rem; background:rgba(255,255,255,0.05); border:1px solid var(--border-color); border-radius:var(--radius-md); text-align:center; position:relative; display:flex; flex-direction:column; align-items:center;";

                    // Tombol X untuk hapus
                    let removeBtn = document.createElement('button');
                    removeBtn.innerHTML = '&times;';
                    removeBtn.style.cssText = "position:absolute; top:-6px; right:-6px; width:20px; height:20px; border-radius:50%; background:var(--danger); color:white; border:none; cursor:pointer; font-size:14px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 4px rgba(0,0,0,0.2); z-index:10;";
                    removeBtn.onclick = function (e) {
                        e.preventDefault();
                        e.stopPropagation();
                        removeFile(i);
                    };
                    itemDiv.appendChild(removeBtn);

                    if (isImage) {
                        // Tampilkan thumbnail gambar dengan FileReader
                        let imgWrapper = document.createElement('div');
                        imgWrapper.style.cssText = "width:100%; height:60px; overflow:hidden; border-radius:var(--radius-sm); margin-bottom:0.4rem; background:#000;";
                        let img = document.createElement('img');
                        img.style.cssText = "width:100%; height:100%; object-fit:cover;";

                        let reader = new FileReader();
                        reader.onload = function (e) { img.src = e.target.result; };
                        reader.readAsDataURL(f);

                        imgWrapper.appendChild(img);
                        itemDiv.appendChild(imgWrapper);
                    } else {
                        // Tampilkan ikon file biasa
                        let iconWrapper = document.createElement('div');
                        iconWrapper.style.cssText = "width:100%; height:60px; display:flex; align-items:center; justify-content:center; background:var(--primary-light); color:var(--primary); border-radius:var(--radius-sm); margin-bottom:0.4rem;";
                        iconWrapper.innerHTML = '<i data-feather="file" style="width:24px;height:24px;"></i>';
                        itemDiv.appendChild(iconWrapper);
                    }

                    // Nama & ukuran (dipotong kalau panjang)
                    let textDiv = document.createElement('div');
                    let shortName = name.length > 15 ? name.substring(0, 13) + "…" : name;
                    textDiv.innerHTML = `<div style="font-size:0.7rem; font-weight:600; color:var(--text-main); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${name}">${shortName}</div>
                                 <div style="font-size:0.65rem; color:var(--text-light); margin-bottom: 0.3rem;">${size}</div>`;
                    itemDiv.appendChild(textDiv);

                    // Input Keterangan spesifik
                    let ketValue = (fileDescriptions[i] || '').replace(/"/g, '&quot;');
                    let ketDiv = document.createElement('div');
                    ketDiv.style.cssText = "width:100%; margin-top:auto;";
                    ketDiv.innerHTML = `<input type="text" name="file_keterangan[]" class="form-control" style="font-size:0.7rem; padding:0.3rem 0.5rem; height:auto; text-align:center;" placeholder="Keterangan..." value="${ketValue}" oninput="updateDesc(${i}, this.value)" autocomplete="off">`;
                    itemDiv.appendChild(ketDiv);

                    list.appendChild(itemDiv);
                }
                feather.replace();
            }
        }

        /* Drop zone highlight */
        const dropZone = document.getElementById('drop-zone');
        ['dragenter', 'dragover'].forEach(e => dropZone.addEventListener(e, (event) => {
            event.preventDefault(); // Mencegah browser membuka file
            dropZone.classList.add('dragover');
        }));
        ['dragleave', 'drop'].forEach(e => dropZone.addEventListener(e, (event) => {
            event.preventDefault(); // Mencegah browser membuka file
            dropZone.classList.remove('dragover');
        }));
        dropZone.addEventListener('drop', e => {
            e.preventDefault();
            handleFilesAdded(e.dataTransfer.files);
        });

        /* Upload confirm modal */
        function confirmUpload() {
            const judul = document.getElementById('judul_dokumen').value.trim();
            if (!judul) {
                document.getElementById('judul_dokumen').focus();
                document.getElementById('judul_dokumen').style.borderColor = 'var(--danger)';
                return;
            }
            document.getElementById('upload-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
            feather.replace();
        }

        function closeUploadModal() {
            document.getElementById('upload-modal').classList.remove('open');
            document.body.style.overflow = '';
        }

        function doUpload() {
            closeUploadModal();
            const btn = document.getElementById('btn-submit-trigger');
            const oriText = btn.innerHTML;
            btn.innerHTML = '<i data-feather="loader" style="width:18px;height:18px;" class="spin"></i> Mengunggah...';
            btn.classList.add('disabled');
            btn.style.pointerEvents = 'none';

            // Tambahkan input hidden agar kita tahu form ini disubmit
            const form = document.getElementById('upload-form');
            let hiddenAction = document.createElement('input');
            hiddenAction.type = 'hidden';
            hiddenAction.name = 'upload';
            form.appendChild(hiddenAction);

            setTimeout(() => { form.submit(); }, 150);
        }

        // Menjalankan fungsi isi tanggal otomatis saat halaman dimuat
        /* WebRTC Camera Logic */
        let videoStream = null;
        let currentFacing = 'user'; // akan diisi dari track.getSettings() yang aktual
        let allCameras = [];      // daftar semua kamera yang tersedia
        let camIndex = 0;       // indeks kamera aktif saat ini

        const videoElement = document.getElementById('webrtc-video');
        const canvasElement = document.getElementById('webrtc-canvas');
        const cameraModal = document.getElementById('webrtc-modal');

        function applyMirror() {
            // currentFacing berdasarkan facingMode AKTUAL dari track,
            // bukan dari parameter yang diminta ── sehingga akurat di PC maupun HP.
            // PC webcam biasanya mengembalikan 'user' atau string kosong → mirror tetap aktif.
            const shouldMirror = (currentFacing !== 'environment');
            videoElement.style.transform = shouldMirror ? 'scaleX(-1)' : 'scaleX(1)';
        }

        async function startCameraByIndex(index) {
            if (videoStream) {
                videoStream.getTracks().forEach(t => t.stop());
                videoStream = null;
            }
            camIndex = index;
            const cam = allCameras[index];
            try {
                // Gunakan deviceId exact agar tidak bisa salah pilih kamera
                const constraints = cam
                    ? { video: { deviceId: { exact: cam.deviceId } } }
                    : { video: true };
                videoStream = await navigator.mediaDevices.getUserMedia(constraints);
                videoElement.srcObject = videoStream;

                // Baca facingMode aktual dari kamera yang benar-benar terbuka
                const track = videoStream.getVideoTracks()[0];
                const settings = track ? track.getSettings() : {};
                currentFacing = settings.facingMode || 'user'; // default 'user' jika PC (tanpa label)
                applyMirror();
            } catch (err) {
                alert('Kamera tidak bisa dibuka: ' + err.message);
            }
        }

        async function openCameraModal() {
            cameraModal.style.display = 'block';
            feather.replace();
            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error("Akses kamera diblokir oleh browser. URL harus HTTPS atau Localhost agar fitur WebRTC diizinkan.");
                }
                // Minta izin dulu agar enumerateDevices bisa membaca label & ID
                const tmp = await navigator.mediaDevices.getUserMedia({ video: true });
                tmp.getTracks().forEach(t => t.stop());

                const devices = await navigator.mediaDevices.enumerateDevices();
                allCameras = devices.filter(d => d.kind === 'videoinput');

                // Sembunyikan tombol flip jika hanya ada 1 kamera (misal: PC)
                const flipBtn = document.getElementById('btn-flip-cam');
                if (flipBtn) {
                    flipBtn.style.display = allCameras.length > 1 ? 'flex' : 'none';
                }

                // Mulai dengan kamera indeks 0 (biasanya kamera depan / webcam default)
                await startCameraByIndex(0);
            } catch (err) {
                alert('Kamera tidak bisa dibuka: ' + err.message);
                cameraModal.style.display = 'none';
            }
        }

        async function flipCamera() {
            if (allCameras.length <= 1) return;
            const nextIndex = (camIndex + 1) % allCameras.length;
            const btn = document.getElementById('btn-flip-cam');
            if (btn) btn.disabled = true;
            await startCameraByIndex(nextIndex);
            if (btn) btn.disabled = false;
            feather.replace();
        }

        function closeCameraModal() {
            if (videoStream) {
                videoStream.getTracks().forEach(track => track.stop());
            }
            videoStream = null;
            currentFacing = 'user';
            camIndex = 0;
            cameraModal.style.display = 'none';
        }

        function snapPhoto() {
            if (!videoStream) return;

            // Set ukuran canvas menyamai resolusi bingkai video aslinya
            canvasElement.width = videoElement.videoWidth;
            canvasElement.height = videoElement.videoHeight;
            const ctx = canvasElement.getContext('2d');

            // Terapkan efek mirror pada kanvas sesuai kamera aktif (konsisten dgn applyMirror)
            const shouldMirror = (currentFacing !== 'environment');
            if (shouldMirror) {
                ctx.translate(canvasElement.width, 0);
                ctx.scale(-1, 1);
            }

            ctx.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);

            // Ubah gambar menjadi blob data (File)
            canvasElement.toBlob(blob => {
                if (!blob) return;
                const ext = "jpg";
                const fileName = "Jepretan_" + (new Date().toISOString().replace(/[:.-]/g, '')) + "." + ext;
                const file = new File([blob], fileName, { type: "image/jpeg" });

                // Panggil fungsi handleFilesAdded untuk mengakumulasi file
                handleFilesAdded([file]);

                closeCameraModal();
            }, "image/jpeg", 0.9);
        }

        document.addEventListener('DOMContentLoaded', function () {
            isiTanggalSekarang();
        });

        document.getElementById('upload-modal').addEventListener('click', function (e) {
            if (e.target === this) closeUploadModal();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeUploadModal();
        });

        /* Logout modal */
        document.getElementById('btn-logout').addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('logout-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
        });
        function closeLogoutModal() {
            document.getElementById('logout-modal').classList.remove('open');
            document.body.style.overflow = '';
        }
        document.getElementById('logout-modal').addEventListener('click', function (e) {
            if (e.target === this) closeLogoutModal();
        });

        /* Sidebar mobile */
        function openSidebar() {
            document.getElementById('sidebar').classList.add('open');
            document.getElementById('sidebar-overlay').classList.add('open');
            document.body.style.overflow = 'hidden';
        }
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.remove('open');
            document.body.style.overflow = '';
        }
    </script>
</body>

</html>