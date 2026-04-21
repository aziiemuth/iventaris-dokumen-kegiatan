<?php
ob_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$search = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, trim($_GET['q'])) : '';
$folder_id = isset($_GET['folder']) ? (int) $_GET['folder'] : null;

$is_search_mode = !empty($search);
$active_folder_name = null;
$folders_data = null;

$user_filter = ""; // Semua user bisa melihat semua dokumen (arsip publik)

$limit = 10;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $limit;
$total_pages = 1;
$total_records = 0;

if ($is_search_mode) {
    $c_q = "SELECT COUNT(*) as total FROM documents d WHERE (d.judul_dokumen LIKE '%$search%' OR d.id IN (SELECT document_id FROM attachments WHERE nama_asli LIKE '%$search%' OR keterangan LIKE '%$search%')) $user_filter";
    $c_res = mysqli_query($koneksi, $c_q);
    $total_records = mysqli_fetch_assoc($c_res)['total'];
    $total_pages = ceil($total_records / $limit);

    $q = "SELECT d.*, fol.nama_folder as nama_folder_parent, u.username as uploader_name 
          FROM documents d 
          LEFT JOIN folders fol ON d.folder_id = fol.id 
          LEFT JOIN users u ON d.user_id = u.id 
          WHERE (d.judul_dokumen LIKE '%$search%' 
             OR d.id IN (SELECT document_id FROM attachments WHERE nama_asli LIKE '%$search%' OR keterangan LIKE '%$search%'))
             $user_filter
          ORDER BY d.tanggal_upload DESC
          LIMIT $limit OFFSET $offset";
    $files_data = mysqli_query($koneksi, $q);
} else {
    if ($folder_id) {
        $c_q = "SELECT COUNT(*) as total FROM documents d WHERE d.folder_id = $folder_id $user_filter";
        $c_res = mysqli_query($koneksi, $c_q);
        $total_records = mysqli_fetch_assoc($c_res)['total'];
        $total_pages = ceil($total_records / $limit);

        $q = "SELECT d.*, fol.nama_folder as nama_folder_parent, u.username as uploader_name 
              FROM documents d 
              LEFT JOIN folders fol ON d.folder_id = fol.id 
              LEFT JOIN users u ON d.user_id = u.id 
              WHERE d.folder_id = $folder_id $user_filter ORDER BY d.tanggal_upload DESC LIMIT $limit OFFSET $offset";
        $files_data = mysqli_query($koneksi, $q);

        $fq = mysqli_query($koneksi, "SELECT * FROM folders WHERE id = $folder_id");
        if (mysqli_num_rows($fq) > 0) {
            $active_folder = mysqli_fetch_assoc($fq);
            $active_folder_name = $active_folder['nama_folder'];
        }
    } else {
        $c_q = "SELECT COUNT(*) as total FROM documents d WHERE d.folder_id IS NULL $user_filter";
        $c_res = mysqli_query($koneksi, $c_q);
        $total_records = mysqli_fetch_assoc($c_res)['total'];
        $total_pages = ceil($total_records / $limit);

        $q = "SELECT d.*, NULL as nama_folder_parent, u.username as uploader_name FROM documents d LEFT JOIN users u ON d.user_id = u.id WHERE d.folder_id IS NULL $user_filter ORDER BY d.tanggal_upload DESC LIMIT $limit OFFSET $offset";
        $files_data = mysqli_query($koneksi, $q);
        $folders_data = mysqli_query($koneksi, "SELECT f.*, (SELECT COUNT(id) FROM documents WHERE folder_id = f.id) as total_files FROM folders f ORDER BY f.nama_folder ASC");
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Inventaris Dokumen Kegiatan</title>
    <meta name="description" content="Kelola arsip dokumen kegiatan instansi">
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
</head>

<body>

    <!-- Sidebar overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

    <!-- ========== SIDEBAR ========== -->
    <aside class="sidebar" id="sidebar">

        <!-- Brand -->
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

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="sidebar-label">Menu</div>

            <a href="dashboard.php"
                class="sidebar-link <?php echo (!$folder_id && !$is_search_mode) ? 'active' : ''; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" />
                    <rect x="14" y="3" width="7" height="7" />
                    <rect x="14" y="14" width="7" height="7" />
                    <rect x="3" y="14" width="7" height="7" />
                </svg>
                Dashboard
            </a>

            <a href="upload.php<?php echo $folder_id ? '?folder=' . $folder_id : ''; ?>" class="sidebar-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="16 16 12 12 8 16" />
                    <line x1="12" y1="12" x2="12" y2="21" />
                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3" />
                </svg>
                Upload Arsip
            </a>

            <a href="folder.php" class="sidebar-link">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                Kategori
            </a>

            <?php if ($user_role === 'admin'): ?>
                <div class="sidebar-label">Admin</div>

                <a href="users.php" class="sidebar-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Manajemen User
                </a>

                <a href="log_aktivitas.php" class="sidebar-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12" />
                    </svg>
                    Log Aktivitas
                </a>


            <?php endif; ?>

            <?php if ($folders_data_sidebar = mysqli_query($koneksi, "SELECT id, nama_folder FROM folders ORDER BY nama_folder ASC")): ?>
                <?php if (mysqli_num_rows($folders_data_sidebar) > 0): ?>
                    <div class="sidebar-label">Kategori</div>
                    <?php while ($sf = mysqli_fetch_assoc($folders_data_sidebar)): ?>
                        <a href="dashboard.php?folder=<?php echo $sf['id']; ?>"
                            class="sidebar-link <?php echo ($folder_id == $sf['id']) ? 'active' : ''; ?>"
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

        <!-- Bottom: user info + logout -->
        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <div class="sidebar-user-info">
                    <div class="name"><?php echo htmlspecialchars($username); ?></div>
                    <span class="role-badge <?php echo $user_role === 'admin' ? 'role-admin' : 'role-user'; ?>">
                        <?php echo $user_role; ?>
                    </span>
                </div>
            </div>
            <button class="sidebar-link" id="btn-logout" style="color:#f87171;">
                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <line x1="21" y1="12" x2="9" y2="12" />
                </svg>
                Keluar
            </button>
        </div>
    </aside>

    <!-- ========== MAIN WRAPPER ========== -->
    <div class="main-wrapper">

        <!-- Topbar: search only -->
        <header class="topbar">
            <!-- Hamburger (mobile only) -->
            <div class="topbar-left">
                <button class="hamburger-btn" id="hamburger-btn" onclick="openSidebar()" title="Buka menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
            </div>
            <!-- Search form -->
            <form method="GET" action="dashboard.php" class="topbar-search">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input type="text" name="q" class="search-input"
                    placeholder="Cari dokumen – judul, keterangan, atau nama file..."
                    value="<?php echo htmlspecialchars($search); ?>" autocomplete="off" id="search-input">
                <?php if ($is_search_mode): ?>
                    <a href="dashboard.php"
                        style="position:absolute;right:1rem;color:var(--danger);display:flex;align-items:center;"
                        title="Hapus pencarian">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="15" y1="9" x2="9" y2="15" />
                            <line x1="9" y1="9" x2="15" y2="15" />
                        </svg>
                    </a>
                <?php endif; ?>
            </form>
        </header>

        <!-- ========== LOGOUT CONFIRM MODAL ========== -->
        <div class="modal-overlay" id="logout-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div class="modal-box">
                <div class="modal-icon modal-icon-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none"
                        stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                        <polyline points="16 17 21 12 16 7" />
                        <line x1="21" y1="12" x2="9" y2="12" />
                    </svg>
                </div>
                <div class="modal-title" id="modal-title">Konfirmasi Keluar</div>
                <div class="modal-desc">Yakin ingin keluar dari sistem?</div>
                <div class="modal-actions">
                    <button id="btn-cancel-logout" class="btn btn-outline" onclick="closeLogoutModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                        Batal
                    </button>
                    <a href="logout.php" class="btn btn-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                        Ya, Keluar
                    </a>
                </div>
            </div>
        </div>

        <!-- ========== MAIN CONTENT ========== -->
        <main class="main-content">

            <!-- ========== MAIN CARD ========== -->
            <div class="card fade-in">

                <div class="app-header">
                    <div>
                        <h2>
                            <?php
                            if ($is_search_mode) {
                                echo '<i data-feather="search" style="width:20px;height:20px;vertical-align:middle;margin-right:0.4rem;"></i>';
                                echo 'Hasil: "' . htmlspecialchars($search) . '"';
                            } elseif ($active_folder_name) {
                                echo '<i data-feather="folder-open" style="width:20px;height:20px;vertical-align:middle;margin-right:0.4rem;"></i>';
                                echo htmlspecialchars($active_folder_name);
                            } else {
                                echo '<i data-feather="layout" style="width:20px;height:20px;vertical-align:middle;margin-right:0.4rem;"></i>';
                                echo 'Dashboard Arsip';
                            }
                            ?>
                        </h2>
                    </div>

                    <div class="actions-group">
                        <!-- Back button (inside folder or search) -->
                        <?php if ($active_folder_name || $is_search_mode): ?>
                            <a href="dashboard.php" class="btn btn-outline" title="Kembali ke halaman utama"
                                data-tooltip="Kembali ke root / semua kategori">
                                <i data-feather="arrow-left" style="width:15px;height:15px;"></i>
                                Kembali
                            </a>
                        <?php else: ?>
                            <!-- Create folder -->
                                <a href="folder.php" class="btn btn-outline"
                                    title="Kelola Kategori / Folder Kegiatan"
                                    data-tooltip="Kelola Kategori">
                                    <i data-feather="folder" style="width:15px;height:15px;"></i>
                                    Kategori
                                </a>
                        <?php endif; ?>

                        <?php if ($folder_id || $is_search_mode): ?>
                        <!-- Export ZIP button -->
                        <a href="export_zip.php?<?php echo $is_search_mode ? 'q=' . urlencode($search) : 'folder=' . $folder_id; ?>"
                            class="btn btn-outline" style="border-color:#3b82f6;color:#3b82f6;"
                            title="Download hanya file foto ke dalam format ZIP"
                            data-tooltip="Export Foto ke ZIP">
                            <i data-feather="archive" style="width:15px;height:15px;"></i>
                            Export Foto (ZIP)
                        </a>

                        <!-- Export button -->
                        <a href="export_doc.php?<?php echo $is_search_mode ? 'q=' . urlencode($search) : 'folder=' . $folder_id; ?>"
                            class="btn btn-outline" style="border-color:#10b981;color:#10b981;"
                            title="Download daftar dokumen ini ke format Word (.docx)"
                            data-tooltip="Export tabel ke Word (.docx)">
                            <i data-feather="file-text" style="width:15px;height:15px;"></i>
                            Export ke Word
                        </a>
                        <?php endif; ?>

                        <!-- Upload button (all roles) -->
                        <a href="upload.php<?php echo $folder_id ? '?folder=' . $folder_id : ''; ?>"
                            class="btn btn-primary" title="Upload dokumen arsip baru ke dalam sistem"
                            data-tooltip="Unggah arsip baru">
                            <i data-feather="upload-cloud" style="width:15px;height:15px;"></i>
                            Upload Arsip
                        </a>
                    </div>
                </div>

                <!-- ====== FOLDERS GRID (root only) ====== -->
                <?php if (!$is_search_mode && !$folder_id && $folders_data && mysqli_num_rows($folders_data) > 0): ?>
                    <div class="section-label">
                        <i data-feather="grid" style="width:13px;height:13px;"></i>
                        Kategori Kegiatan
                    </div>
                    <div class="folder-grid">
                        <?php while ($fold = mysqli_fetch_assoc($folders_data)): ?>
                            <a href="dashboard.php?folder=<?php echo $fold['id']; ?>" class="folder-item"
                                title="Buka kategori: <?php echo htmlspecialchars($fold['nama_folder']); ?>">
                                <i data-feather="folder" style="width:20px;height:20px;"></i>
                                <div class="folder-name">
                                    <?php echo htmlspecialchars($fold['nama_folder']); ?>
                                </div>
                                <span class="folder-badge"><?php echo $fold['total_files']; ?> file</span>
                            </a>
                        <?php endwhile; ?>
                    </div>
                    <div class="section-label">
                        <i data-feather="file-text" style="width:13px;height:13px;"></i>
                        Dokumen Tanpa Kategori
                    </div>
                <?php endif; ?>

                <!-- ====== FILES TABLE (folder or search mode) ====== -->
                <?php if ($folder_id || $is_search_mode): ?>
                    <div class="section-label">
                        <i data-feather="file" style="width:13px;height:13px;"></i>
                        <?php echo $is_search_mode ? 'Hasil Pencarian Dokumen' : 'Daftar Arsip dalam Kegiatan'; ?>
                    </div>
                <?php endif; ?>

                <?php
                $has_files = isset($files_data) && mysqli_num_rows($files_data) > 0;
                if (($folder_id || $is_search_mode) && !$has_files):
                    ?>
                    <div class="empty-state">
                        <i data-feather="inbox"
                            style="width:56px;height:56px;display:block;margin:0 auto 1rem;opacity:0.25;"></i>
                        <h3>Tidak Ada Dokumen</h3>
                        <p><?php echo $is_search_mode ? 'Tidak ada hasil untuk pencarian tersebut.' : 'Belum ada arsip dalam kategori ini.'; ?>
                        </p>
                        <?php if (!$is_search_mode): ?>
                            <a href="upload.php?folder=<?php echo $folder_id; ?>" class="btn btn-primary"
                                style="margin-top:1.25rem;">
                                <i data-feather="upload-cloud" style="width:15px;height:15px;"></i>
                                Upload Dokumen Pertama
                            </a>
                        <?php endif; ?>
                    </div>

                <?php elseif ($folder_id || $is_search_mode): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:35%">Judul Dokumen</th>
                                    <th style="width:18%">Kategori / Lokasi</th>
                                    <th style="width:27%">File Terlampir</th>
                                    <th style="width:20%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($d = mysqli_fetch_assoc($files_data)):
                                    $doc_id = $d['id'];
                                    $q_atts = mysqli_query($koneksi, "SELECT * FROM attachments WHERE document_id = $doc_id");
                                    $attachments_arr = [];
                                    while ($a = mysqli_fetch_assoc($q_atts))
                                        $attachments_arr[] = $a;

                                    $judul = !empty($d['judul_dokumen']) ? $d['judul_dokumen'] : 'Dokumen Tanpa Judul';
                                    $keterangan = !empty($d['keterangan']) ? $d['keterangan'] : '-';
                                    $tanggal = !empty($d['tanggal_upload']) ? date('d M Y', strtotime($d['tanggal_upload'])) : '-';
                                    ?>
                                    <tr>
                                        <!-- TITLE COLUMN -->
                                        <td style="vertical-align:top;">
                                            <div
                                                style="font-weight:700;color:var(--primary);margin-bottom:0.3rem;font-size:0.925rem;">
                                                <?php echo htmlspecialchars($judul); ?>
                                            </div>
                                            <div
                                                style="font-size:0.82rem;color:var(--text-muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:0.3rem; display:none;">
                                                <?php echo htmlspecialchars($keterangan); ?>
                                            </div>
                                            <div
                                                style="font-size:0.75rem;color:var(--text-light);display:flex;align-items:center;gap:0.3rem;">
                                                <i data-feather="clock" style="width:11px;height:11px;"></i>
                                                <?php echo $tanggal; ?>
                                                <span style="color:var(--border);">|</span>
                                                <i data-feather="user" style="width:11px;height:11px;"></i>
                                                <?php echo htmlspecialchars($d['uploader_name'] ?? 'Unknown'); ?>
                                            </div>
                                        </td>

                                        <!-- CATEGORY / LOCATION COLUMN -->
                                        <td style="vertical-align:top;">
                                            <?php if ($is_search_mode && $d['nama_folder_parent']): ?>
                                                <div class="category-badge"
                                                    style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.82rem;background:var(--primary-light);color:var(--primary);padding:0.25rem 0.6rem;border-radius:99px;font-weight:600;">
                                                    <i data-feather="folder" style="width:11px;height:11px;"></i>
                                                    <?php echo htmlspecialchars($d['nama_folder_parent']); ?>
                                                </div>
                                            <?php elseif ($d['folder_id']): ?>
                                                <span style="font-size:0.8rem;color:var(--text-muted);">Dalam folder</span>
                                            <?php else: ?>
                                                <span style="font-size:0.8rem;color:var(--text-light);font-style:italic;">Tanpa
                                                    kategori</span>
                                            <?php endif; ?>

                                            <?php if (!empty($d['latitude']) && !empty($d['longitude'])): ?>
                                                <div style="margin-top:0.5rem;">
                                                    <a href="https://maps.google.com/?q=<?php echo $d['latitude'] . ',' . $d['longitude']; ?>"
                                                        target="_blank" title="Lihat lokasi GPS dokumen ini di Google Maps"
                                                        style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.78rem;font-weight:600;color:var(--primary);background:var(--primary-light);padding:0.2rem 0.55rem;border-radius:99px;text-decoration:none;">
                                                        <i data-feather="map-pin" style="width:10px;height:10px;"></i>
                                                        Lihat Peta
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- FILES COLUMN -->
                                        <td style="vertical-align:top;">
                                            <?php if (count($attachments_arr) > 0): ?>
                                                <?php foreach ($attachments_arr as $att):
                                                    $bytes = max($att['ukuran'], 0);
                                                    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                                                    $pow = min($pow, 4);
                                                    $bytes /= (1 << (10 * $pow));
                                                    $fsize = round($bytes, 1) . ' ' . ['B', 'KB', 'MB', 'GB', 'TB'][$pow];
                                                    $ext = strtolower(pathinfo($att['nama_file'], PATHINFO_EXTENSION));
                                                    $is_img = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                    ?>
                                                    <div class="file-preview-card">
                                                        <?php if ($is_img): ?>
                                                            <div style="height:90px;border-radius:6px;overflow:hidden;background:#f1f5f9;margin-bottom:0.45rem;cursor:pointer;"
                                                                onclick="window.open('file_action.php?id=<?php echo $att['id']; ?>&action=view','_blank')"
                                                                title="Klik untuk melihat gambar">
                                                                <img src="uploads/<?php echo htmlspecialchars($att['nama_file']); ?>"
                                                                    style="width:100%;height:100%;object-fit:cover;" alt="Preview">
                                                            </div>
                                                        <?php endif; ?>
                                                        <div style="display:flex;align-items:flex-start;gap:0.4rem;margin-bottom:0.35rem;">
                                                            <i data-feather="<?php echo $is_img ? 'image' : 'file-text'; ?>"
                                                                style="width:12px;height:12px;color:var(--primary);flex-shrink:0;margin-top:0.2rem;"></i>
                                                            <div style="flex:1; overflow:hidden;">
                                                                <div style="font-size:0.78rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:120px;"
                                                                    title="<?php echo htmlspecialchars($att['nama_asli']); ?>">
                                                                    <?php echo htmlspecialchars(strlen($att['nama_asli']) > 18 ? substr($att['nama_asli'], 0, 16) . '…' : $att['nama_asli']); ?>
                                                                </div>
                                                                <?php if (!empty($att['keterangan'])): ?>
                                                                <div style="font-size:0.7rem; color:var(--text-muted); margin-top:0.2rem; line-height:1.2;">
                                                                    <?php echo htmlspecialchars($att['keterangan']); ?>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div style="display:flex;justify-content:space-between;align-items:center;">
                                                            <span
                                                                style="font-size:0.72rem;color:var(--text-light);"><?php echo $fsize; ?></span>
                                                            <div style="display:flex;gap:0.3rem;">
                                                                <a href="file_action.php?id=<?php echo $att['id']; ?>&action=view"
                                                                    target="_blank" class="btn btn-ghost btn-icon"
                                                                    title="Lihat / buka file <?php echo htmlspecialchars($att['nama_asli']); ?>"
                                                                    data-tooltip="Lihat file">
                                                                    <i data-feather="eye"
                                                                        style="width:13px;height:13px;color:var(--primary);"></i>
                                                                </a>
                                                                <a href="file_action.php?id=<?php echo $att['id']; ?>&action=download"
                                                                    class="btn btn-ghost btn-icon"
                                                                    title="Unduh file <?php echo htmlspecialchars($att['nama_asli']); ?>"
                                                                    data-tooltip="Unduh file">
                                                                    <i data-feather="download"
                                                                        style="width:13px;height:13px;color:var(--success);"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span style="font-size:0.8rem;color:var(--text-light);font-style:italic;">Tidak ada
                                                    lampiran</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- ACTIONS COLUMN -->
                                        <td style="vertical-align:top;">
                                            <div class="actions" style="flex-direction:column;gap:0.45rem;">
                                                <?php if ($user_role === 'admin' || ($user_role === 'user' && $d['user_id'] == $user_id)): ?>
                                                    <a href="edit.php?id=<?php echo $d['id']; ?>" class="btn btn-warning"
                                                        style="width:100%;justify-content:flex-start;text-decoration:none;"
                                                        title="Edit judul, keterangan, folder, lokasi GPS, dan tambah lampiran dokumen ini"
                                                        data-tooltip="Edit detail dokumen">
                                                        <i data-feather="edit-3" style="width:14px;height:14px;"></i>
                                                        Edit Dokumen
                                                    </a>

                                                    <button type="button" class="btn btn-danger"
                                                        style="width:100%;justify-content:flex-start;"
                                                        title="Hapus dokumen beserta semua lampirannya secara permanen"
                                                        data-tooltip="Hapus permanen dokumen"
                                                        onclick="confirmDelete(<?php echo $d['id']; ?>, '<?php echo htmlspecialchars(addslashes($judul)); ?>')">
                                                        <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                                        Hapus Dokumen
                                                    </button>
                                                <?php else: ?>
                                                    <span style="font-size:0.78rem;color:var(--text-light);font-style:italic;">Hanya
                                                        pemilik</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; // folder/search table ?>

                <!-- FILES ON ROOT (no folder, no search) -->
                <?php if (!$is_search_mode && !$folder_id): ?>
                    <?php
                    // Re-query since pointer has been used for folder count already
                    $q2 = "SELECT d.*, NULL as nama_folder_parent, u.username as uploader_name FROM documents d LEFT JOIN users u ON d.user_id = u.id WHERE d.folder_id IS NULL $user_filter ORDER BY d.tanggal_upload DESC LIMIT $limit OFFSET $offset";
                    $root_files = mysqli_query($koneksi, $q2);
                    if (mysqli_num_rows($root_files) === 0):
                        ?>
                        <div class="empty-state" style="padding:2rem 2rem 1rem;">
                            <p style="color:var(--text-light);font-size:0.875rem;">Belum ada dokumen di luar kategori. Pilih
                                atau buat kategori untuk mulai mengarsipkan.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:35%">Judul Dokumen</th>
                                        <th style="width:20%">Lokasi GPS</th>
                                        <th style="width:25%">File Terlampir</th>
                                        <th style="width:20%">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($d = mysqli_fetch_assoc($root_files)):
                                        $doc_id = $d['id'];
                                        $q_atts = mysqli_query($koneksi, "SELECT * FROM attachments WHERE document_id = $doc_id");
                                        $attachments_arr = [];
                                        while ($a = mysqli_fetch_assoc($q_atts))
                                            $attachments_arr[] = $a;
                                        $judul = !empty($d['judul_dokumen']) ? $d['judul_dokumen'] : 'Dokumen Tanpa Judul';
                                        $keterangan = !empty($d['keterangan']) ? $d['keterangan'] : '-';
                                        $tanggal = !empty($d['tanggal_upload']) ? date('d M Y', strtotime($d['tanggal_upload'])) : '-';
                                        ?>
                                        <tr>
                                            <td style="vertical-align:top;">
                                                <div
                                                    style="font-weight:700;color:var(--primary);margin-bottom:0.3rem;font-size:0.925rem;">
                                                    <?php echo htmlspecialchars($judul); ?>
                                                </div>
                                                <div
                                                    style="font-size:0.82rem;color:var(--text-muted);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:0.3rem; display:none;">
                                                    <?php echo htmlspecialchars($keterangan); ?>
                                                </div>
                                                <div
                                                    style="font-size:0.75rem;color:var(--text-light);display:flex;align-items:center;gap:0.3rem;">
                                                    <i data-feather="clock" style="width:11px;height:11px;"></i><?php echo $tanggal; ?>
                                                    <span style="color:var(--border);">|</span>
                                                    <i data-feather="user" style="width:11px;height:11px;"></i>
                                                    <?php echo htmlspecialchars($d['uploader_name'] ?? 'Unknown'); ?>
                                                </div>
                                            </td>
                                            <td style="vertical-align:top;">
                                                <?php if (!empty($d['latitude']) && !empty($d['longitude'])): ?>
                                                    <a href="https://maps.google.com/?q=<?php echo $d['latitude'] . ',' . $d['longitude']; ?>"
                                                        target="_blank"
                                                        style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.78rem;font-weight:600;color:var(--primary);background:var(--primary-light);padding:0.2rem 0.55rem;border-radius:99px;">
                                                        <i data-feather="map-pin" style="width:10px;height:10px;"></i>Lihat Peta
                                                    </a>
                                                <?php else: ?>
                                                    <span style="font-size:0.8rem;color:var(--text-light);font-style:italic;">–</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="vertical-align:top;">
                                                <?php if (count($attachments_arr) > 0): ?>
                                                    <?php foreach ($attachments_arr as $att):
                                                        $bytes = max($att['ukuran'], 0);
                                                        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                                                        $pow = min($pow, 4);
                                                        $bytes /= (1 << (10 * $pow));
                                                        $fsize = round($bytes, 1) . ' ' . ['B', 'KB', 'MB', 'GB', 'TB'][$pow];
                                                        $ext = strtolower(pathinfo($att['nama_file'], PATHINFO_EXTENSION));
                                                        $is_img = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                        ?>
                                                        <div class="file-preview-card">
                                                            <?php if ($is_img): ?>
                                                                <div style="height:80px;border-radius:6px;overflow:hidden;background:#f1f5f9;margin-bottom:0.4rem;cursor:pointer;"
                                                                    onclick="window.open('file_action.php?id=<?php echo $att['id']; ?>&action=view','_blank')">
                                                                    <img src="uploads/<?php echo htmlspecialchars($att['nama_file']); ?>"
                                                                        style="width:100%;height:100%;object-fit:cover;" alt="Preview">
                                                                </div>
                                                            <?php endif; ?>
                                                            <div style="display:flex;align-items:flex-start;gap:0.4rem;margin-bottom:0.3rem;">
                                                                <i data-feather="<?php echo $is_img ? 'image' : 'file-text'; ?>"
                                                                    style="width:11px;height:11px;color:var(--primary);margin-top:0.2rem;"></i>
                                                                <div style="flex:1; overflow:hidden;">
                                                                    <div style="font-size:0.77rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:110px;"
                                                                        title="<?php echo htmlspecialchars($att['nama_asli']); ?>">
                                                                        <?php echo htmlspecialchars(strlen($att['nama_asli']) > 16 ? substr($att['nama_asli'], 0, 14) . '…' : $att['nama_asli']); ?>
                                                                    </div>
                                                                    <?php if (!empty($att['keterangan'])): ?>
                                                                    <div style="font-size:0.7rem; color:var(--text-muted); margin-top:0.2rem; line-height:1.2;">
                                                                        <?php echo htmlspecialchars($att['keterangan']); ?>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                                                <span
                                                                    style="font-size:0.72rem;color:var(--text-light);"><?php echo $fsize; ?></span>
                                                                <div style="display:flex;gap:0.3rem;">
                                                                    <a href="file_action.php?id=<?php echo $att['id']; ?>&action=view"
                                                                        target="_blank" class="btn btn-ghost btn-icon" title="Lihat file"
                                                                        data-tooltip="Lihat file">
                                                                        <i data-feather="eye"
                                                                            style="width:13px;height:13px;color:var(--primary);"></i>
                                                                    </a>
                                                                    <a href="file_action.php?id=<?php echo $att['id']; ?>&action=download"
                                                                        class="btn btn-ghost btn-icon" title="Unduh file"
                                                                        data-tooltip="Unduh file">
                                                                        <i data-feather="download"
                                                                            style="width:13px;height:13px;color:var(--success);"></i>
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span style="font-size:0.8rem;color:var(--text-light);font-style:italic;">Tidak ada
                                                        lampiran</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="vertical-align:top;">
                                                <div class="actions" style="flex-direction:column;gap:0.45rem;">
                                                    <?php if ($user_role === 'admin' || ($user_role === 'user' && $d['user_id'] == $user_id)): ?>
                                                        <a href="edit.php?id=<?php echo $d['id']; ?>" class="btn btn-warning"
                                                            style="width:100%;justify-content:flex-start;text-decoration:none;"
                                                            title="Edit dokumen ini" data-tooltip="Edit detail dokumen">
                                                            <i data-feather="edit-3" style="width:14px;height:14px;"></i>
                                                            Edit Dokumen
                                                        </a>
                                                        <button type="button" class="btn btn-danger"
                                                            style="width:100%;justify-content:flex-start;"
                                                            title="Hapus dokumen ini secara permanen" data-tooltip="Hapus permanen"
                                                            onclick="confirmDelete(<?php echo $d['id']; ?>, '<?php echo htmlspecialchars(addslashes($judul)); ?>')">
                                                            <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                                            Hapus Dokumen
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- ====== PAGINATION ====== -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination"
                        style="display:flex;justify-content:center;align-items:center;margin-top:1.5rem;gap:0.5rem;padding-bottom:1rem;flex-wrap:wrap;">
                        <?php
                        $query_params = $_GET;

                        // Prev button
                        if ($page > 1) {
                            $query_params['page'] = $page - 1;
                            $prev_link = 'dashboard.php?' . http_build_query($query_params);
                            echo '<a href="' . htmlspecialchars($prev_link) . '" class="btn btn-outline" style="padding:0.4rem 0.8rem;border-color:var(--border);color:var(--text-main);"><i data-feather="chevron-left" style="width:14px;height:14px;margin-right:0.2rem;"></i> Prev</a>';
                        }

                        // Page numbers
                        // Limit page numbers to display to avoid too many buttons
                        $start_num = max(1, $page - 2);
                        $end_num = min($total_pages, $page + 2);

                        if ($start_num > 1) {
                            $query_params['page'] = 1;
                            $page_link = 'dashboard.php?' . http_build_query($query_params);
                            echo '<a href="' . htmlspecialchars($page_link) . '" class="btn btn-outline" style="padding:0.4rem 0.8rem;border-color:var(--border);color:var(--text-main);">1</a>';
                            if ($start_num > 2)
                                echo '<span style="color:var(--text-muted);padding:0 0.5rem;">...</span>';
                        }

                        for ($i = $start_num; $i <= $end_num; $i++) {
                            $query_params['page'] = $i;
                            $page_link = 'dashboard.php?' . http_build_query($query_params);
                            if ($i == $page) {
                                echo '<span class="btn btn-primary" style="padding:0.4rem 0.8rem;">' . $i . '</span>';
                            } else {
                                echo '<a href="' . htmlspecialchars($page_link) . '" class="btn btn-outline" style="padding:0.4rem 0.8rem;border-color:var(--border);color:var(--text-main);">' . $i . '</a>';
                            }
                        }

                        if ($end_num < $total_pages) {
                            if ($end_num < $total_pages - 1)
                                echo '<span style="color:var(--text-muted);padding:0 0.5rem;">...</span>';
                            $query_params['page'] = $total_pages;
                            $page_link = 'dashboard.php?' . http_build_query($query_params);
                            echo '<a href="' . htmlspecialchars($page_link) . '" class="btn btn-outline" style="padding:0.4rem 0.8rem;border-color:var(--border);color:var(--text-main);">' . $total_pages . '</a>';
                        }

                        // Next button
                        if ($page < $total_pages) {
                            $query_params['page'] = $page + 1;
                            $next_link = 'dashboard.php?' . http_build_query($query_params);
                            echo '<a href="' . htmlspecialchars($next_link) . '" class="btn btn-outline" style="padding:0.4rem 0.8rem;border-color:var(--border);color:var(--text-main);">Next <i data-feather="chevron-right" style="width:14px;height:14px;margin-left:0.2rem;"></i></a>';
                        }
                        ?>
                    </div>
                <?php endif; ?>

            </div><!-- /card -->
    </div><!-- /container -->


    <!-- ========== DELETE CONFIRM MODAL ========== -->
    <div class="modal-overlay" id="delete-modal" role="dialog" aria-modal="true">
        <div class="modal-box">
            <div class="modal-icon modal-icon-danger">
                <i data-feather="trash-2" style="width:28px;height:28px;color:var(--danger);"></i>
            </div>
            <div class="modal-title">Hapus Dokumen?</div>
            <div class="modal-desc">
                Anda akan menghapus dokumen:<br>
                <strong id="delete-doc-name" style="color:var(--text-main);"></strong><br><br>
                Semua file lampiran akan ikut terhapus.<br>
                <span style="color:var(--danger);font-weight:600;">Tindakan ini tidak bisa dikembalikan!</span>
            </div>
            <div class="modal-actions">
                <button class="btn btn-outline" onclick="closeDeleteModal()">
                    <i data-feather="x" style="width:15px;height:15px;"></i>
                    Batal
                </button>
                <a id="delete-confirm-link" href="#" class="btn btn-danger">
                    <i data-feather="trash-2" style="width:15px;height:15px;"></i>
                    Ya, Hapus
                </a>
            </div>
        </div>
    </div>

    <script>
        feather.replace();

        /* ===== LOGOUT MODAL ===== */
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


        /* ===== DELETE MODAL ===== */
        function confirmDelete(docId, docName) {
            document.getElementById('delete-doc-name').textContent = docName;
            document.getElementById('delete-confirm-link').href = 'hapus.php?id=' + docId;
            document.getElementById('delete-modal').classList.add('open');
            document.body.style.overflow = 'hidden';
            feather.replace();
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.remove('open');
            document.body.style.overflow = '';
        }

        document.getElementById('delete-modal').addEventListener('click', function (e) {
            if (e.target === this) closeDeleteModal();
        });

        /* ===== ESC key to close modals ===== */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
                closeDeleteModal();
            }
        });

        /* ===== Search auto-focus ===== */
        const si = document.getElementById('search-input');
        if (si) {
            si.addEventListener('input', function () {
                if (this.value.length > 2) {
                    clearTimeout(this._timer);
                    this._timer = setTimeout(() => this.form.submit(), 700);
                }
            });
        }

        /* ===== Sidebar toggle (mobile) ===== */
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

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeSidebar();
        });
    </script>

    </main><!-- /main-content -->
    </div><!-- /main-wrapper -->

</body>

</html>