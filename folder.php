<?php
ob_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_role = $_SESSION['user_role'];

$folder_message = "";

// Aksi Tambah Kategori
if (isset($_POST['buat'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_folder']);
    if (mysqli_query($koneksi, "INSERT INTO folders (nama_folder) VALUES ('$nama')")) {
        header("Location: folder.php?msg=created");
        exit;
    } else {
        $folder_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Gagal membuat kategori.</div>";
    }
}

// Aksi Edit Kategori
if (isset($_POST['edit']) && $user_role === 'admin') {
    $id_edit = (int) $_POST['folder_id'];
    $nama_edit = mysqli_real_escape_string($koneksi, $_POST['nama_folder_edit']);
    if (mysqli_query($koneksi, "UPDATE folders SET nama_folder = '$nama_edit' WHERE id = $id_edit")) {
        header("Location: folder.php?msg=edited");
        exit;
    } else {
        $folder_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Gagal mengubah nama kategori.</div>";
    }
}

// Aksi Hapus Kategori beserta seluruh isinya
if (isset($_POST['hapus']) && $user_role === 'admin') {
    $id_hapus = (int) $_POST['folder_id_hapus'];

    // Tarik list semua attachment fisik dari semua dokumen yang ada di dalam folder ini
    $q_atts = mysqli_query($koneksi, "SELECT a.nama_file FROM attachments a JOIN documents d ON a.document_id = d.id WHERE d.folder_id = $id_hapus");
    while ($att = mysqli_fetch_assoc($q_atts)) {
        $file_path = __DIR__ . '/uploads/' . $att['nama_file'];
        if (file_exists($file_path) && is_file($file_path)) {
            unlink($file_path);
        }
    }

    // Menghapus data dokumen di mana database akan melakukan cascade otomatis hapus attachments
    mysqli_query($koneksi, "DELETE FROM documents WHERE folder_id = $id_hapus");

    // Menghapus rekod foldernya itu sendiri
    if (mysqli_query($koneksi, "DELETE FROM folders WHERE id = $id_hapus")) {
        header("Location: folder.php?msg=deleted");
        exit;
    } else {
        $folder_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Gagal menghapus kategori.</div>";
    }
}

// Pesan Sukses
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created')
        $folder_message = "<div class='alert alert-success'><i data-feather='check-circle' style='width:16px;height:16px;'></i> Kategori berhasil dibuat.</div>";
    if ($_GET['msg'] === 'edited')
        $folder_message = "<div class='alert alert-success'><i data-feather='check-circle' style='width:16px;height:16px;'></i> Kategori berhasil diperbarui.</div>";
    if ($_GET['msg'] === 'deleted')
        $folder_message = "<div class='alert alert-success'><i data-feather='check-circle' style='width:16px;height:16px;'></i> Kategori beserta seluruh isinya berhasil dihapus permanen.</div>";
}

// Get all folders and count of their documents
$folders_data = mysqli_query($koneksi, "
    SELECT f.*, (SELECT COUNT(id) FROM documents WHERE folder_id = f.id) as total_docs 
    FROM folders f 
    ORDER BY f.nama_folder ASC
");

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori – Inventaris Dokumen</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <?php $active_page = 'folder'; ?>
</head>

<body>

    <?php include 'config/sidebar.php'; ?>

    <div class="main-wrapper">

        <!-- Mobile Topbar -->
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
            <div style="font-weight:700; color:var(--primary); font-size:1.1rem; margin-left:0.5rem;">Manajemen Kategori
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
            <div class="card fade-in">
                <div class="app-header" style="flex-wrap: wrap; gap: 1rem;">
                    <div>
                        <h2>
                            <i data-feather="folder"
                                style="width:20px;height:20px;vertical-align:middle;margin-right:0.4rem;"></i>
                            Manajemen Kategori
                        </h2>
                    </div>
                    <div class="actions-group">
                        <button onclick="openBuatModal()" class="btn btn-primary" title="Tambah kategori baru">
                            <i data-feather="folder-plus" style="width:15px;height:15px;"></i>
                            Tambah Kategori Baru
                        </button>
                    </div>
                </div>

                <?php echo $folder_message; ?>

                <div class="table-wrapper" style="margin-top:1.5rem;">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Kategori</th>
                                <th style="width:25%; text-align:center;">Jml Dokumen</th>
                                <th style="width:25%; text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($folders_data) > 0): ?>
                                <?php while ($f = mysqli_fetch_assoc($folders_data)): ?>
                                    <tr>
                                        <td style="font-weight:600; color:var(--primary); font-size:0.95rem;">
                                            <?php echo htmlspecialchars($f['nama_folder']); ?>
                                        </td>
                                        <td style="text-align:center;">
                                            <span class="badge"
                                                style="background:var(--primary-light); color:var(--primary); padding:0.25rem 0.6rem; border-radius:99px; font-weight:600; font-size:0.8rem;">
                                                <?php echo $f['total_docs']; ?> dokumen
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                                                <?php if ($user_role === 'admin'): ?>
                                                    <button
                                                        onclick="openEditModal(<?php echo $f['id']; ?>, '<?php echo addslashes(htmlspecialchars($f['nama_folder'])); ?>')"
                                                        class="btn btn-warning btn-icon" title="Ubah Nama">
                                                        <i data-feather="edit-2" style="width:14px;height:14px;"></i>
                                                    </button>
                                                    <button
                                                        onclick="openDeleteModal(<?php echo $f['id']; ?>, '<?php echo addslashes(htmlspecialchars($f['nama_folder'])); ?>', <?php echo $f['total_docs']; ?>)"
                                                        class="btn btn-danger btn-icon" title="Hapus Kategori">
                                                        <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span style="font-size:0.8rem;color:var(--text-light);font-style:italic;">Hanya
                                                        Admin</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3"
                                        style="text-align:center; padding: 2rem; color:var(--text-light); font-style:italic;">
                                        Belum ada kategori yang dibuat.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div><!-- /main-content -->
    </div><!-- /main-wrapper -->

    <!-- Buat Kategori Modal -->
    <div class="modal-overlay" id="buat-modal" role="dialog" aria-modal="true">
        <div class="modal-box">
            <div class="modal-icon" style="background:var(--primary-light);">
                <i data-feather="folder-plus" style="width:28px;height:28px;color:var(--primary);"></i>
            </div>
            <div class="modal-title">Tambah Kategori Baru</div>
            <form method="POST" action="folder.php" id="buat-form">
                <input type="hidden" name="buat" value="1">
                <div class="form-group" style="text-align:left; margin-top:1rem;">
                    <label for="nama_folder_buat"
                        style="font-weight:600; font-size:0.85rem; margin-bottom:0.4rem; display:block;">Nama Kategori
                        Baru</label>
                    <input type="text" name="nama_folder" id="nama_folder_buat" class="form-control" required
                        placeholder="Cth: Laporan Keuangan" autocomplete="off">
                </div>
                <div class="modal-actions" style="margin-top:1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeBuatModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-feather="save" style="width:15px;height:15px;"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Kategori Modal -->
    <div class="modal-overlay" id="edit-modal" role="dialog" aria-modal="true">
        <div class="modal-box">
            <div class="modal-icon" style="background:#fef3c7;">
                <i data-feather="edit-2" style="width:28px;height:28px;color:#d97706;"></i>
            </div>
            <div class="modal-title">Ubah Nama Kategori</div>
            <form method="POST" action="folder.php" id="edit-form">
                <input type="hidden" name="edit" value="1">
                <input type="hidden" name="folder_id" id="edit_folder_id" value="">
                <div class="form-group" style="text-align:left; margin-top:1rem;">
                    <label for="nama_folder_edit"
                        style="font-weight:600; font-size:0.85rem; margin-bottom:0.4rem; display:block;">Nama
                        Kategori</label>
                    <input type="text" name="nama_folder_edit" id="nama_folder_edit" class="form-control" required
                        autocomplete="off">
                </div>
                <div class="modal-actions" style="margin-top:1.5rem;">
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">Batal</button>
                    <button type="submit" class="btn btn-warning">
                        <i data-feather="save" style="width:15px;height:15px;"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Kategori Modal -->
    <div class="modal-overlay" id="delete-modal" role="dialog" aria-modal="true">
        <div class="modal-box">
            <div class="modal-icon modal-icon-danger">
                <i data-feather="alert-triangle" style="width:28px;height:28px;color:var(--danger);"></i>
            </div>
            <div class="modal-title">Hapus Kategori?</div>
            <div class="modal-desc" style="margin-bottom:1.2rem;">
                Anda akan menghapus kategori: <br>
                <strong id="delete-folder-name" style="color:var(--text-main);font-size:1.05rem;"></strong>
                <br><br>
                <span style="color:var(--danger);font-weight:700;">PERINGATAN KRITIS:</span><br>
                Ada <strong id="delete-folder-count">0</strong> dokumen di dalam kategori ini. <strong>SEMUA DOKUMEN DAN
                    FILE LAMPIRANNYA AKAN IKUT TERHAPUS PERMANEN!</strong>
            </div>
            <form method="POST" action="folder.php">
                <input type="hidden" name="hapus" value="1">
                <input type="hidden" name="folder_id_hapus" id="delete_folder_id" value="">
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">
                        <i data-feather="x" style="width:15px;height:15px;"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i data-feather="trash-2" style="width:15px;height:15px;"></i> Ya, Hapus Semua!
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        feather.replace();

        /* === Modals === */
        function openBuatModal() {
            document.getElementById('buat-modal').classList.add('open');
            setTimeout(() => document.getElementById('nama_folder_buat').focus(), 100);
        }
        function closeBuatModal() {
            document.getElementById('buat-modal').classList.remove('open');
        }

        function openEditModal(id, currentName) {
            document.getElementById('edit_folder_id').value = id;
            document.getElementById('nama_folder_edit').value = currentName;
            document.getElementById('edit-modal').classList.add('open');
            setTimeout(() => document.getElementById('nama_folder_edit').focus(), 100);
        }
        function closeEditModal() {
            document.getElementById('edit-modal').classList.remove('open');
        }

        function openDeleteModal(id, name, docCount) {
            document.getElementById('delete_folder_id').value = id;
            document.getElementById('delete-folder-name').innerText = name;
            document.getElementById('delete-folder-count').innerText = docCount;
            document.getElementById('delete-modal').classList.add('open');
        }
        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.remove('open');
        }

        /* Close Modals on Overlay Click / ESC */
        const modals = ['buat-modal', 'edit-modal', 'delete-modal'];
        modals.forEach(modalId => {
            document.getElementById(modalId).addEventListener('click', function (e) {
                if (e.target === this) {
                    this.classList.remove('open');
                }
            });
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                modals.forEach(id => document.getElementById(id).classList.remove('open'));
            }
        });



        /* Clear URL parameters so alerts don't show up on refresh/history browsing */
        if (window.history.replaceState) {
            const url = new URL(window.location);
            if (url.searchParams.has('msg')) {
                url.searchParams.delete('msg');
                window.history.replaceState({ path: url.href }, '', url.href);
            }
        }
    </script>

    <script src="assets/script.js"></script>
</body>

</html>