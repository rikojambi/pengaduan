<?php
/**
 * CETAK LAPORAN REKAPITULASI RESMI (STANDAR UKK RPL)
 * Aplikasi Pengaduan Sarana Sekolah
 */
session_start();
require_once "koneksi.php";

// Pastikan sesi admin aktif untuk mencetak laporan
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Filter opsional jika diteruskan via GET
$filter_bulan = trim($_GET['bulan'] ?? '');
$filter_kategori = intval($_GET['kategori'] ?? 0);

$conditions = [];
$params = [];
$types = "";

if (!empty($filter_bulan)) {
    $conditions[] = "DATE_FORMAT(ia.tanggal_pelaporan, '%Y-%m') = ?";
    $params[] = $filter_bulan;
    $types .= "s";
}

if ($filter_kategori > 0) {
    $conditions[] = "ia.id_kategori = ?";
    $params[] = $filter_kategori;
    $types .= "i";
}

$sql = "
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
";

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY ia.id_pelaporan ASC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt && count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
if ($stmt) {
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($conn, $sql);
}

// Hitung rekap statistik
$total = mysqli_num_rows($res);
$c_menunggu = 0;
$c_proses = 0;
$c_selesai = 0;

$data_rows = [];
while ($r = mysqli_fetch_assoc($res)) {
    $data_rows[] = $r;
    if ($r['status'] === 'Menunggu') $c_menunggu++;
    else if ($r['status'] === 'Proses') $c_proses++;
    else if ($r['status'] === 'Selesai') $c_selesai++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pengaduan Sarana Sekolah - UKK RPL</title>
    <style>
        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            color: #111;
            background: #fff;
            margin: 0;
            padding: 20px 30px;
            font-size: 11pt;
            line-height: 1.4;
        }

        /* Kop Surat Resmi */
        .kop-surat {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 20px;
            position: relative;
        }

        .kop-surat h3 {
            margin: 0;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kop-surat h2 {
            margin: 4px 0;
            font-size: 17pt;
            font-weight: 800;
            text-transform: uppercase;
        }

        .kop-surat p {
            margin: 2px 0;
            font-size: 9.5pt;
            color: #333;
        }

        /* Judul Dokumen */
        .judul-dokumen {
            text-align: center;
            margin: 20px 0 15px;
        }

        .judul-dokumen h4 {
            margin: 0;
            font-size: 12.5pt;
            text-transform: uppercase;
            text-decoration: underline;
        }

        .judul-dokumen span {
            font-size: 9pt;
            color: #555;
        }

        /* Summary Box */
        .rekap-box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 9.5pt;
            background: #f8fafc;
            border: 1px solid #ccc;
            padding: 8px 15px;
            border-radius: 4px;
        }

        /* Tabel Laporan */
        table.tabel-laporan {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9pt;
        }

        table.tabel-laporan th, 
        table.tabel-laporan td {
            border: 1px solid #444;
            padding: 6px 8px;
            vertical-align: top;
        }

        table.tabel-laporan th {
            background-color: #e2e8f0;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5pt;
        }

        /* Tanda Tangan */
        .ttd-wrapper {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .ttd-box {
            width: 260px;
            text-align: center;
            font-size: 10pt;
        }

        .ttd-space {
            height: 70px;
        }

        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }

        /* Tombol Aksi Layar */
        .action-bar {
            background: #f1f5f9;
            padding: 10px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-print {
            background: #2563eb;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            font-size: 10pt;
        }

        .btn-print:hover {
            background: #1d4ed8;
        }

        @media print {
            body {
                padding: 0;
            }
            .action-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- BAR AKSI LAYAR (Disembunyikan saat dicetak) -->
    <div class="action-bar">
        <div>
            <strong>Mode Cetak Laporan Resmi:</strong> Pastikan printer atau opsi "Save as PDF" sudah terpilih.
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn-print" onclick="window.print()">🖨️ Cetak Dokumen (Print)</button>
            <button class="btn-print" style="background:#64748b;" onclick="window.close()">Tutup</button>
        </div>
    </div>

    <!-- KOP SURAT RESMI -->
    <div class="kop-surat">
        <h3>PEMERINTAH DAERAH PROVINSI — DINAS PENDIDIKAN</h3>
        <h2>SMK NEGERI REKAYASA PERANGKAT LUNAK</h2>
        <p>Bidang Keahlian: Teknologi Informasi dan Komunikasi | Program Keahlian: Pengembangan Perangkat Lunak dan Gim</p>
        <p>Jl. Pendidikan No. 45, Kompleks Sarana Edukasi | Telp: (021) 7890123 | Email: sarpras@smkrpl.sch.id</p>
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="judul-dokumen">
        <h4>REKAPITULASI LAPORAN PENGADUAN & ASPIRASI SARANA PRASARANA SEKOLAH</h4>
        <span>Tanggal Cetak: <?= tgl_indo(date('Y-m-d')) ?> | Sumber Data: Database Sistem Informasi Sarpras</span>
    </div>

    <!-- REKAPITULASI STATISTIK -->
    <div class="rekap-box">
        <div><strong>Total Pengaduan:</strong> <?= $total ?> Laporan</div>
        <div><strong>Menunggu Verifikasi:</strong> <?= $c_menunggu ?></div>
        <div><strong>Sedang Diproses:</strong> <?= $c_proses ?></div>
        <div><strong>Selesai Diperbaiki:</strong> <?= $c_selesai ?></div>
    </div>

    <!-- TABEL DATA LAPORAN -->
    <table class="tabel-laporan">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 55px;">ID Lapor</th>
                <th style="width: 75px;">Tgl Lapor</th>
                <th style="width: 140px;">Siswa Pelapor</th>
                <th style="width: 110px;">Kategori Sarana</th>
                <th style="width: 130px;">Lokasi Sarana</th>
                <th>Keterangan Kerusakan</th>
                <th style="width: 65px;">Status</th>
                <th>Tindakan / Feedback Sarpras</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($data_rows) === 0): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">Tidak ada data pengaduan yang tercatat.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($data_rows as $row): ?>
                    <tr>
                        <td style="text-align: center;"><?= $no++ ?></td>
                        <td style="text-align: center; font-weight: bold;">#<?= $row['id_pelaporan'] ?></td>
                        <td style="text-align: center;"><?= tgl_indo($row['tanggal_pelaporan']) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_siswa']) ?></strong><br>
                            <span style="font-size: 8pt; color: #555;">NIS: <?= $row['nis'] ?> (<?= htmlspecialchars($row['kelas']) ?>)</span>
                        </td>
                        <td><?= htmlspecialchars($row['ket_kategori']) ?></td>
                        <td><?= htmlspecialchars($row['lokasi']) ?></td>
                        <td><?= htmlspecialchars($row['ket']) ?></td>
                        <td style="text-align: center; font-weight: bold;"><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= htmlspecialchars($row['feedback']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- TANDA TANGAN RESMI -->
    <div class="ttd-wrapper">
        <div class="ttd-box">
            <p>Mengetahui,<br>Kepala Sekolah</p>
            <div class="ttd-space"></div>
            <p class="ttd-nama">Drs. H. Mulyadi, M.Pd</p>
            <p style="margin: 0; font-size: 8.5pt;">NIP. 19750812 200003 1 002</p>
        </div>

        <div class="ttd-box">
            <p>Kota Pembelajaran, <?= tgl_indo(date('Y-m-d')) ?><br>Petugas Sarana & Prasarana</p>
            <div class="ttd-space"></div>
            <p class="ttd-nama"><?= htmlspecialchars($_SESSION['nama_petugas'] ?? 'Administrator Sarpras') ?></p>
            <p style="margin: 0; font-size: 8.5pt;">Penanggung Jawab Sarpras</p>
        </div>
    </div>

</body>
</html>
