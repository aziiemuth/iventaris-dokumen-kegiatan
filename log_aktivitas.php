<?php
ob_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Pagination logic
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $limit;

// Count total records for pagination
$total_query = "SELECT COUNT(*) as total FROM audit_logs";
$total_result = mysqli_query($koneksi, $total_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $limit);

$query = "SELECT al.*, u.username, d.judul_dokumen 
          FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          LEFT JOIN documents d ON al.document_id = d.id 
          ORDER BY al.created_at DESC
          LIMIT $limit OFFSET $offset";
$logs = mysqli_query($koneksi, $query);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas – Inventaris Dokumen</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <?php $active_page = 'log'; ?>
</head>

<body>

    <?php include 'config/sidebar.php'; ?>

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
                <div class="card fade-in">
                    <div class="app-header">
                        <div>
                            <h2>
                                <i data-feather="activity"
                                    style="width:22px;height:22px;vertical-align:middle;margin-right:0.4rem;"></i>
                                Rekam Jejak Aktivitas
                            </h2>
                            <p class="subtitle" style="display:none;">
                            </p>
                        </div>
                        <div class="actions-group" style="margin-bottom:0;">
                            <div
                                style="font-size:0.875rem;color:var(--text-muted);background:var(--bg-body);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:0.5rem 0.9rem;display:flex;align-items:center;gap:0.5rem;">
                                <i data-feather="shield" style="width:14px;height:14px;color:var(--primary);"></i>
                                <strong>
                                    <?php echo number_format($total_records, 0, ',', '.'); ?>
                                </strong>&nbsp;entri
                                log
                            </div>
                        </div>
                    </div>

                    <!-- Legend badges -->
                    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1.5rem;">
                        <span class="badge badge-upload"><i data-feather="upload" style="width:10px;height:10px;"></i>
                            Upload</span>
                        <span class="badge badge-download"><i data-feather="download"
                                style="width:10px;height:10px;"></i>
                            Download</span>
                        <span class="badge badge-view"><i data-feather="eye" style="width:10px;height:10px;"></i>
                            View</span>
                        <span class="badge badge-edit"><i data-feather="edit" style="width:10px;height:10px;"></i>
                            Edit</span>
                        <span class="badge badge-delete"><i data-feather="trash" style="width:10px;height:10px;"></i>
                            Delete</span>
                        <span style="font-size:0.78rem;color:var(--text-light);align-self:center;margin-left:0.25rem;">←
                            Keterangan jenis aktivitas</span>
                    </div>

                    <?php if (mysqli_num_rows($logs) === 0): ?>
                        <div class="empty-state">
                            <i data-feather="clock"
                                style="width:56px;height:56px;display:block;margin:0 auto 1rem;opacity:0.25;"></i>
                            <h3>Belum Ada Log</h3>
                            <p>Aktivitas pengguna akan muncul di sini setelah ada interaksi dengan sistem.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-wrapper">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width:17%">Waktu</th>
                                        <th style="width:15%">Pengguna</th>
                                        <th style="width:12%">Aktivitas</th>
                                        <th style="width:25%">Dokumen Terkait</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($logs)):
                                        $badge_class = 'badge-view';
                                        switch (strtolower($row['action'])) {
                                            case 'download':
                                                $badge_class = 'badge-download';
                                                break;
                                            case 'upload':
                                                $badge_class = 'badge-upload';
                                                break;
                                            case 'edit':
                                                $badge_class = 'badge-edit';
                                                break;
                                            case 'delete':
                                                $badge_class = 'badge-delete';
                                                break;
                                        }
                                        $icon_map = ['upload' => 'upload', 'download' => 'download', 'view' => 'eye', 'edit' => 'edit-3', 'delete' => 'trash-2'];
                                        $icon = $icon_map[strtolower($row['action'])] ?? 'activity';
                                        ?>
                                        <tr>
                                            <td>
                                                <div style="font-size:0.875rem;font-weight:600;color:var(--text-main);">
                                                    <?php echo date('d M Y', strtotime($row['created_at'])); ?>
                                                </div>
                                                <div style="font-size:0.78rem;color:var(--text-light);">
                                                    <?php echo date('H:i:s', strtotime($row['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display:flex;align-items:center;gap:0.5rem;">
                                                    <div
                                                        style="width:30px;height:30px;background:var(--primary-light);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.75rem;font-weight:700;color:var(--primary);flex-shrink:0;">
                                                        <?php echo strtoupper(substr($row['username'] ?? 'S', 0, 1)); ?>
                                                    </div>
                                                    <span style="font-weight:600;font-size:0.875rem;">
                                                        <?php echo htmlspecialchars($row['username'] ?? 'Sistem'); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $badge_class; ?>">
                                                    <i data-feather="<?php echo $icon; ?>" style="width:10px;height:10px;"></i>
                                                    <?php echo htmlspecialchars($row['action']); ?>
                                                </span>
                                            </td>
                                            <td style="font-size:0.875rem;">
                                                <?php
                                                if ($row['judul_dokumen']) {
                                                    echo '<span style="font-weight:600;">' . htmlspecialchars($row['judul_dokumen']) . '</span>';
                                                } elseif ($row['document_id']) {
                                                    echo '<em style="color:var(--text-light);">ID: ' . $row['document_id'] . ' (dihapus)</em>';
                                                } else {
                                                    echo '<span style="color:var(--text-light);">–</span>';
                                                }
                                                ?>
                                            </td>
                                            <td style="font-size:0.875rem;color:var(--text-muted);">
                                                <?php echo htmlspecialchars($row['keterangan'] ?: '–'); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination-container">
                            <div class="pagination-info">
                                Menampilkan
                                <?php echo ($offset + 1); ?> –
                                <?php echo min($offset + $limit, $total_records); ?> dari
                                <?php echo $total_records; ?> aktivitas
                                </div>
                                <div class="pagination">
                                    <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>" class="page-link" title="Halaman Sebelumnya">
                                        <i data-feather="chevron-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="page-link disabled">
                                        <i data-feather="chevron-left"></i>
                                                </span>
                                    <?php endif; ?>

                                    <span class="page-link active current-page-info">
                                                Hal. <?php echo $page; ?> dari
                                        <?php echo $total_pages; ?>
                                    </span>
                                
                        <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="page-link" title="Halaman Selanjutnya">
                                    <i data-feather="chevron-right"></i>
                                </a>
                        <?php else: ?>
                                <span class="page-link disabled">
                                    <i data-feather="chevron-right"></i>
                                </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
    <?php endif; ?>
    </div>
    </div>
    </div><!-- /main-content -->
    </div><!-- /main-wrapper -->

    <script>
                                feather.replace();
               docu             ment.getElementById('btn-logout').addEventListener('click', function (e) {
                                e.preventDefault();
                                document.getElementById('logout-modal').classList.add('open');
                                    document.body.style.overflow = 'hidden';
                                });
                     func       tion closeLogoutModal() {
                                document.getElementById('logout-modal').classList.remove('open');
                                    document.body.style.overflow = '';
                                }
                  docu          ment.getElementById('logout-modal').addEventListener('click', function (e) {
                                    if (e.target === this) closeLogoutModal();
                                    });
                 func           tion openSidebar() {
                                document.getElementById('sidebar').classList.add('open');
                                document.getElementById('sidebar-overlay').classList.add('open');
                                    document.body.style.overflow = 'hidden';
                                }
                      func      tion closeSidebar() {
                                document.getElementById('sidebar').classList.remove('open');
                                document.getElementById('sidebar-overlay').classList.remove('open');
                                    document.body.style.overflow = '';
                                    }
    </script>
</body>

</html>