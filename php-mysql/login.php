<?php
/**
 * HALAMAN LOGIN SISWA & ADMIN
 * Aplikasi Pengaduan Sarana Sekolah (UKK RPL)
 */
session_start();
require_once "koneksi.php";

// Jika sudah ada sesi aktif, langsung alihkan
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    } else if ($_SESSION['role'] === 'siswa') {
        header("Location: siswa_dashboard.php");
        exit();
    }
}

$error_msg = "";
$active_tab = "siswa"; // default tab

// Proses Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_type = isset($_POST['login_type']) ? $_POST['login_type'] : 'siswa';
    $active_tab = $login_type;

    if ($login_type === 'siswa') {
        // --- LOGIN SISWA BERDASARKAN NIS ---
        $nis = intval($_POST['nis'] ?? 0);

        if ($nis <= 0) {
            $error_msg = "Nomor Induk Siswa (NIS) tidak valid!";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT nis, nama, kelas FROM siswa WHERE nis = ?");
            mysqli_stmt_bind_param($stmt, "i", $nis);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($res)) {
                $_SESSION['role'] = 'siswa';
                $_SESSION['nis'] = $row['nis'];
                $_SESSION['nama'] = $row['nama'];
                $_SESSION['kelas'] = $row['kelas'];

                header("Location: siswa_dashboard.php");
                exit();
            } else {
                $error_msg = "NIS <strong>" . htmlspecialchars($nis) . "</strong> belum terdaftar dalam sistem database sekolah.";
            }
        }
    } else if ($login_type === 'admin') {
        // --- LOGIN ADMIN / PETUGAS SARPRAS ---
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error_msg = "Username dan password wajib diisi!";
        } else {
            $stmt = mysqli_prepare($conn, "SELECT username, password, nama_petugas FROM admin WHERE username = ?");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($res)) {
                // Mendukung plain text, md5, dan password_verify
                $valid = ($password === $row['password']) || 
                         (md5($password) === $row['password']) || 
                         password_verify($password, $row['password']);

                if ($valid) {
                    $_SESSION['role'] = 'admin';
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['nama_petugas'] = $row['nama_petugas'];

                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    $error_msg = "Kata sandi yang Anda masukkan salah!";
                }
            } else {
                $error_msg = "Akun Administrator dengan username tersebut tidak ditemukan!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Pengaduan Sarana Sekolah</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        }
        .login-box {
            width: 100%;
            max-width: 460px;
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        .login-header {
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: white;
            padding: 2rem 1.5rem;
            text-align: center;
        }
        .login-tabs {
            display: flex;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
        }
        .login-tab-btn {
            flex: 1;
            padding: 0.9rem;
            border: none;
            background: transparent;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
        }
        .login-tab-btn.active {
            color: var(--primary);
            background: white;
            border-bottom-color: var(--primary);
        }
        .login-body {
            padding: 1.75rem 1.5rem;
        }
        .demo-box {
            background: #f8fafc;
            border: 1px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 0.85rem;
            margin-top: 1.25rem;
            font-size: 0.78rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-box">
        <div class="login-header">
            <div style="font-size: 2.2rem; margin-bottom: 0.25rem;">🏫</div>
            <h2 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.2rem;">SI-PENGADUAN SARPRAS</h2>
            <p style="font-size: 0.85rem; opacity: 0.9;">Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak</p>
        </div>

        <!-- Tab Pemilih Peran -->
        <div class="login-tabs">
            <button type="button" class="login-tab-btn <?= $active_tab === 'siswa' ? 'active' : '' ?>" onclick="switchLoginTab('siswa')">
                👨‍🎓 Portal Siswa
            </button>
            <button type="button" class="login-tab-btn <?= $active_tab === 'admin' ? 'active' : '' ?>" onclick="switchLoginTab('admin')">
                🛡️ Petugas Admin
            </button>
        </div>

        <div class="login-body">
            <?php if (!empty($error_msg)): ?>
                <div style="background: #fee2e2; border-left: 4px solid var(--danger); padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1.25rem; font-size: 0.85rem; color: #991b1b;">
                    <?= $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- FORM LOGIN SISWA -->
            <form id="formLoginSiswa" method="POST" action="login.php" style="display: <?= $active_tab === 'siswa' ? 'block' : 'none' ?>;">
                <input type="hidden" name="login_type" value="siswa">
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" for="loginNis">Nomor Induk Siswa (NIS) <span class="required">*</span></label>
                    <input type="number" id="loginNis" name="nis" class="form-control" placeholder="Contoh: 1024001" required autofocus>
                    <span class="form-help">Masukkan NIS terdaftar untuk mengakses formulir dan riwayat aspirasi.</span>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Masuk Sebagai Siswa
                </button>

                <div class="demo-box">
                    <strong>💡 Demo Akun Siswa Tersedia:</strong><br>
                    • NIS: <a href="javascript:void(0)" onclick="fillNis(1024001)">1024001</a> (Ahmad Fauzi - XII RPL 1)<br>
                    • NIS: <a href="javascript:void(0)" onclick="fillNis(1024002)">1024002</a> (Siti Nurhaliza - XII RPL 2)<br>
                    • NIS: <a href="javascript:void(0)" onclick="fillNis(1024003)">1024003</a> (Budi Santoso - XI RPL 1)
                </div>
            </form>

            <!-- FORM LOGIN ADMIN -->
            <form id="formLoginAdmin" method="POST" action="login.php" style="display: <?= $active_tab === 'admin' ? 'block' : 'none' ?>;">
                <input type="hidden" name="login_type" value="admin">
                <div class="form-group" style="margin-bottom: 1rem;">
                    <label class="form-label" for="loginUsername">Username Admin <span class="required">*</span></label>
                    <input type="text" id="loginUsername" name="username" class="form-control" placeholder="admin" required>
                </div>

                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label class="form-label" for="loginPassword">Kata Sandi (Password) <span class="required">*</span></label>
                    <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Masuk Sebagai Admin
                </button>

                <div class="demo-box">
                    <strong>💡 Kredensial Default Penguji UKK:</strong><br>
                    • Username: <code>admin</code><br>
                    • Password: <code>password123</code>
                </div>
            </form>

            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="../index.html" class="btn btn-secondary btn-sm" style="display: inline-flex;">
                    🌐 Buka Versi Web Interaktif Client-Side
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function switchLoginTab(tab) {
    const tabs = document.querySelectorAll('.login-tab-btn');
    tabs[0].classList.toggle('active', tab === 'siswa');
    tabs[1].classList.toggle('active', tab === 'admin');

    document.getElementById('formLoginSiswa').style.display = tab === 'siswa' ? 'block' : 'none';
    document.getElementById('formLoginAdmin').style.display = tab === 'admin' ? 'block' : 'none';
}

function fillNis(nis) {
    document.getElementById('loginNis').value = nis;
}
</script>

</body>
</html>
