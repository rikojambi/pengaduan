<?php
/**
 * KONEKSI DATABASE & FUNGSI HELPER
 * Aplikasi Pengaduan Sarana Sekolah (UKK RPL)
 */

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "db_pengaduan_sarana";

// Membuat koneksi ke database MySQL
$conn = @mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// Penanganan jika koneksi gagal
if (!$conn) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Koneksi Database Gagal</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body style="display:flex; align-items:center; justify-content:center; min-height:100vh;">
        <div class="card" style="max-width:550px; text-align:center;">
            <div style="font-size:3rem; margin-bottom:1rem;">⚠️</div>
            <h2 style="color:var(--danger); margin-bottom:0.5rem;">Koneksi Database Gagal!</h2>
            <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">
                Pastikan service MySQL di XAMPP/Laragon sudah dijalankan (Running), dan database 
                <code>db_pengaduan_sarana</code> telah diimpor dari file <code>database.sql</code>.
            </p>
            <div style="background:#fee2e2; border-radius:6px; padding:0.75rem; text-align:left; font-size:0.8rem; color:#991b1b; margin-bottom:1.5rem;">
                <strong>Error:</strong> <?= htmlspecialchars(mysqli_connect_error()); ?>
            </div>
            <a href="index.php" class="btn btn-primary">Coba Muat Ulang Halaman</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

mysqli_set_charset($conn, "utf8mb4");

/**
 * Format tanggal standar Indonesia (contoh: 15 Okt 2026)
 */
function tgl_indo($tanggal) {
    if (!$tanggal || $tanggal == '0000-00-00') return '-';
    $bulan_arr = array(
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    );
    $pecah = explode('-', $tanggal);
    if (count($pecah) != 3) return $tanggal;
    return $pecah[2] . ' ' . $bulan_arr[(int)$pecah[1]] . ' ' . $pecah[0];
}

/**
 * Membersihkan input form untuk mencegah XSS & injection sederhana
 */
function bersihkan($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8'));
}

/**
 * Helper HTML Badge Status
 */
function render_badge_status($status) {
    $class = 'badge-menunggu';
    if ($status === 'Proses') $class = 'badge-proses';
    if ($status === 'Selesai') $class = 'badge-selesai';

    return '<span class="badge-status ' . $class . '"><span class="badge-dot"></span>' . htmlspecialchars($status) . '</span>';
}

/**
 * Helper Visual Stepper Progres
 */
function render_stepper($status) {
    $s1 = 'step-item';
    $s2 = 'step-item';
    $s3 = 'step-item';
    $width = '0%';

    if ($status === 'Menunggu') {
        $s1 .= ' active';
        $width = '15%';
    } else if ($status === 'Proses') {
        $s1 .= ' completed';
        $s2 .= ' active';
        $width = '50%';
    } else if ($status === 'Selesai') {
        $s1 .= ' completed';
        $s2 .= ' completed';
        $s3 .= ' completed';
        $width = '100%';
    }

    $c1 = ($status === 'Menunggu') ? '1' : '✓';
    $c2 = ($status === 'Selesai') ? '✓' : '2';
    $c3 = ($status === 'Selesai') ? '✓' : '3';

    return '
    <div class="stepper-container">
      <div class="stepper-progress" style="width: ' . $width . ';"></div>
      <div class="' . $s1 . '">
        <div class="step-circle">' . $c1 . '</div>
        <div class="step-label">Menunggu Verifikasi</div>
      </div>
      <div class="' . $s2 . '">
        <div class="step-circle">' . $c2 . '</div>
        <div class="step-label">Sedang Diproses</div>
      </div>
      <div class="' . $s3 . '">
        <div class="step-circle">' . $c3 . '</div>
        <div class="step-label">Perbaikan Selesai</div>
      </div>
    </div>';
}
