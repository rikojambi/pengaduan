<?php
/**
 * HANDLER POST PENGADUAN ASPIRASI SISWA
 * Aplikasi Pengaduan Sarana Sekolah (UKK RPL)
 */
session_start();
require_once "koneksi.php";

// Pastikan yang mengakses adalah siswa yang telah login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = $_SESSION['nis'];
    $id_kategori = intval($_POST['id_kategori'] ?? 0);
    $lokasi = trim($_POST['lokasi'] ?? '');
    $ket = trim($_POST['ket'] ?? '');
    $tanggal_pelaporan = date('Y-m-d');

    // Validasi input
    if ($id_kategori <= 0 || empty($lokasi) || empty($ket)) {
        $_SESSION['flash_error'] = "Semua field formulir pengaduan wajib diisi secara lengkap!";
        header("Location: siswa_dashboard.php");
        exit();
    }

    if (strlen($ket) < 8) {
        $_SESSION['flash_error'] = "Keterangan pengaduan harus diisi minimal 8 karakter!";
        header("Location: siswa_dashboard.php");
        exit();
    }

    // Mulai Database Transaction untuk integritas data kedua tabel (input_aspirasi & aspirasi)
    mysqli_begin_transaction($conn);

    try {
        // 1. Simpan ke tabel input_aspirasi
        $stmt1 = mysqli_prepare($conn, "INSERT INTO input_aspirasi (nis, id_kategori, lokasi, ket, tanggal_pelaporan) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt1, "iisss", $nis, $id_kategori, $lokasi, $ket, $tanggal_pelaporan);
        
        if (!mysqli_stmt_execute($stmt1)) {
            throw new Exception("Gagal menyimpan ke tabel input_aspirasi: " . mysqli_stmt_error($stmt1));
        }

        $id_pelaporan_baru = mysqli_insert_id($conn);

        // 2. Simpan status awal ke tabel aspirasi (Status: 'Menunggu', feedback awal default)
        $default_status = 'Menunggu';
        $default_feedback = 'Pengaduan baru diterima sistem. Menunggu verifikasi tim sarpras.';
        
        $stmt2 = mysqli_prepare($conn, "INSERT INTO aspirasi (id_pelaporan, status, id_kategori, feedback, tanggal_feedback) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt2, "isiss", $id_pelaporan_baru, $default_status, $id_kategori, $default_feedback, $tanggal_pelaporan);
        
        if (!mysqli_stmt_execute($stmt2)) {
            throw new Exception("Gagal menyimpan ke tabel aspirasi: " . mysqli_stmt_error($stmt2));
        }

        // Commit transaksi
        mysqli_commit($conn);
        $_SESSION['flash_success'] = "Pengaduan sarana berhasil dikirim dengan Nomor Laporan #" . $id_pelaporan_baru . "! Tim sarpras akan segera memverifikasi.";

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['flash_error'] = "Terjadi kesalahan: " . $e->getMessage();
    }

    header("Location: siswa_dashboard.php#riwayat");
    exit();
}

header("Location: siswa_dashboard.php");
exit();
