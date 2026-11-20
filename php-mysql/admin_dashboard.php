<?php
/**
 * DASHBOARD ADMIN & FILTER ASPIRASI
 * Aplikasi Pengaduan Sarana Sekolah (UKK RPL)
 */
session_start();
require_once "koneksi.php";

// Cek autentikasi sesi admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$nama_petugas = $_SESSION['nama_petugas'] ?? 'Administrator Sarpras';

// -------------------------------------------------------------
// 1. HITUNG STATISTIK KESELURUHAN (METRICS)
// -------------------------------------------------------------
$stat_total_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM input_aspirasi");
$total_laporan = mysqli_fetch_assoc($stat_total_query)['total'];

$stat_menunggu_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM input_aspirasi ia 
    LEFT JOIN aspirasi a ON ia.id_pelaporan = a.id_pelaporan 
    WHERE a.status = 'Menunggu' OR a.status IS NULL
");
$total_menunggu = mysqli_fetch_assoc($stat_menunggu_query)['total'];

$stat_proses_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM input_aspirasi ia 
    JOIN aspirasi a ON ia.id_pelaporan = a.id_pelaporan 
    WHERE a.status = 'Proses'
");
$total_proses = mysqli_fetch_assoc($stat_proses_query)['total'];

$stat_selesai_query = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM input_aspirasi ia 
    JOIN aspirasi a ON ia.id_pelaporan = a.id_pelaporan 
    WHERE a.status = 'Selesai'
");
$total_selesai = mysqli_fetch_assoc($stat_selesai_query)['total'];

$persen_selesai = $total_laporan > 0 ? round(($total_selesai / $total_laporan) * 100) : 0;

// -------------------------------------------------------------
// 2. QUERY DAFTAR KATEGORI & SISWA UNTUK OPSI FILTER
// -------------------------------------------------------------
$res_kategori_opt = mysqli_query($conn, "SELECT id_kategori, ket_kategori FROM kategori ORDER BY ket_kategori ASC");
$res_siswa_opt = mysqli_query($conn, "SELECT nis, nama, kelas FROM siswa ORDER BY nama ASC");

// -------------------------------------------------------------
// 3. LOGIKA FILTERING MULTI-PARAMETER (UKK RPL)
// -------------------------------------------------------------
$filter_tanggal = trim($_GET['tanggal'] ?? '');
$filter_bulan   = trim($_GET['bulan'] ?? '');
$filter_nis     = intval($_GET['nis'] ?? 0);
$filter_kategori= intval($_GET['kategori'] ?? 0);
$filter_keyword = trim($_GET['q'] ?? '');

$conditions = [];
$params = [];
$types = "";

// Filter per tanggal
if (!empty($filter_tanggal)) {
    $conditions[] = "ia.tanggal_pelaporan = ?";
    $params[] = $filter_tanggal;
    $types .= "s";
}

// Filter per bulan (format: YYYY-MM)
if (!empty($filter_bulan)) {
    $conditions[] = "DATE_FORMAT(ia.tanggal_pelaporan, '%Y-%m') = ?";
    $params[] = $filter_bulan;
    $types .= "s";
}

// Filter per siswa (NIS)
if ($filter_nis > 0) {
    $conditions[] = "ia.nis = ?";
    $params[] = $filter_nis;
    $types .= "i";
}

// Filter per kategori
if ($filter_kategori > 0) {
    $conditions[] = "ia.id_kategori = ?";
    $params[] = $filter_kategori;
    $types .= "i";
}

// Filter kata kunci (Search)
if (!empty($filter_keyword)) {
    $conditions[] = "(ia.lokasi LIKE ? OR ia.ket LIKE ? OR s.nama LIKE ? OR s.kelas LIKE ? OR k.ket_kategori LIKE ?)";
    $like = "%" . $filter_keyword . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sssss";
}

// Susun Query Utama
$sql = "
    SELECT 
        ia.id_pelaporan,
        ia.nis,
        s.nama AS nama_siswa,
        s.kelas,
        ia.lokasi,
        ia.ket,
        ia.tanggal_pelaporan,
        k.id_kategori,
        k.ket_kategori,
        COALESCE(a.status, 'Menunggu') AS status,
        COALESCE(a.feedback, '') AS feedback,
        a.tanggal_feedback
    FROM input_aspirasi ia
    JOIN siswa s ON ia.nis = s.nis
    JOIN kategori k ON ia.id_kategori = k.id_kategori
    LEFT JOIN aspirasi a ON ia.id_pelaporan = a.id_pelaporan
";

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY ia.id_pelaporan DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt && count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $res_aspirasi = mysqli_stmt_get_result($stmt);
} else {
    $res_aspirasi = mysqli_query($conn, $sql);
}

$jumlah_hasil = mysqli_num_rows($res_aspirasi);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Aplikasi Pengaduan Sarana Sekolah</title>
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
                        <p>Dashboard Manajemen Aspirasi & Pengaduan Sarana — Panel Admin</p>
                    </div>
                </div>

                <div class="nav-actions">
                    <div class="role-pill">
                        <span class="role-badge admin">ADMIN</span>
                        <span style="font-weight: 600;"><?= htmlspecialchars($nama_petugas) ?></span>
                    </div>

                    <a href="logout.php" class="btn btn-secondary btn-sm" onclick="return confirm('Keluar dari sistem admin?')">
                        🚪 Keluar
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <!-- TAB SUB-NAV -->
    <div class="app-tabs-wrapper no-print">
        <div class="container">
            <ul class="app-tabs">
                <li><a href="admin_dashboard.php" class="tab-btn active">📊 Daftar Aspirasi & Filter</a></li>
                <li><a href="admin_histori.php" class="tab-btn">📜 Rekap Histori & Log Selesai</a></li>
                <li><a href="cetak_laporan.php" target="_blank" class="tab-btn">🖨️ Cetak Rekap Laporan Resmi</a></li>
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

            <div class="section-header">
                <div>
                    <h2>Manajemen Pengaduan Sarana Sekolah</h2>
                    <p>Pantau semua masukan siswa, lakukan filter pencarian data, serta perbarui status dan umpan balik pengerjaan.</p>
                </div>
                <div>
                    <a href="cetak_laporan.php" target="_blank" class="btn btn-primary btn-sm">
                        🖨️ Cetak Laporan (Print)
                    </a>
                </div>
            </div>

            <!-- KARTU STATISTIK (METRICS) -->
            <div class="grid-stats">
                <div class="stat-card">
                    <div class="stat-icon blue">📊</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $total_laporan ?></div>
                        <div class="stat-label">Total Pengaduan Masuk</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon amber">⏳</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $total_menunggu ?></div>
                        <div class="stat-label">Status Menunggu</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon cyan">🔧</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $total_proses ?></div>
                        <div class="stat-label">Sedang Diproses</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $total_selesai ?></div>
                        <div class="stat-label">Selesai Diperbaiki</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue">📈</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $persen_selesai ?>%</div>
                        <div class="stat-label">Tingkat Penyelesaian</div>
                    </div>
                </div>
            </div>

            <!-- CARD FILTER INTERAKTIF (Spesifikasi UKK: Tanggal, Bulan, Siswa, Kategori, Keyword) -->
            <div class="filter-card">
                <div style="font-weight: 700; margin-bottom: 0.85rem; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.95rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        Filter & Pencarian Pengaduan (Spesifikasi UKK RPL)
                    </div>
                    <span style="font-size: 0.82rem; color: var(--text-muted);">
                        Menampilkan <strong><?= $jumlah_hasil ?></strong> dari <?= $total_laporan ?> data
                    </span>
                </div>

                <form method="GET" action="admin_dashboard.php">
                    <div class="filter-grid">
                        <!-- Filter Tanggal -->
                        <div class="form-group">
                            <label class="form-label" for="filterTanggal">Per Tanggal</label>
                            <input type="date" id="filterTanggal" name="tanggal" class="form-control" value="<?= htmlspecialchars($filter_tanggal) ?>">
                        </div>

                        <!-- Filter Bulan -->
                        <div class="form-group">
                            <label class="form-label" for="filterBulan">Per Bulan</label>
                            <select id="filterBulan" name="bulan" class="form-control">
                                <option value="">Semua Bulan</option>
                                <option value="2026-10" <?= $filter_bulan === '2026-10' ? 'selected' : '' ?>>Oktober 2026</option>
                                <option value="2026-11" <?= $filter_bulan === '2026-11' ? 'selected' : '' ?>>November 2026</option>
                                <option value="2026-12" <?= $filter_bulan === '2026-12' ? 'selected' : '' ?>>Desember 2026</option>
                                <option value="2026-09" <?= $filter_bulan === '2026-09' ? 'selected' : '' ?>>September 2026</option>
                                <option value="2026-08" <?= $filter_bulan === '2026-08' ? 'selected' : '' ?>>Agustus 2026</option>
                            </select>
                        </div>

                        <!-- Filter Siswa -->
                        <div class="form-group">
                            <label class="form-label" for="filterSiswa">Per Siswa</label>
                            <select id="filterSiswa" name="nis" class="form-control">
                                <option value="">Semua Siswa</option>
                                <?php while ($s = mysqli_fetch_assoc($res_siswa_opt)): ?>
                                    <option value="<?= $s['nis'] ?>" <?= $filter_nis == $s['nis'] ? 'selected' : '' ?>>
                                        <?= $s['nis'] ?> - <?= htmlspecialchars($s['nama']) ?> (<?= htmlspecialchars($s['kelas']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Filter Kategori -->
                        <div class="form-group">
                            <label class="form-label" for="filterKat">Per Kategori</label>
                            <select id="filterKat" name="kategori" class="form-control">
                                <option value="">Semua Kategori</option>
                                <?php while ($k = mysqli_fetch_assoc($res_kategori_opt)): ?>
                                    <option value="<?= $k['id_kategori'] ?>" <?= $filter_kategori == $k['id_kategori'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($k['ket_kategori']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Pencarian Keyword -->
                        <div class="form-group">
                            <label class="form-label" for="filterQ">Cari Kata Kunci</label>
                            <input type="text" id="filterQ" name="q" class="form-control" placeholder="Ketik lokasi / keterangan..." value="<?= htmlspecialchars($filter_keyword) ?>">
                        </div>

                        <!-- Tombol Terapkan & Reset -->
                        <div class="form-group" style="justify-content: flex-end;">
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="submit" class="btn btn-primary" style="flex: 1;">
                                    🔍 Filter
                                </button>
                                <a href="admin_dashboard.php" class="btn btn-secondary" title="Reset Filter">
                                    🔄 Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- TABEL DAFTAR ASPIRASI KESELURUHAN -->
            <div class="card" style="padding: 1rem 0;">
                <div style="padding: 0 1.25rem 1rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="font-size: 1.05rem; font-weight: 700;">Tabel Aspirasi Sarana Masuk</h3>
                </div>

                <div class="table-responsive" style="border: none;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 45px; text-align: center;">No</th>
                                <th>ID & Tanggal</th>
                                <th>Siswa Pelapor</th>
                                <th>Kategori Sarana</th>
                                <th>Lokasi & Kerusakan</th>
                                <th>Status</th>
                                <th>Umpan Balik Terakhir</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($jumlah_hasil === 0): ?>
                                <tr>
                                    <td colspan="8" class="empty-state" style="padding: 3rem;">
                                        <div class="empty-state-icon">🔍</div>
                                        <h4>Tidak ada data aspirasi yang sesuai dengan kriteria filter.</h4>
                                        <p style="margin-top: 0.5rem;">Silakan atur ulang filter pencarian Anda.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; while ($row = mysqli_fetch_assoc($res_aspirasi)): ?>
                                    <tr>
                                        <td style="text-align: center; font-weight: 600;"><?= $no++ ?></td>
                                        <td>
                                            <span style="font-weight: 700; color: var(--primary);">#<?= $row['id_pelaporan'] ?></span>
                                            <div style="font-size: 0.75rem; color: var(--text-muted);"><?= tgl_indo($row['tanggal_pelaporan']) ?></div>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600;"><?= htmlspecialchars($row['nama_siswa']) ?></div>
                                            <div style="font-size: 0.78rem; color: var(--text-muted);">NIS: <?= $row['nis'] ?> (<?= htmlspecialchars($row['kelas']) ?>)</div>
                                        </td>
                                        <td>
                                            <span class="aspirasi-category"><?= htmlspecialchars($row['ket_kategori']) ?></span>
                                        </td>
                                        <td>
                                            <div style="font-weight: 600; color: var(--text-main);"><?= htmlspecialchars($row['lokasi']) ?></div>
                                            <div style="font-size: 0.8rem; color: #475569; max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['ket']) ?>">
                                                <?= htmlspecialchars($row['ket']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?= render_badge_status($row['status']) ?>
                                        </td>
                                        <td style="max-width: 220px;">
                                            <div style="font-size: 0.8rem; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($row['feedback']) ?>">
                                                <?= !empty($row['feedback']) ? htmlspecialchars($row['feedback']) : '<span style="color: #94a3b8;">Belum ada tanggapan</span>' ?>
                                            </div>
                                            <?php if (!empty($row['tanggal_feedback'])): ?>
                                                <div style="font-size: 0.72rem; color: #94a3b8;">Update: <?= tgl_indo($row['tanggal_feedback']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <button 
                                                class="btn btn-primary btn-sm" 
                                                onclick="openFeedbackModal(
                                                    '<?= $row['id_pelaporan'] ?>',
                                                    '<?= tgl_indo($row['tanggal_pelaporan']) ?>',
                                                    '<?= htmlspecialchars(addslashes($row['nama_siswa'])) ?>',
                                                    '<?= $row['nis'] ?>',
                                                    '<?= htmlspecialchars(addslashes($row['kelas'])) ?>',
                                                    '<?= htmlspecialchars(addslashes($row['ket_kategori'])) ?>',
                                                    '<?= htmlspecialchars(addslashes($row['lokasi'])) ?>',
                                                    '<?= htmlspecialchars(addslashes($row['ket'])) ?>',
                                                    '<?= $row['status'] ?>',
                                                    '<?= htmlspecialchars(addslashes($row['feedback'])) ?>'
                                                )">
                                                ✏️ Tanggapi
                                            </button>
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

    <!-- MODAL TANGGAPAN & UPDATE STATUS -->
    <div id="modalFeedbackOverlay" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header">
                <h3>Umpan Balik & Status Pengaduan Sarana</h3>
                <button type="button" class="btn-close" onclick="closeFeedbackModal()">✕</button>
            </div>

            <form action="admin_feedback.php" method="POST">
                <input type="hidden" name="id_pelaporan" id="modalInputIdPelaporan">

                <div class="modal-body">
                    <!-- Ringkasan Detail -->
                    <div class="detail-summary-card">
                        <div class="detail-row">
                            <div class="detail-label">ID Pelaporan:</div>
                            <div class="detail-val" id="modalTextId" style="color:var(--primary); font-weight:700;">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Tanggal Lapor:</div>
                            <div class="detail-val" id="modalTextTgl">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Siswa Pelapor:</div>
                            <div class="detail-val" id="modalTextSiswa">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Sarana & Lokasi:</div>
                            <div class="detail-val" id="modalTextLokasi">-</div>
                        </div>
                        <div class="detail-row" style="margin-top:0.4rem;">
                            <div class="detail-label">Keterangan:</div>
                            <div class="detail-val" id="modalTextKet" style="font-style:italic;">-</div>
                        </div>
                    </div>

                    <!-- Pilihan Status Baru -->
                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label class="form-label" for="modalSelectStatus">Ubah Status Penyelesaian <span class="required">*</span></label>
                        <select id="modalSelectStatus" name="status" class="form-control" required>
                            <option value="Menunggu">Menunggu (Verifikasi Awal Tim Sarpras)</option>
                            <option value="Proses">Proses (Dalam Pengerjaan / Pengadaan Suku Cadang)</option>
                            <option value="Selesai">Selesai (Sarana Telah Diperbaiki & Berfungsi Normal)</option>
                        </select>
                        <span class="form-help">Status ini langsung mempengaruhi indikator progres pada akun siswa.</span>
                    </div>

                    <!-- Input Feedback -->
                    <div class="form-group">
                        <label class="form-label" for="modalInputFeedback">Catatan Umpan Balik (Feedback) <span class="required">*</span></label>
                        <textarea id="modalInputFeedback" name="feedback" class="form-control" rows="4" placeholder="Tuliskan tindakan teknisi, informasi suku cadang, atau penyelesaian..." required></textarea>
                        <span class="form-help">Pesan umpan balik ini akan ditampilkan secara transparan di dashboard siswa.</span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeFeedbackModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        💾 Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">
            <p>&copy; 2026 Aplikasi Pengaduan Sarana Sekolah (PHP Native & MySQL) — Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak.</p>
        </div>
    </footer>

    <script>
    function openFeedbackModal(id, tgl, nama, nis, kelas, kategori, lokasi, ket, status, feedback) {
        document.getElementById('modalInputIdPelaporan').value = id;
        document.getElementById('modalTextId').textContent = '#' + id;
        document.getElementById('modalTextTgl').textContent = tgl;
        document.getElementById('modalTextSiswa').textContent = nama + ' (NIS: ' + nis + ' - ' + kelas + ')';
        document.getElementById('modalTextLokasi').textContent = kategori + ' • ' + lokasi;
        document.getElementById('modalTextKet').textContent = ket;

        document.getElementById('modalSelectStatus').value = status;
        document.getElementById('modalInputFeedback').value = feedback;

        document.getElementById('modalFeedbackOverlay').classList.add('open');
    }

    function closeFeedbackModal() {
        document.getElementById('modalFeedbackOverlay').classList.remove('open');
    }
    </script>

</body>
</html>
