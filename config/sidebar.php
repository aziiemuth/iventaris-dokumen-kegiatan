<?php
// Pastikan session & variabel tersedia
if (!isset($user_role))  $user_role  = $_SESSION['user_role']  ?? '';
if (!isset($username))   $username   = $_SESSION['username']   ?? '';
if (!isset($active_page)) $active_page = ''; // nilai: 'dashboard','upload','folder','log'
if (!isset($folder_id))  $folder_id  = null;
?>

<!-- Sidebar overlay (mobile) -->
<div class="sidebar-overlay" id="sidebar-overlay" onclick="closeSidebar()"></div>

<!-- ========== SIDEBAR ========== -->
<aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <a href="dashboard.php" class="sidebar-brand">
        <div class="brand-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
        </div>
        Inventaris Dokumen
    </a>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="sidebar-label">Menu</div>

        <a href="dashboard.php" class="sidebar-link <?php echo $active_page === 'dashboard' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>

        <a href="upload.php" class="sidebar-link <?php echo $active_page === 'upload' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
            Upload Arsip
        </a>

        <a href="folder.php" class="sidebar-link <?php echo $active_page === 'folder' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
            Kategori
        </a>

        <?php if ($user_role === 'admin'): ?>
        <div class="sidebar-label">Admin</div>

        <a href="users.php" class="sidebar-link <?php echo $active_page === 'users' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Manajemen User
        </a>

        <a href="log_aktivitas.php" class="sidebar-link <?php echo $active_page === 'log' ? 'active' : ''; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Log Aktivitas
        </a>


        <?php endif; ?>

        <?php
        $folders_sidebar = mysqli_query($koneksi, "SELECT id, nama_folder FROM folders ORDER BY nama_folder ASC");
        if ($folders_sidebar && mysqli_num_rows($folders_sidebar) > 0):
        ?>
        <div class="sidebar-label">Kategori</div>
        <?php while ($sf = mysqli_fetch_assoc($folders_sidebar)): ?>
        <a href="dashboard.php?folder=<?php echo $sf['id']; ?>"
           class="sidebar-link <?php echo ($folder_id == $sf['id']) ? 'active' : ''; ?>"
           title="<?php echo htmlspecialchars($sf['nama_folder']); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <?php echo htmlspecialchars($sf['nama_folder']); ?>
        </a>
        <?php endwhile; ?>
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
            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Keluar
        </button>
    </div>
</aside>
