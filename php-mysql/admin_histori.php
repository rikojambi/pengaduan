<?php
/**
 * REKAP HISTORI PENGADUAN LENGKAP (ADMIN)
 * Aplikasi Pengaduan Sarana Sekolah (UKK RPL)
 */
session_start();
require_once "koneksi.php";

// Cek autentikasi sesi admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$nama_petugas = $_SESSION['nama_petugas'] ?? 'Administrator Sarpras';

// Query semua histori pengaduan
$sql_histori = "
    SELECT 
        ia.id_pelaporan,
        ia.nis,
        s.nama AS nama_siswa,
        s.kelas,
        ia.lokasi,
        ia.ket,
        ia.tanggal_pelaporan,
        k.ket_kategori,
        COALESCE(a.status, 'Menunggu') AS status,
        COALESCE(a.feedback, '-') AS feedback,
        a.tanggal_feedback
    FROM input_aspirasi ia
    JOIN siswa s ON ia.nis = s.nis
    JOIN kategori k ON ia.id_kategori = k.id_kategori
    LEFT JOIN aspirasi a ON ia.id_pelaporan = a.id_pelaporan
    ORDER BY ia.id_pelaporan DESC
";
$res_histori = mysqli_query($conn, $sql_histori);
$total_data = mysqli_num_rows($res_histori);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori Aspirasi - Aplikasi Pengaduan Sarana Sekolah</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <!-- SITE HEADER -->
    <header class="site-header">
        <div class="container">
            <nav class="navbar">
                <div class="brand">
                    <div class="brand-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                    </div>
                    <div class="brand-text">
                        <h1>SI-PENGADUAN SARPRAS</h1>
                        <p>Rekam Jejak & Histori Aspirasi Siswa</p>
                    </div>
                </div>

                <div class="nav-actions">
                    <div class="role-pill">
                        <span class="role-badge admin">ADMIN</span>
                        <span style="font-weight: 600;"><?= htmlspecialchars($nama_petugas) ?></span>
                    </div>

                    <a href="logout.php" class="btn btn-secondary btn-sm">🚪 Keluar</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- TAB SUB-NAV -->
    <div class="app-tabs-wrapper no-print">
        <div class="container">
            <ul class="app-tabs">
                <li><a href="admin_dashboard.php" class="tab-btn">📊 Daftar Aspirasi & Filter</a></li>
                <li><a href="admin_histori.php" class="tab-btn active">📜 Rekap Histori & Log Selesai</a></li>
                <li><a href="cetak_laporan.php" target="_blank" class="tab-btn">🖨️ Cetak Rekap Laporan Resmi</a></li>
            </ul>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="container">

            <div class="section-header">
                <div>
                    <h2>Rekam Jejak (Histori) Pengaduan Sarana</h2>
                    <p>Log komprehensif seluruh pengaduan yang masuk beserta riwayat umpan balik dan status akhir perbaikan.</p>
                </div>
                <div>
                    <a href="cetak_laporan.php" target="_blank" class="btn btn-primary">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 6 2 18 2 18 9"></polyline>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <rect x="6" y="14" width="12" height="8"></rect>
                        </svg>
                        Cetak Format Dokumen Resmi
                    </a>
                </div>
            </div>

            <div class="card" style="padding: 1rem 0;">
                <div class="table-responsive" style="border: none;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center;">No</th>
                                <th>ID</th>
                                <th>Tgl Lapor</th>
                                <th>Pelapor (NIS / Kelas)</th>
                                <th>Kategori</th>
                                <th>Lokasi Sarana</th>
                                <th>Deskripsi Kerusakan</th>
                                <th>Status Akhir</th>
                                <th>Tindakan / Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_data === 0): ?>
                                <tr>
                                    <td colspan="9" class="empty-state" style="padding: 3rem;">
                                        <div class="empty-state-icon">📭</div>
                                        <h4>Belum ada rekaman histori pengaduan.</h4>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_histori)): ?>
                                    <tr>
                                        <td style="text-align: center; font-weight: 600;"><?= $no++ ?></td>
                                        <td><strong>#<?= $row['id_pelaporan'] ?></strong></td>
                                        <td style="white-space: nowrap;"><?= tgl_indo($row['tanggal_pelaporan']) ?></td>
                                        <td>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= $row['nis'] ?> • <?= htmlspecialchars($row['kelas']) ?></div>
                                        </td>
                                        <td><span class="aspirasi-category"><?= htmlspecialchars($row['ket_kategori']) ?></span></td>
                                        <td><strong><?= htmlspecialchars($row['lokasi']) ?></strong></td>
                                        <td><?= htmlspecialchars($row['ket']) ?></td>
                                        <td><?= render_badge_status($row['status']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($row['feedback']) ?>
                                            <?php if (!empty($row['tanggal_feedback'])): ?>
                                                <div style="font-size: 0.72rem; color: #94a3b8; margin-top: 0.2rem;">
                                                    Update: <?= tgl_indo($row['tanggal_feedback']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">
            <p>&copy; 2026 Aplikasi Pengaduan Sarana Sekolah (PHP Native & MySQL) — Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak.</p>
        </div>
    </footer>

</body>
</html>
