<?php
ob_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_role = $_SESSION['user_role'];
$username = $_SESSION['username'];

// Ensure only admin can access
if ($user_role !== 'admin') {
    header("Location: dashboard.php?msg=access_denied");
    exit;
}

$msg = isset($_GET['msg']) ? $_GET['msg'] : '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    if ($action === 'generate_dummy') {
        // Generate 15 categories related to ISP RT RW NET
        $kategori_prefixes = [
            'Pemasangan Baru',
            'Maintenance ODP',
            'Tagihan Bulanan',
            'Izin Lingkungan',
            'Perangkat ONU',
            'Kabel Dropcore',
            'Router Pelanggan',
            'Splicing FO',
            'Marketing ISP',
            'Legalitas RT-RW',
            'Site Survey',
            'Monitoring NOC',
            'Gangguan Teknis',
            'Upgrade Bandwidth',
            'Inventaris Alat'
        ];

        $inserted_docs = 0;
        foreach ($kategori_prefixes as $k_idx => $kat) {
            $folder_name = "$kat"; // User requested short names
            $stmt = $koneksi->prepare("INSERT INTO folders (nama_folder) VALUES (?)");
            $stmt->bind_param("s", $folder_name);
            $stmt->execute();
            $folder_id = $stmt->insert_id;

            for ($i = 1; $i <= 20; $i++) {
                $doc_title = "Data $kat Ke-$i";
                $doc_desc = "Dokumen simulasi untuk kegiatan $kat (ISP RT RW NET). Pelaksanaan teknis ke-$i.";
                $date_up = date('Y-m-d H:i:s', strtotime("-" . rand(0, 30) . " days"));

                $sql_doc = "INSERT INTO documents (folder_id, user_id, judul_dokumen, keterangan, tanggal_upload, latitude, longitude) 
                            VALUES ('$folder_id', '{$_SESSION['user_id']}', '$doc_title', '$doc_desc', '$date_up', NULL, NULL)";
                mysqli_query($koneksi, $sql_doc);
                $inserted_docs++;
            }
        }

        audit_log('Generate Dummy', null, "Admin membuat 15 kategori ISP dan 300 dokumen dummy.");
        header("Location: tools.php?msg=dummy_success");
        exit;
    }

    if ($action === 'clear_data') {
        // Find and delete all attachment files physically
        $atts = mysqli_query($koneksi, "SELECT * FROM attachments");
        $target_dir = __DIR__ . '/uploads/';
        while ($att = mysqli_fetch_assoc($atts)) {
            $file_path = $target_dir . $att['nama_file'];
            if (file_exists($file_path) && is_file($file_path)) {
                unlink($file_path);
            }
        }

        // Delete rows using DELETE (can't use TRUNCATE if we want to retain constraints, though DELETE is fine)
        mysqli_query($koneksi, "DELETE FROM attachments");
        mysqli_query($koneksi, "DELETE FROM documents");
        mysqli_query($koneksi, "DELETE FROM folders");

        // Optional: Reset auto increment
        mysqli_query($koneksi, "ALTER TABLE attachments AUTO_INCREMENT = 1");
        mysqli_query($koneksi, "ALTER TABLE documents AUTO_INCREMENT = 1");
        mysqli_query($koneksi, "ALTER TABLE folders AUTO_INCREMENT = 1");

        audit_log('Clear Data', null, "Admin membersihkan semua data folder, dokumen, dan file lampiran dari database !!");
        header("Location: tools.php?msg=clear_success");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools – Inventaris Dokumen Kegiatan</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body>

    <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

    <!-- ========== SIDEBAR ========== -->
    <aside class="sidebar" id="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="brand-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="21 8 21 21 3 21 3 8" />
                    <rect x="1" y="3" width="22" height="5" />
                    <line x1="10" y1="12" x2="14" y2="12" />
                </svg>
            </div>
            Inventaris Dokumen
        </a>

        <nav class="sidebar-nav">
            <div class="sidebar-label">Menu</div>
            <a href="dashboard.php" class="sidebar-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="14" y="14" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                </svg>
                Dashboard
            </a>

            <a href="upload.php" class="sidebar-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="16 16 12 12 8 16" />
                    <line x1="12" y1="12" x2="12" y2="21" />
                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3" />
                </svg>
                Upload Arsip
            </a>

            <a href="folder.php" class="sidebar-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                    <line x1="12" y1="11" x2="12" y2="17" />
                    <line x1="9" y1="14" x2="15" y2="14" />
                </svg>
                Buat Kategori
            </a>

            <div class="sidebar-label">Admin</div>

            <a href="log_aktivitas.php" class="sidebar-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                </svg>
                Log Aktivitas
            </a>

            <a href="tools.php" class="sidebar-link active">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                </svg>
                Tools
            </a>

            <?php if ($folders_data_sidebar = mysqli_query($koneksi, "SELECT id, nama_folder FROM folders ORDER BY nama_folder ASC")): ?>
                <?php if (mysqli_num_rows($folders_data_sidebar) > 0): ?>
                    <div class="sidebar-label">Kategori</div>
                    <?php while ($sf = mysqli_fetch_assoc($folders_data_sidebar)): ?>
                        <a href="dashboard.php?folder=<?php echo $sf['id']; ?>" class="sidebar-link"
                            title="<?php echo htmlspecialchars($sf['nama_folder']); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                            </svg>
                            <?php echo htmlspecialchars($sf['nama_folder']); ?>
                        </a>
                    <?php endwhile; ?>
                <?php endif; ?>
            <?php endif; ?>
        </nav>
        <div class="sidebar-bottom">
            <a href="logout.php" class="sidebar-link" style="color:var(--danger)">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Keluar
            </a>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="topbar">
            <div class="topbar-left">
                <button class="hamburger-btn"
                    onclick="document.getElementById('sidebar').classList.add('open'); document.getElementById('sidebar-overlay').classList.add('open');"><svg
                        xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg></button>
                <h3 style="margin-left: 1rem;">Sistem Tools (Admin)</h3>
            </div>
        </header>

        <main class="main-content">
            <?php if ($msg === 'dummy_success'): ?>
                <div class="alert alert-success"
                    style="margin-bottom:1rem;background:#dcfce7;color:#166534;padding:1rem;border-radius:8px;display:flex;align-items:center;gap:0.5rem;animation:fade-in 0.3s ease;">
                    <i data-feather="check-circle" style="width:18px;height:18px;"></i>
                    Data dummy (15 Kategori, @20 Dokumen) berhasil ditambahkan ke sistem!
                </div>
            <?php elseif ($msg === 'clear_success'): ?>
                <div class="alert alert-success"
                    style="margin-bottom:1rem;background:#dcfce7;color:#166534;padding:1rem;border-radius:8px;display:flex;align-items:center;gap:0.5rem;animation:fade-in 0.3s ease;">
                    <i data-feather="check-circle" style="width:18px;height:18px;"></i>
                    Seluruh data berhasil dihapus (Folders, Documents, Attachments) beserta fisiknya!
                </div>
            <?php endif; ?>

            <div style="display:flex; gap: 1.5rem; flex-wrap: wrap;">
                <!-- Dummy Data Generator -->
                <div class="card fade-in" style="flex:1; min-width: 300px;">
                    <div class="app-header"
                        style="border-bottom:1px solid var(--border); padding-bottom:1rem; margin-bottom:1.5rem;">
                        <div>
                            <h2><i data-feather="plus-square"
                                    style="width:20px;height:20px;vertical-align:middle;margin-right:0.4rem;"></i>
                                Generate Dummy Data</h2>
                        </div>
                    </div>
                    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom: 2rem;">
                        Buat data percobaan otomatis untuk mengecek kecepatan sistem, pagination, atau simulasi
                        penggunaan (Konteks: ISP RT RW NET).
                        Fitur ini akan menambah <strong>15 kategori</strong> dan <strong>20 dokumen</strong> di
                        setiap kategori (Total 300 dokumen).
                    </p>
                    <form method="POST" action="tools.php">
                        <input type="hidden" name="action" value="generate_dummy">
                        <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Buat data dummy sekarang? Data yang ada tidak akan terhapus.');">
                            <i data-feather="database" style="width:16px;height:16px;"></i>
                            Generate Dummy Sekarang
                        </button>
                    </form>
                </div>

                <!-- Clear All Data -->
                <div class="card fade-in" style="flex:1; min-width: 300px; border-color: #fee2e2;">
                    <div class="app-header"
                        style="border-bottom:1px solid #fee2e2; padding-bottom:1rem; margin-bottom:1.5rem;">
                        <div>
                            <h2 style="color:var(--danger)"><i data-feather="alert-triangle"
                                    style="width:20px;height:20px;vertical-align:middle;margin-right:0.4rem;"></i>Hapus
                                Semua Data</h2>
                        </div>
                    </div>
                    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom: 2rem;">
                        Fitur berbahaya. Opsi ini akan menghapus <strong>seluruh data</strong> di tabel Folders,
                        Documents, dan Attachments pada database anda, dan tidak hanya itu program akan
                        <strong>menghapus/unlink</strong> seluruh file fisik yang sudah diupload ke sistem.
                        <br><br>
                        Data yang sudah dihapus tidak bisa dikembalikan kecuali Anda punya backup SQL.
                    </p>
                    <!-- the button will open a custom modal for clear safety -->
                    <button type="button" class="btn btn-danger" onclick="openClearModal()">
                        <i data-feather="trash" style="width:16px;height:16px;"></i>
                        Hapus Semua Data (Bahaya)
                    </button>
                </div>
            </div>
        </main>
    </div>

    <!-- ========== CLEAR DATA CONFIRM MODAL ========== -->
    <div class="modal-overlay" id="clear-modal" role="dialog" aria-modal="true" style="z-index: 9999;">
        <div class="modal-box" style="border-top: 4px solid var(--danger);">
            <div class="modal-icon modal-icon-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                    stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path
                        d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                    <line x1="12" y1="9" x2="12" y2="13" />
                    <line x1="12" y1="17" x2="12.01" y2="17" />
                </svg>
            </div>
            <div class="modal-title">Konfirmasi TRUNCATE / DELETE ALL</div>
            <div class="modal-desc" style="color:var(--text-main);">
                Apakah Anda setuju ingin menghapus <strong>seluruh database dokumen</strong> & <strong>seluruh file
                    fisik</strong>?<br><br>
                Tindakan ini tidak bisa dibatalkan! Semua file akan di-unlinked dari direktori `uploads/`. Log aktivitas
                tidak akan dihapus.
            </div>

            <form method="POST" action="tools.php" style="margin-top: 1rem;">
                <input type="hidden" name="action" value="clear_data">

                <!-- Type validation for extra safety -->
                <div style="margin-bottom: 1.5rem; text-align: left;">
                    <label
                        style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:0.5rem; color:var(--text-main);">Ketik
                        <strong>HAPUS</strong> untuk melanjutkan:</label>
                    <input type="text" id="confirm-type" class="form-control" autocomplete="off" placeholder="..."
                        required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeClearModal()">
                        Batal
                    </button>
                    <button type="submit" id="btn-final-clear" class="btn btn-danger" disabled>
                        Ya, Hapus Semua Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        feather.replace();
        function closeSidebar() {
            document.getElementById('sidebar').classList.remove('open');
            document.getElementById('sidebar-overlay').classList.remove('open');
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeSidebar();
                closeClearModal();
            }
        });

        // Clear Modal logic
        function openClearModal() {
            document.getElementById('clear-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
            document.getElementById('confirm-type').value = '';
            document.getElementById('btn-final-clear').disabled = true;
        }

        function closeClearModal() {
            document.getElementById('clear-modal').classList.remove('open');
            document.body.style.overflow = '';
        }

        document.getElementById('confirm-type').addEventListener('input', function () {
            if (this.value === 'HAPUS') {
                document.getElementById('btn-final-clear').disabled = false;
            } else {
                document.getElementById('btn-final-clear').disabled = true;
            }
        });
    </script>
</body>

</html>