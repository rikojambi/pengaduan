<?php
/**
 * DASHBOARD SISWA (PORTAL PENGADUAN)
 * Aplikasi Pengaduan Sarana Sekolah (UKK RPL)
 */
session_start();
require_once "koneksi.php";

// Cek autentikasi sesi siswa
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: login.php");
    exit();
}

$nis = $_SESSION['nis'];
$nama = $_SESSION['nama'];
$kelas = $_SESSION['kelas'];

// Ambil daftar kategori dari database
$res_kategori = mysqli_query($conn, "SELECT id_kategori, ket_kategori FROM kategori ORDER BY id_kategori ASC");

// Ambil data riwayat pengaduan milik siswa ini (JOIN input_aspirasi + aspirasi + kategori)
$query_riwayat = "
    SELECT 
        ia.id_pelaporan,
        ia.nis,
        ia.lokasi,
        ia.ket,
        ia.tanggal_pelaporan,
        k.ket_kategori,
        COALESCE(a.status, 'Menunggu') AS status,
        COALESCE(a.feedback, 'Belum ada tanggapan teknisi.') AS feedback,
        a.tanggal_feedback
    FROM input_aspirasi ia
    JOIN kategori k ON ia.id_kategori = k.id_kategori
    LEFT JOIN aspirasi a ON ia.id_pelaporan = a.id_pelaporan
    WHERE ia.nis = ?
    ORDER BY ia.id_pelaporan DESC
";

$stmt = mysqli_prepare($conn, $query_riwayat);
mysqli_stmt_bind_param($stmt, "i", $nis);
mysqli_stmt_execute($stmt);
$res_riwayat = mysqli_stmt_get_result($stmt);

$total_laporan_siswa = mysqli_num_rows($res_riwayat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Siswa - Aplikasi Pengaduan Sarana Sekolah</title>
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
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </div>
                    <div class="brand-text">
                        <h1>SI-PENGADUAN SARPRAS</h1>
                        <p>Portal Pengaduan Sarana Sekolah — Panel Siswa</p>
                    </div>
                </div>

                <div class="nav-actions">
                    <div class="role-pill">
                        <span class="role-badge siswa">SISWA</span>
                        <span style="font-weight: 600;"><?= htmlspecialchars($nama) ?> (<?= htmlspecialchars($kelas) ?>)</span>
                    </div>

                    <a href="logout.php" class="btn btn-secondary btn-sm" onclick="return confirm('Keluar dari portal pengaduan?')">
                        🚪 Keluar
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- TAB SUB-NAV -->
    <div class="app-tabs-wrapper">
        <div class="container">
            <ul class="app-tabs">
                <li><a href="#form" class="tab-btn active" id="tabBtnForm" onclick="showSection('form')">📝 Formulir Aspirasi</a></li>
                <li><a href="#riwayat" class="tab-btn" id="tabBtnRiwayat" onclick="showSection('riwayat')">📋 Riwayat & Status (<?= $total_laporan_siswa ?>)</a></li>
            </ul>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="container">

            <!-- NOTIFIKASI FLASH MESSAGE -->
            <?php if (isset($_SESSION['flash_success'])): ?>
                <div class="card" style="background:#dcfce7; border-color:#86efac; color:#166534; padding:1rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.75rem;">
                    <span style="font-size:1.4rem;">✅</span>
                    <div>
                        <strong>Sukses:</strong> <?= htmlspecialchars($_SESSION['flash_success']); ?>
                    </div>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['flash_error'])): ?>
                <div class="card" style="background:#fee2e2; border-color:#fca5a5; color:#991b1b; padding:1rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.75rem;">
                    <span style="font-size:1.4rem;">⚠️</span>
                    <div>
                        <strong>Peringatan:</strong> <?= htmlspecialchars($_SESSION['flash_error']); ?>
                    </div>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <!-- SECTION 1: FORMULIR PENGADUAN SISWA -->
            <section id="section-form" class="view-section active">
                <div class="section-header">
                    <div>
                        <h2>Formulir Aspirasi & Pengaduan Sarana</h2>
                        <p>Laporkan fasilitas sekolah yang rusak, butuh perawatan, atau perbaikan demi kenyamanan belajar.</p>
                    </div>
                </div>

                <div class="card" style="max-width: 860px; margin: 0 auto;">
                    <h3 class="card-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Input Pengaduan Baru
                    </h3>

                    <form action="siswa_proses.php" method="POST">
                        <div class="form-grid">
                            <!-- Identitas Siswa -->
                            <div class="form-group">
                                <label class="form-label">Nomor Induk Siswa (NIS)</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($nis) ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Kelas Siswa</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($kelas) ?>" readonly>
                            </div>

                            <!-- Pilihan Kategori Sarana -->
                            <div class="form-group full-width">
                                <label class="form-label" for="id_kategori">Kategori Sarana Sekolah <span class="required">*</span></label>
                                <select id="id_kategori" name="id_kategori" class="form-control" required>
                                    <option value="">-- Pilih Kategori Sarana --</option>
                                    <?php while ($kat = mysqli_fetch_assoc($res_kategori)): ?>
                                        <option value="<?= $kat['id_kategori'] ?>"><?= htmlspecialchars($kat['ket_kategori']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Lokasi Spesifik -->
                            <div class="form-group full-width">
                                <label class="form-label" for="lokasi">Lokasi Spesifik Sarana <span class="required">*</span></label>
                                <input type="text" id="lokasi" name="lokasi" class="form-control" placeholder="Contoh: Gedung B Lantai 2 Kelas XII RPL 1, Lab RPL Meja 12" required>
                                <span class="form-help">Sebutkan nama ruangan, lantai, gedung, atau posisi sarana secara jelas.</span>
                            </div>

                            <!-- Keterangan Kerusakan -->
                            <div class="form-group full-width">
                                <label class="form-label" for="ket">Keterangan Kerusakan / Pengaduan <span class="required">*</span></label>
                                <textarea id="ket" name="ket" class="form-control" rows="4" placeholder="Jelaskan kendala atau kerusakan sarana secara rinci..." required></textarea>
                                <span class="form-help">Minimal 8 karakter. Masukan yang detail akan mempercepat proses penanganan teknisi.</span>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                            <button type="reset" class="btn btn-secondary">Bersihkan</button>
                            <button type="submit" class="btn btn-primary">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                </svg>
                                Kirim Pengaduan Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <!-- SECTION 2: RIWAYAT & STATUS PENGADUAN SISWA -->
            <section id="section-riwayat" class="view-section">
                <div class="section-header">
                    <div>
                        <h2>Riwayat & Status Pengaduan Saya</h2>
                        <p>Daftar seluruh aspirasi yang pernah Anda laporkan, lengkap dengan progres penyelesaian dan tanggapan admin.</p>
                    </div>
                    <div>
                        <span class="badge-status badge-proses" style="font-size:0.85rem; padding:0.4rem 0.8rem;">
                            <?= $total_laporan_siswa ?> Pengaduan Dilaporkan
                        </span>
                    </div>
                </div>

                <div class="aspirasi-card-list">
                    <?php if ($total_laporan_siswa === 0): ?>
                        <div class="empty-state card">
                            <div class="empty-state-icon">📭</div>
                            <h3>Belum Ada Pengaduan</h3>
                            <p>Anda belum pernah mengirimkan laporan sarana. Buka menu Formulir Aspirasi untuk melaporkan sarana rusak.</p>
                        </div>
                    <?php else: ?>
                        <?php while ($row = mysqli_fetch_assoc($res_riwayat)): ?>
                            <div class="aspirasi-card">
                                <div class="aspirasi-header">
                                    <div>
                                        <span class="aspirasi-category"><?= htmlspecialchars($row['ket_kategori']) ?></span>
                                        <div class="aspirasi-location" style="margin-top: 0.4rem;">📍 <?= htmlspecialchars($row['lokasi']) ?></div>
                                    </div>
                                    <div>
                                        <?= render_badge_status($row['status']) ?>
                                    </div>
                                </div>

                                <div class="aspirasi-meta">
                                    <span><strong>ID Laporan:</strong> #<?= $row['id_pelaporan'] ?></span>
                                    <span>•</span>
                                    <span>📅 Tanggal: <?= tgl_indo($row['tanggal_pelaporan']) ?></span>
                                </div>

                                <div class="aspirasi-desc" style="margin-top: 0.6rem;">
                                    <?= nl2br(htmlspecialchars($row['ket'])) ?>
                                </div>

                                <!-- Stepper Visual Progres -->
                                <?= render_stepper($row['status']) ?>

                                <!-- Kotak Umpan Balik Admin -->
                                <div class="feedback-box">
                                    <div class="feedback-box-header">
                                        <span>💬 Tanggapan / Umpan Balik Tim Sarpras:</span>
                                        <?php if (!empty($row['tanggal_feedback'])): ?>
                                            <span style="font-weight: normal; font-size: 0.75rem; color: #64748b; margin-left: auto;">
                                                (<?= tgl_indo($row['tanggal_feedback']) ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="feedback-text">
                                        "<?= htmlspecialchars($row['feedback']) ?>"
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">
            <p>&copy; 2026 Aplikasi Pengaduan Sarana Sekolah (PHP Native & MySQL) — Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak.</p>
        </div>
    </footer>

    <script>
    function showSection(name) {
        document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

        if (name === 'riwayat') {
            document.getElementById('section-riwayat').classList.add('active');
            document.getElementById('tabBtnRiwayat').classList.add('active');
        } else {
            document.getElementById('section-form').classList.add('active');
            document.getElementById('tabBtnForm').classList.add('active');
        }
    }

    // Periksa hash di URL jika diarahkan ke #riwayat
    if (window.location.hash === '#riwayat') {
        showSection('riwayat');
    }
    </script>

</body>
</html>
