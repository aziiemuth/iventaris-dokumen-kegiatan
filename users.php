<?php
ob_start();
include 'config/koneksi.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

$user_message = "";

// Aksi Tambah User
if (isset($_POST['buat'])) {
    $username_baru = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password_baru = mysqli_real_escape_string($koneksi, trim($_POST['password']));
    $role_baru = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    // Cek apakah username sudah ada
    $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username_baru'");
    if (mysqli_num_rows($cek) > 0) {
        $user_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Gagal menambah: Username sudah digunakan.</div>";
    } else {
        if (mysqli_query($koneksi, "INSERT INTO users (username, password, role) VALUES ('$username_baru', '$password_baru', '$role_baru')")) {
            header("Location: users.php?msg=created");
            exit;
        } else {
            $user_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Gagal menambah user.</div>";
        }
    }
}

// Aksi Edit User
if (isset($_POST['edit'])) {
    $id_edit = (int)$_POST['user_id'];
    $username_edit = mysqli_real_escape_string($koneksi, trim($_POST['username']));
    $password_edit = mysqli_real_escape_string($koneksi, trim($_POST['password']));
    $role_edit = mysqli_real_escape_string($koneksi, $_POST['role']);
    
    // Cek duplikasi username untuk user lain
    $cek = mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username_edit' AND id != $id_edit");
    if (mysqli_num_rows($cek) > 0) {
        $user_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Gagal mengedit: Username sudah digunakan.</div>";
    } else {
        $query_update = "UPDATE users SET username = '$username_edit', password = '$password_edit', role = '$role_edit' WHERE id = $id_edit";
        if (mysqli_query($koneksi, $query_update)) {
            header("Location: users.php?msg=edited");
            exit;
        } else {
            $user_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Gagal mengedit user.</div>";
        }
    }
}

// Aksi Hapus User
if (isset($_POST['hapus'])) {
    $id_hapus = (int)$_POST['user_id_hapus'];
    
    // Cegah hapus diri sendiri jika perlu, namun untuk kemudahan kita biarkan admin sadar
    if ($id_hapus == $_SESSION['user_id']) {
        $user_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Tidak dapat menghapus akun Anda sendiri saat sedang login.</div>";
    } else {
        // Hapus user
        if (mysqli_query($koneksi, "DELETE FROM users WHERE id = $id_hapus")) {
            header("Location: users.php?msg=deleted");
            exit;
        } else {
            $user_message = "<div class='alert alert-danger'><i data-feather='alert-circle' style='width:16px;height:16px;flex-shrink:0;'></i> Gagal menghapus user.</div>";
        }
    }
}

// Pesan Sukses
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $user_message = "<div class='alert alert-success'><i data-feather='check-circle' style='width:16px;height:16px;'></i> User berhasil ditambahkan.</div>";
    if ($_GET['msg'] === 'edited') $user_message = "<div class='alert alert-success'><i data-feather='check-circle' style='width:16px;height:16px;'></i> Data user berhasil diperbarui.</div>";
    if ($_GET['msg'] === 'deleted') $user_message = "<div class='alert alert-success'><i data-feather='check-circle' style='width:16px;height:16px;'></i> User berhasil dihapus permanen.</div>";
}

// Get all users
$usersUrl = mysqli_query($koneksi, "SELECT * FROM users ORDER BY username ASC");

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen User – Inventaris Dokumen</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
<?php $active_page = 'users'; ?>
</head>
<body>

<?php include 'config/sidebar.php'; ?>

<div class="main-wrapper">

<!-- Mobile Topbar -->
<header class="topbar mobile-topbar">
    <div class="topbar-left">
        <button class="hamburger-btn" onclick="openSidebar()" title="Buka menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
    </div>
    <div style="font-weight:700; color:var(--primary); font-size:1.1rem; margin-left:0.5rem;">Manajemen User</div>
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
<div class="card fade-in">
    <div class="app-header" style="flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2>
                <i data-feather="users" style="width:20px;height:20px;vertical-align:middle;margin-right:0.4rem;"></i>
                Manajemen User
            </h2>
        </div>
        <div class="actions-group">
            <button onclick="openBuatModal()" class="btn btn-primary" title="Tambah user baru">
                <i data-feather="user-plus" style="width:15px;height:15px;"></i>
                Tambah User Baru
            </button>
        </div>
    </div>

    <?php echo $user_message; ?>

    <div class="table-wrapper" style="margin-top:1.5rem;">
        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Role</th>
                    <th style="width:25%; text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($usersUrl) > 0): ?>
                    <?php while($u = mysqli_fetch_assoc($usersUrl)): ?>
                        <tr>
                            <td style="font-weight:600; color:var(--primary); font-size:0.95rem;">
                                <?php echo htmlspecialchars($u['username']); ?>
                            </td>
                            <td>
                                <!-- Password ditampilkan karena plain-text form ini -->
                                <?php echo htmlspecialchars($u['password']); ?>
                            </td>
                            <td>
                                <span class="badge" style="background:<?php echo $u['role'] === 'admin' ? '#fef3c7' : 'var(--primary-light)'; ?>; color:<?php echo $u['role'] === 'admin' ? '#d97706' : 'var(--primary)'; ?>; padding:0.25rem 0.6rem; border-radius:99px; font-weight:600; font-size:0.8rem;">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:0.5rem; justify-content:flex-end;">
                                    <button onclick="openEditModal(<?php echo $u['id']; ?>, '<?php echo addslashes(htmlspecialchars($u['username'])); ?>', '<?php echo addslashes(htmlspecialchars($u['password'])); ?>', '<?php echo addslashes(htmlspecialchars($u['role'])); ?>')" class="btn btn-warning btn-icon" title="Edit User">
                                        <i data-feather="edit-2" style="width:14px;height:14px;"></i>
                                    </button>
                                    <button onclick="openDeleteModal(<?php echo $u['id']; ?>, '<?php echo addslashes(htmlspecialchars($u['username'])); ?>')" class="btn btn-danger btn-icon" title="Hapus User" <?php echo ($u['id'] == $_SESSION['user_id']) ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>
                                        <i data-feather="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center; padding: 2rem; color:var(--text-light); font-style:italic;">
                            Belum ada user yang terdaftar.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
</div><!-- /main-content -->
</div><!-- /main-wrapper -->

<!-- Buat User Modal -->
<div class="modal-overlay" id="buat-modal" role="dialog" aria-modal="true">
    <div class="modal-box">
        <div class="modal-icon" style="background:var(--primary-light);">
            <i data-feather="user-plus" style="width:28px;height:28px;color:var(--primary);"></i>
        </div>
        <div class="modal-title">Tambah User Baru</div>
        <form method="POST" action="users.php" id="buat-form">
            <input type="hidden" name="buat" value="1">
            <div class="form-group" style="text-align:left; margin-top:1rem;">
                <label for="username_buat" style="font-weight:600; font-size:0.85rem; margin-bottom:0.4rem; display:block;">Username</label>
                <input type="text" name="username" id="username_buat" class="form-control" required placeholder="Masukkan username" autocomplete="off">
            </div>
            <div class="form-group" style="text-align:left; margin-top:1rem;">
                <label for="password_buat" style="font-weight:600; font-size:0.85rem; margin-bottom:0.4rem; display:block;">Password</label>
                <input type="text" name="password" id="password_buat" class="form-control" required placeholder="Masukkan password" autocomplete="off">
            </div>
            <div class="form-group" style="text-align:left; margin-top:1rem;">
                <label for="role_buat" style="font-weight:600; font-size:0.85rem; margin-bottom:0.4rem; display:block;">Role</label>
                <select name="role" id="role_buat" class="form-control" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
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

<!-- Edit User Modal -->
<div class="modal-overlay" id="edit-modal" role="dialog" aria-modal="true">
    <div class="modal-box">
        <div class="modal-icon" style="background:#fef3c7;">
            <i data-feather="edit-2" style="width:28px;height:28px;color:#d97706;"></i>
        </div>
        <div class="modal-title">Ubah Detail User</div>
        <form method="POST" action="users.php" id="edit-form">
            <input type="hidden" name="edit" value="1">
            <input type="hidden" name="user_id" id="edit_user_id" value="">
            <div class="form-group" style="text-align:left; margin-top:1rem;">
                <label for="username_edit" style="font-weight:600; font-size:0.85rem; margin-bottom:0.4rem; display:block;">Username</label>
                <input type="text" name="username" id="username_edit" class="form-control" required autocomplete="off">
            </div>
            <div class="form-group" style="text-align:left; margin-top:1rem;">
                <label for="password_edit" style="font-weight:600; font-size:0.85rem; margin-bottom:0.4rem; display:block;">Password</label>
                <input type="text" name="password" id="password_edit" class="form-control" required autocomplete="off">
            </div>
            <div class="form-group" style="text-align:left; margin-top:1rem;">
                <label for="role_edit" style="font-weight:600; font-size:0.85rem; margin-bottom:0.4rem; display:block;">Role</label>
                <select name="role" id="role_edit" class="form-control" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
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

<!-- Delete User Modal -->
<div class="modal-overlay" id="delete-modal" role="dialog" aria-modal="true">
    <div class="modal-box">
        <div class="modal-icon modal-icon-danger">
            <i data-feather="alert-triangle" style="width:28px;height:28px;color:var(--danger);"></i>
        </div>
        <div class="modal-title">Hapus User?</div>
        <div class="modal-desc" style="margin-bottom:1.2rem;">
            Anda akan menghapus user: <br>
            <strong id="delete-user-name" style="color:var(--text-main);font-size:1.05rem;"></strong>
            <br><br>
            <span style="color:var(--danger);font-weight:700;">Tindakan ini tidak bisa dikembalikan!</span>
        </div>
        <form method="POST" action="users.php">
            <input type="hidden" name="hapus" value="1">
            <input type="hidden" name="user_id_hapus" id="delete_user_id" value="">
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">
                    <i data-feather="x" style="width:15px;height:15px;"></i> Batal
                </button>
                <button type="submit" class="btn btn-danger">
                    <i data-feather="trash-2" style="width:15px;height:15px;"></i> Ya, Hapus!
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
    setTimeout(() => document.getElementById('username_buat').focus(), 100);
}
function closeBuatModal() {
    document.getElementById('buat-modal').classList.remove('open');
}

function openEditModal(id, username, password, role) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('username_edit').value = username;
    document.getElementById('password_edit').value = password;
    document.getElementById('role_edit').value = role;
    document.getElementById('edit-modal').classList.add('open');
    setTimeout(() => document.getElementById('username_edit').focus(), 100);
}
function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('open');
}

function openDeleteModal(id, username) {
    document.getElementById('delete_user_id').value = id;
    document.getElementById('delete-user-name').innerText = username;
    document.getElementById('delete-modal').classList.add('open');
}
function closeDeleteModal() {
    document.getElementById('delete-modal').classList.remove('open');
}

/* Close Modals on Overlay Click / ESC */
const modals = ['buat-modal', 'edit-modal', 'delete-modal', 'logout-modal'];
modals.forEach(modalId => {
    document.getElementById(modalId).addEventListener('click', function(e) {
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

/* Toggle Sidebar Mobile */
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

/* Logout */
document.getElementById('btn-logout').addEventListener('click', function(e) {
    e.preventDefault();
    document.getElementById('logout-modal').classList.add('open');
});
function closeLogoutModal() {
    document.getElementById('logout-modal').classList.remove('open');
}

/* Clear URL parameters so alerts don't show up on refresh/history browsing */
if (window.history.replaceState) {
    const url = new URL(window.location);
    if (url.searchParams.has('msg')) {
        url.searchParams.delete('msg');
        window.history.replaceState({path:url.href}, '', url.href);
    }
}
</script>

</body>
</html>
