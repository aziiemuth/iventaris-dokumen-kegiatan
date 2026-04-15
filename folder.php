<?php
ob_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$folder_message = "";

if (isset($_POST['buat'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_folder']);
    if (mysqli_query($koneksi, "INSERT INTO folders (nama_folder) VALUES ('$nama')")) {
        header("Location: dashboard.php?msg=folder_created");
        exit;
    } else {
        $folder_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Gagal membuat kategori. Coba lagi.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Kategori – Inventaris Dokumen</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
<?php $active_page = 'folder'; ?>
</head>
<body>

<?php include 'config/sidebar.php'; ?>

<div class="main-wrapper">

<!-- Mobile Topbar (khusus HP) -->
<header class="topbar mobile-topbar">
    <div class="topbar-left">
        <button class="hamburger-btn" onclick="openSidebar()" title="Buka menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
    </div>
    <div style="font-weight:700; color:var(--primary); font-size:1.1rem; margin-left:0.5rem;">Inventaris Dokumen</div>
</header>

<!-- Logout Modal -->
<div class="modal-overlay" id="logout-modal" role="dialog" aria-modal="true">
    <div class="modal-box">
        <div class="modal-icon modal-icon-danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
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
                    <i data-feather="folder-plus" style="width:20px;height:20px;vertical-align:middle;margin-right:0.4rem;"></i>
                    Buat Kategori Baru
                </h2>
            </div>
        </div>

        <?php echo $folder_message; ?>

        <form method="POST" id="folder-form">
            <div class="form-group">
                <label for="nama_folder">
                    <i data-feather="folder" style="width:14px;height:14px;color:var(--primary);"></i>
                    Nama Kategori / Folder
                    <span class="label-hint">Wajib diisi</span>
                </label>
                <input type="text" name="nama_folder" id="nama_folder" class="form-control"
                       placeholder="Contoh: Laporan Keuangan 2024, Kegiatan HUT RI, dst."
                       required autocomplete="off" maxlength="100">
                <p style="font-size:0.78rem;color:var(--text-light);margin-top:0.4rem;">
                    Gunakan nama yang deskriptif agar mudah dicari.
                </p>
            </div>

            <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                <a href="dashboard.php" class="btn btn-outline"
                   title="Batalkan dan kembali ke dashboard">
                    <i data-feather="x" style="width:15px;height:15px;"></i>
                    Batal
                </a>
                <button type="button" id="btn-buat-trigger"
                        class="btn btn-primary w-full"
                        style="flex:1;padding:0.85rem;font-size:1rem;font-weight:700;"
                        title="Buat kategori folder baru ini untuk mengelompokkan dokumen"
                        onclick="confirmBuat()">
                    <i data-feather="folder-plus" style="width:18px;height:18px;"></i>
                    Buat Kategori
                </button>
            </div>
        </form>
    </div>
</div>
</div><!-- /main-content -->
</div><!-- /main-wrapper -->

<!-- Folder Confirm Modal -->
<div class="modal-overlay" id="folder-modal" role="dialog" aria-modal="true">
    <div class="modal-box">
        <div class="modal-icon" style="background:var(--primary-light);">
            <i data-feather="folder-plus" style="width:28px;height:28px;color:var(--primary);"></i>
        </div>
        <div class="modal-title">Buat Kategori Baru?</div>
        <div class="modal-desc">
            Kategori dengan nama:<br>
            <strong id="folder-name-preview" style="color:var(--primary);"></strong><br><br>
            akan dibuat dan bisa langsung digunakan untuk mengelompokkan dokumen.
        </div>
        <div class="modal-actions">
            <button class="btn btn-outline" onclick="closeFolderModal()">
                <i data-feather="x" style="width:15px;height:15px;"></i>
                Periksa Lagi
            </button>
            <button id="folder-confirm-btn" class="btn btn-primary" onclick="doBuat()">
                <i data-feather="folder-plus" style="width:15px;height:15px;"></i>
                Ya, Buat
            </button>
        </div>
    </div>
</div>

<script>
feather.replace();
document.getElementById('nama_folder').focus();

function confirmBuat() {
    const nama = document.getElementById('nama_folder').value.trim();
    if (!nama) {
        document.getElementById('nama_folder').focus();
        document.getElementById('nama_folder').style.borderColor = 'var(--danger)';
        return;
    }
    document.getElementById('folder-name-preview').textContent = '"' + nama + '"';
    document.getElementById('folder-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
    feather.replace();
}

function closeFolderModal() {
    document.getElementById('folder-modal').classList.remove('open');
    document.body.style.overflow = '';
}

function doBuat() {
    const btn = document.getElementById('folder-confirm-btn');
    btn.disabled = true;
    btn.innerHTML = '<i data-feather="loader" style="width:15px;height:15px;" class="spin"></i> Membuat...';
    feather.replace();
    const form = document.getElementById('folder-form');
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'buat';
    hidden.value = '1';
    form.appendChild(hidden);
    form.submit();
}

document.getElementById('folder-modal').addEventListener('click', function(e) {
    if (e.target === this) closeFolderModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeFolderModal();
});
document.getElementById('btn-logout').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('logout-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
});
function closeLogoutModal() {
    document.getElementById('logout-modal').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('logout-modal').addEventListener('click', function(e) {
    if (e.target === this) closeLogoutModal();
});
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