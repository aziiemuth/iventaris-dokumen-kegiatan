<?php
ob_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_role = $_SESSION['user_role'];
$user_id   = $_SESSION['user_id'];

$id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$check = mysqli_query($koneksi, "SELECT * FROM documents WHERE id = $id");
$doc   = mysqli_fetch_assoc($check);

if (!$doc) { header("Location: dashboard.php"); exit; }
if ($user_role === 'user' && $doc['user_id'] != $user_id) {
    header("Location: dashboard.php?msg=access_denied"); exit;
}

$edit_message = "";

if (isset($_POST['update'])) {
    $judul_dokumen = mysqli_real_escape_string($koneksi, $_POST['judul_dokumen']);
    $folder_id     = empty($_POST['folder_id']) ? "NULL" : (int)$_POST['folder_id'];
    $keterangan    = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
    $latitude      = mysqli_real_escape_string($koneksi, $_POST['latitude']);
    $longitude     = mysqli_real_escape_string($koneksi, $_POST['longitude']);

    $q = "UPDATE documents SET 
          judul_dokumen='$judul_dokumen', folder_id=$folder_id, keterangan='$keterangan',
          latitude='$latitude', longitude='$longitude'
          WHERE id=$id";

    if (mysqli_query($koneksi, $q)) {
        if (!empty($_FILES['files']['name'][0])) {
            $target_dir = __DIR__ . '/uploads/';
            foreach ($_FILES['files']['name'] as $key => $nama_asli) {
                if (empty($nama_asli)) continue;
                $tmp          = $_FILES['files']['tmp_name'][$key];
                $ukuran       = $_FILES['files']['size'][$key];
                $nama_bersih  = str_replace(' ', '_', $nama_asli);
                $nama_file    = time() . '_' . rand(100,999) . '_' . $nama_bersih;
                if (move_uploaded_file($tmp, $target_dir . $nama_file)) {
                    mysqli_query($koneksi, "INSERT INTO attachments (document_id, nama_file, nama_asli, ukuran) 
                                           VALUES ($id, '$nama_file', '$nama_asli', '$ukuran')");
                }
            }
        }
        audit_log('Edit', $id, "Mengubah metadata dokumen.");
        header("Location: dashboard.php?folder=" . ($folder_id == "NULL" ? "" : $folder_id));
        exit;
    } else {
        $edit_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Gagal Update: " . mysqli_error($koneksi) . "</div>";
    }
}

$attachments   = mysqli_query($koneksi, "SELECT * FROM attachments WHERE document_id = $id");
$folders_query = mysqli_query($koneksi, "SELECT * FROM folders ORDER BY nama_folder ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dokumen #<?php echo $id; ?> – Inventaris Dokumen</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body>

<?php include __DIR__ . '/config/sidebar.php'; ?>

<!-- ========== MAIN WRAPPER ========== -->
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
    <div class="card form-card fade-in" style="max-width:700px;">

        <!-- Header -->
        <!-- Header -->
        <div class="app-header">
            <div>
                <h2>
                    <i data-feather="edit-3" style="width:20px;height:20px;vertical-align:middle;margin-right:0.4rem;"></i>
                    Edit Dokumen
                </h2>
            </div>
        </div>

        <?php echo $edit_message; ?>

        <form action="" method="POST" enctype="multipart/form-data" id="edit-form">

            <!-- Judul -->
            <div class="form-group">
                <label for="judul_dokumen">
                    <i data-feather="file-text" style="width:14px;height:14px;color:var(--primary);"></i>
                    Judul / Nama Dokumen
                    <span class="label-hint">Wajib diisi</span>
                </label>
                <input type="text" name="judul_dokumen" id="judul_dokumen" class="form-control" required
                       value="<?php echo htmlspecialchars($doc['judul_dokumen']); ?>"
                       placeholder="Nama dokumen atau kegiatan">
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
                    <option value="<?php echo $f['id']; ?>" <?php echo ($doc['folder_id'] == $f['id']) ? 'selected' : ''; ?>>
                        📁  <?php echo htmlspecialchars($f['nama_folder']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Keterangan -->
            <div class="form-group">
                <label for="keterangan">
                    <i data-feather="align-left" style="width:14px;height:14px;color:var(--primary);"></i>
                    Keterangan / Deskripsi
                    <span class="label-hint">Opsional</span>
                </label>
                <textarea name="keterangan" id="keterangan" class="form-control" rows="3"
                          placeholder="Detail kegiatan, catatan, atau keterangan tambahan..."><?php echo htmlspecialchars($doc['keterangan']); ?></textarea>
            </div>

            <!-- GPS -->
            <div class="form-group">
                <div class="gps-card">
                    <label style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem;font-weight:600;font-size:0.875rem;">
                        <i data-feather="navigation" style="width:15px;height:15px;color:var(--primary);"></i>
                        Koordinat GPS
                        <span class="label-hint">Perbarui jika berpindah lokasi</span>
                    </label>
                    <div style="display:flex;gap:0.75rem;margin-bottom:0.85rem;">
                        <div style="flex:1;">
                            <label style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--text-light);">Latitude</label>
                            <input type="text" name="latitude" id="latitude" class="form-control"
                                   value="<?php echo htmlspecialchars($doc['latitude']); ?>"
                                   placeholder="Kosong = tidak ada" readonly style="background:#f8fafc;font-size:0.875rem;">
                        </div>
                        <div style="flex:1;">
                            <label style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:var(--text-light);">Longitude</label>
                            <input type="text" name="longitude" id="longitude" class="form-control"
                                   value="<?php echo htmlspecialchars($doc['longitude']); ?>"
                                   placeholder="Kosong = tidak ada" readonly style="background:#f8fafc;font-size:0.875rem;">
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline w-full" id="btn-lokasi"
                            onclick="getLocation()"
                            title="Ambil ulang koordinat GPS posisi Anda saat ini"
                            style="border-color:var(--primary);color:var(--primary);">
                        <i data-feather="refresh-cw" style="width:15px;height:15px;"></i>
                        Perbarui Lokasi GPS
                    </button>
                    <div id="lokasi-loading" style="display:none;text-align:center;margin-top:0.75rem;font-size:0.84rem;color:var(--text-muted);">
                        <i data-feather="loader" style="width:14px;height:14px;vertical-align:middle;" class="spin"></i>
                        Mengambil koordinat…
                    </div>
                    <div id="lokasi-preview" style="margin-top:0.6rem;text-align:center;<?php echo empty($doc['latitude']) ? 'display:none;' : ''; ?>">
                        <a href="https://maps.google.com/?q=<?php echo $doc['latitude'].','.$doc['longitude']; ?>"
                           id="lokasi-link" target="_blank"
                           style="font-size:0.83rem;color:var(--primary);font-weight:600;text-decoration:underline;">
                            <i data-feather="external-link" style="width:12px;height:12px;"></i>
                            Lihat Lokasi di Google Maps
                        </a>
                    </div>
                </div>
            </div>

            <!-- Current Attachments -->
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:0.4rem;font-size:0.875rem;font-weight:600;">
                    <i data-feather="paperclip" style="width:14px;height:14px;color:var(--primary);"></i>
                    Lampiran File Saat Ini
                </label>
                <?php if (mysqli_num_rows($attachments) > 0): ?>
                <div id="attachments-container">
                    <?php while ($a = mysqli_fetch_assoc($attachments)):
                        $ext_a  = strtolower(pathinfo($a['nama_file'], PATHINFO_EXTENSION));
                        $is_img = in_array($ext_a, ['jpg','jpeg','png','gif','webp']);
                    ?>
                    <div class="attachment-item" id="att-row-<?php echo $a['id']; ?>">
                        <div style="display:flex;align-items:center;gap:0.6rem;overflow:hidden;">
                            <i data-feather="<?php echo $is_img ? 'image' : 'file'; ?>"
                               style="width:16px;height:16px;color:var(--primary);flex-shrink:0;"></i>
                            <span style="font-size:0.875rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?php echo htmlspecialchars($a['nama_asli']); ?>
                            </span>
                        </div>
                        <div style="display:flex;gap:0.5rem;flex-shrink:0;">
                            <a href="file_action.php?id=<?php echo $a['id']; ?>&action=view"
                               target="_blank"
                               class="btn btn-outline btn-icon"
                               title="Buka / lihat file ini"
                               data-tooltip="Lihat file">
                                <i data-feather="eye" style="width:14px;height:14px;color:var(--primary);"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-danger btn-icon"
                                    title="Hapus lampiran ini secara permanen"
                                    data-tooltip="Hapus lampiran"
                                    onclick="deleteAttachment(<?php echo $a['id']; ?>)">
                                <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p style="color:var(--text-light);font-size:0.875rem;font-style:italic;">Belum ada file terlampir.</p>
                <?php endif; ?>
            </div>

            <!-- Add New Files -->
            <div class="form-group">
                <label for="new-files">
                    <i data-feather="plus-circle" style="width:14px;height:14px;color:var(--primary);"></i>
                    Tambah Lampiran Baru
                    <span class="label-hint">Opsional – bisa multi-file</span>
                </label>
                <div class="drop-zone" style="position:relative;padding:1.5rem;">
                    <input type="file" name="files[]" id="new-files" multiple
                           style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;"
                           onchange="showNewFiles(this)">
                    <div style="text-align:center;">
                        <i data-feather="plus" style="width:28px;height:28px;color:var(--border-color);display:block;margin:0 auto 0.5rem;"></i>
                        <p style="font-size:0.95rem;color:var(--text-muted);font-weight:600;margin-bottom:0.2rem;">Pilih File Dokumen</p>
                        <p style="font-size:0.75rem;color:var(--text-light);">(Bisa pilih lebih dari satu file/foto)</p>
                    </div>
                    <div id="new-file-list" style="margin-top:0.6rem;"></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                <a href="dashboard.php" class="btn btn-outline"
                   title="Batalkan semua perubahan dan kembali ke dashboard">
                    <i data-feather="x" style="width:15px;height:15px;"></i>
                    Batal
                </a>
                <button type="button" id="btn-update-trigger"
                        class="btn btn-warning w-full"
                        style="flex:1;padding:0.9rem;font-size:1rem;font-weight:700;"
                        title="Simpan semua perubahan yang Anda buat pada dokumen ini"
                        onclick="confirmUpdate()">
                    <i data-feather="save" style="width:18px;height:18px;"></i>
                    Simpan Perubahan
                </button>
            </div>

        </form>
    </div>
</div>

<!-- Update Confirm Modal -->
<div class="modal-overlay" id="update-modal" role="dialog" aria-modal="true">
    <div class="modal-box">
        <div class="modal-icon" style="background:#ecfdf5;">
            <i data-feather="save" style="width:28px;height:28px;color:#10b981;"></i>
        </div>
        <div class="modal-title">Simpan Perubahan?</div>
        <div class="modal-desc">Yakin ingin menyimpan perubahan?</div>
        <div class="modal-actions">
            <button class="btn btn-outline" onclick="closeUpdateModal()">
                <i data-feather="x" style="width:15px;height:15px;"></i>
                Periksa Lagi
            </button>
            <button id="update-confirm-btn" class="btn btn-success" onclick="doUpdate()">
                <i data-feather="check" style="width:15px;height:15px;"></i>
                Ya, Simpan
            </button>
        </div>
    </div>
</div>

<!-- Delete Attachment Modal -->
<div class="modal-overlay" id="del-att-modal" role="dialog" aria-modal="true">
    <div class="modal-box">
        <div class="modal-icon modal-icon-danger">
            <i data-feather="paperclip" style="width:28px;height:28px;color:var(--danger);"></i>
        </div>
        <div class="modal-title">Hapus Lampiran?</div>
        <div class="modal-desc">File lampiran ini akan dihapus permanen.<br><span style="color:var(--danger);font-weight:600;">Tidak bisa dibatalkan!</span></div>
        <div class="modal-actions">
            <button class="btn btn-outline" onclick="closeDelAttModal()">
                <i data-feather="x" style="width:15px;height:15px;"></i> Batal
            </button>
            <button id="del-att-confirm" class="btn btn-danger">
                <i data-feather="trash-2" style="width:15px;height:15px;"></i> Ya, Hapus
            </button>
        </div>
    </div>
</div>

<script>
feather.replace();

/* GPS */
const btnLokasi    = document.getElementById('btn-lokasi');
const lokasiLoad   = document.getElementById('lokasi-loading');
const inputLat     = document.getElementById('latitude');
const inputLng     = document.getElementById('longitude');
const lokasiPreview= document.getElementById('lokasi-preview');
const lokasiLink   = document.getElementById('lokasi-link');

function getLocation() {
    if (!navigator.geolocation) { alert("Geolocation tidak didukung."); return; }
    btnLokasi.style.display = 'none';
    lokasiLoad.style.display = 'block';
    navigator.geolocation.getCurrentPosition(showPosition, showError, { enableHighAccuracy: true, timeout: 30000, maximumAge: 0 });
}
function showPosition(pos) {
    inputLat.value = pos.coords.latitude;
    inputLng.value = pos.coords.longitude;
    lokasiLoad.style.display = 'none';
    btnLokasi.style.display = 'block';
    btnLokasi.className = 'btn btn-success w-full';
    btnLokasi.style.color = 'white'; // override inline color agar tidak kontras biru
    btnLokasi.innerHTML = '<i data-feather="check" style="width:15px;height:15px;"></i> Lokasi Diperbarui!';
    lokasiLink.href = `https://maps.google.com/?q=${pos.coords.latitude},${pos.coords.longitude}`;
    lokasiLink.innerHTML = '<i data-feather="external-link" style="width:12px;height:12px;"></i> Lihat Lokasi Baru';
    lokasiPreview.style.display = 'block';
    feather.replace();
}
function showError(err) {
    lokasiLoad.style.display = 'none';
    btnLokasi.style.display = 'block';
    const msgs = {1:'Izin lokasi ditolak.',2:'Lokasi tidak tersedia.',3:'Timeout GPS.'};
    alert("Gagal: " + (msgs[err.code] || err.message));
}

/* New file preview */
function showNewFiles(input) {
    const list = document.getElementById('new-file-list');
    list.innerHTML = '';
    if (!input.files.length) return;
    Array.from(input.files).forEach(f => {
        const div = document.createElement('div');
        div.style.cssText = 'display:flex;align-items:center;gap:0.5rem;padding:0.35rem 0.6rem;background:white;border:1px solid var(--border-color);border-radius:6px;margin-bottom:0.3rem;font-size:0.82rem;';
        div.innerHTML = `<i data-feather="file" style="width:12px;height:12px;color:var(--primary);flex-shrink:0;"></i>
                         <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${f.name}</span>
                         <span style="color:var(--text-light);flex-shrink:0;">${(f.size/1024).toFixed(1)} KB</span>`;
        list.appendChild(div);
    });
    feather.replace();
}

/* Delete attachment modal */
let pendingDelAttId = null;

function deleteAttachment(attId) {
    pendingDelAttId = attId;
    document.getElementById('del-att-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeDelAttModal() {
    document.getElementById('del-att-modal').classList.remove('open');
    document.body.style.overflow = '';
    pendingDelAttId = null;
}

document.getElementById('del-att-confirm').addEventListener('click', function() {
    if (!pendingDelAttId) return;
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i data-feather="loader" style="width:14px;height:14px;" class="spin"></i> Menghapus…';
    feather.replace();

    fetch('file_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=delete_attachment&att_id=${pendingDelAttId}&doc_id=<?php echo $id; ?>`
    })
    .then(r => r.json())
    .then(res => {
        closeDelAttModal();
        if (res.status === 'success') {
            const el = document.getElementById(`att-row-${pendingDelAttId}`);
            if (el) { el.style.opacity = '0'; el.style.transition = 'opacity 0.3s'; setTimeout(() => el.remove(), 300); }
        } else {
            alert(res.message || 'Gagal menghapus.');
        }
        btn.disabled = false;
    })
    .catch(() => { closeDelAttModal(); alert('Terjadi kesalahan jaringan.'); btn.disabled = false; });
});

document.getElementById('del-att-modal').addEventListener('click', function(e) {
    if (e.target === this) closeDelAttModal();
});

/* Update confirm modal */
function confirmUpdate() {
    document.getElementById('update-modal').classList.add('open');
    document.body.style.overflow = 'hidden';
    feather.replace();
}

function closeUpdateModal() {
    document.getElementById('update-modal').classList.remove('open');
    document.body.style.overflow = '';
}

function doUpdate() {
    const btn = document.getElementById('update-confirm-btn');
    btn.disabled = true;
    btn.innerHTML = '<i data-feather="loader" style="width:15px;height:15px;" class="spin"></i> Menyimpan…';
    feather.replace();
    // Tambah hidden input name="update" lalu submit form
    const form = document.getElementById('edit-form');
    const hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = 'update';
    hidden.value = '1';
    form.appendChild(hidden);
    form.submit();
}

document.getElementById('update-modal').addEventListener('click', function(e) {
    if (e.target === this) closeUpdateModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeUpdateModal();
        closeDelAttModal();
    }
});
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

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeSidebar();
});
</script>

</div>
</div><!-- /main-content -->
</div><!-- /main-wrapper -->

</body>
</html>