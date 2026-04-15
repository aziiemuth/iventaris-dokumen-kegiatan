<?php
ob_start();
include __DIR__ . '/config/koneksi.php';

$redirect_to_dashboard = false;

if (isset($_SESSION['user_id'])) {
    $redirect_to_dashboard = true;
}

$login_error = false;
$login_success = false;

if (!$redirect_to_dashboard && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $username_esc = mysqli_real_escape_string($koneksi, $username);
    $password_esc = mysqli_real_escape_string($koneksi, $password);

    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username = '$username_esc' AND password = '$password_esc'");
    if ($query && mysqli_num_rows($query) > 0) {
        $user = mysqli_fetch_assoc($query);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $login_success = true;
    } else {
        $login_error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Inventaris Dokumen Kegiatan</title>
    <meta name="description" content="Masuk ke Sistem Inventaris Dokumen Kegiatan">
    <?php if ($redirect_to_dashboard || $login_success): ?>
    <meta http-equiv="refresh" content="0;url=dashboard.php">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <style>
        body { background: linear-gradient(160deg, #0a1628 0%, #0d2151 40%, #0f3460 100%); }

        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-shell {
            display: flex;
            width: 100%;
            max-width: 860px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }

        /* Left branding panel */
        .login-panel-left {
            flex: 1;
            background: rgba(10, 22, 50, 0.6);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255,255,255,0.06);
            padding: 3rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
        }

        .login-panel-left .brand-mark {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #1a56db, #1e3a8a);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(26,86,219,0.5);
        }

        .login-panel-left h1 {
            color: white;
            font-size: 1.7rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            line-height: 1.25;
        }

        .login-panel-left p {
            color: rgba(255,255,255,0.75);
            font-size: 0.9rem;
            line-height: 1.7;
        }

        .feature-list { margin-top: 2rem; display: flex; flex-direction: column; gap: 0.75rem; }
        .feature-item {
            display: flex; align-items: center; gap: 0.75rem;
            color: rgba(255,255,255,0.85);
            font-size: 0.85rem; font-weight: 500;
        }
        .feature-item .dot {
            width: 28px; height: 28px; flex-shrink: 0;
            background: rgba(26,86,219,0.35);
            border: 1px solid rgba(26,86,219,0.5);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }

        /* Right form panel */
        .login-panel-right {
            width: 380px;
            background: #0d1b3e;
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 1px solid rgba(255,255,255,0.06);
        }

        .login-panel-right h2 {
            font-size: 1.4rem;
            margin-bottom: 0.4rem;
            color: #e2e8f0;
        }

        .login-panel-right .tagline {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 2rem;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.1rem;
        }

        .input-group .input-icon {
            position: absolute;
            left: 0.9rem; top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            width: 16px; height: 16px;
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            padding: 0.8rem 0.9rem 0.8rem 2.6rem;
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            font-family: inherit;
            color: #e2e8f0;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: rgba(255,255,255,0.06);
        }

        .input-group input::placeholder { color: #475569; }

        .input-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59,130,246,0.2);
            background: rgba(255,255,255,0.1);
        }

        .input-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.4rem;
        }

        .btn-login {
            width: 100%;
            padding: 0.85rem;
            font-size: 1rem;
            margin-top: 0.5rem;
            border-radius: var(--radius-md);
            font-weight: 700;
            background: linear-gradient(135deg, #1a56db, #1e40af);
            border-color: #1e40af;
            box-shadow: 0 4px 14px rgba(26,86,219,0.5);
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e3a8a);
            box-shadow: 0 6px 20px rgba(26,86,219,0.65);
            transform: translateY(-1px);
        }

        .error-alert {
            background: #fef2f2;
            border: 1.5px solid #fca5a5;
            color: #b91c1c;
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .demo-accounts {
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255,255,255,0.08);
        }

        .demo-accounts p {
            font-size: 0.78rem;
            color: #64748b;
            text-align: center;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
        }

        .demo-row {
            display: flex;
            gap: 0.5rem;
        }

        .demo-btn {
            flex: 1;
            background: rgba(255,255,255,0.05);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: var(--radius-md);
            padding: 0.5rem;
            text-align: center;
            cursor: pointer;
            font-size: 0.8rem;
            color: #94a3b8;
            font-weight: 600;
            transition: all 0.2s;
        }

        .demo-btn:hover {
            border-color: #3b82f6;
            background: rgba(59,130,246,0.15);
            color: #93c5fd;
        }

        .demo-btn span {
            display: block;
            font-size: 0.72rem;
            font-weight: 400;
            opacity: 0.75;
            margin-top: 1px;
        }

        @media (max-width: 680px) {
            .login-shell { flex-direction: column; }
            .login-panel-left { display: none; }
            .login-panel-right { width: 100%; }
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-shell fade-in">

        <!-- Left branding -->
        <div class="login-panel-left">
            <div class="brand-mark">
                <i data-feather="archive" style="color:white; width:28px; height:28px;"></i>
            </div>
            <h1>Inventaris Dokumen Kegiatan</h1>
            <p>Sistem pengarsipan dokumen digital yang terorganisir, aman, dan mudah dicari kapan saja.</p>
            <div class="feature-list">
                <div class="feature-item">
                    <div class="dot"><i data-feather="folder" style="width:14px;height:14px;color:white;"></i></div>
                    Kelola dokumen per kategori kegiatan
                </div>
                <div class="feature-item">
                    <div class="dot"><i data-feather="upload-cloud" style="width:14px;height:14px;color:white;"></i></div>
                    Upload multi-file dengan sekali klik
                </div>
                <div class="feature-item">
                    <div class="dot"><i data-feather="map-pin" style="width:14px;height:14px;color:white;"></i></div>
                    Pencatatan koordinat GPS otomatis
                </div>
                <div class="feature-item">
                    <div class="dot"><i data-feather="activity" style="width:14px;height:14px;color:white;"></i></div>
                    Rekam jejak aktivitas pengguna
                </div>
            </div>
        </div>

        <!-- Right form -->
        <div class="login-panel-right">
            <h2>Masuk ke Sistem</h2>
            <p class="tagline">Masukkan kredensial akun Anda untuk melanjutkan.</p>

            <?php if ($login_error): ?>
            <div class="error-alert">
                <i data-feather="alert-circle" style="width:16px;height:16px;flex-shrink:0;"></i>
                Username atau Password salah! Periksa kembali.
            </div>
            <?php endif; ?>

            <form method="POST" id="login-form">
                <div>
                    <label class="input-label" for="username">Username</label>
                    <div class="input-group">
                        <i data-feather="user" class="input-icon"></i>
                        <input type="text" name="username" id="username"
                               placeholder="Masukkan username"
                               required autocomplete="username" autofocus>
                    </div>
                </div>

                <div>
                    <label class="input-label" for="password">Password</label>
                    <div class="input-group">
                        <i data-feather="lock" class="input-icon"></i>
                        <input type="password" name="password" id="password"
                               placeholder="Masukkan password"
                               required autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" name="login" id="btn-login"
                        class="btn btn-primary btn-login">
                    <i data-feather="log-in" style="width:17px;height:17px;"></i>
                    Masuk ke Sistem
                </button>
            </form>

            <div class="demo-accounts">
                <p>Isi Cepat Akun Demo</p>
                <div class="demo-row">
                    <button class="demo-btn" onclick="fillDemo('admin','admin')">
                        👑 Admin
                        <span>Full Access</span>
                    </button>
                    <button class="demo-btn" onclick="fillDemo('user','user')">
                        👤 User
                        <span>Limited Access</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    feather.replace();

    function fillDemo(u, p) {
        document.getElementById('username').value = u;
        document.getElementById('password').value = p;
        document.getElementById('password').focus();
    }

    // Submit button loading state — gunakan setTimeout agar tidak block form POST
    document.getElementById('login-form').addEventListener('submit', function(e) {
        const btn = document.getElementById('btn-login');
        // Delay disable agar browser sudah mengirimkan POST request lebih dulu
        setTimeout(function() {
            btn.innerHTML = '<i data-feather="loader" style="width:17px;height:17px;" class="spin"></i> Memproses...';
            btn.disabled = true;
            feather.replace();
        }, 100);
    });

    // JS redirect fallback jika PHP header redirect tidak berjalan
    <?php if ($login_success || $redirect_to_dashboard): ?>
    window.location.replace('dashboard.php');
    <?php endif; ?>
</script>
</body>
</html>