<?php
/**
 * HANDLER FEEDBACK & UPDATE STATUS OLEH ADMIN
 * Aplikasi Pengaduan Sarana Sekolah (UKK RPL)
 */
session_start();
require_once "koneksi.php";

// Pastikan yang mengakses adalah admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pelaporan = intval($_POST['id_pelaporan'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $feedback = trim($_POST['feedback'] ?? '');
    $tanggal_feedback = date('Y-m-d');

    // Validasi enum status
    $allowed_status = ['Menunggu', 'Proses', 'Selesai'];
    if (!in_array($status, $allowed_status)) {
        $_SESSION['flash_error'] = "Status yang dipilih tidak valid!";
        header("Location: admin_dashboard.php");
        exit();
    }

    if (empty($feedback)) {
        $_SESSION['flash_error'] = "Catatan umpan balik (feedback) wajib diisi untuk memberi informasi kepada siswa pelapor!";
        header("Location: admin_dashboard.php");
        exit();
    }

    // Cek apakah data aspirasi untuk id_pelaporan sudah ada
    $check_stmt = mysqli_prepare($conn, "SELECT id_aspirasi FROM aspirasi WHERE id_pelaporan = ?");
    mysqli_stmt_bind_param($check_stmt, "i", $id_pelaporan);
    mysqli_stmt_execute($check_stmt);
    $res_check = mysqli_stmt_get_result($check_stmt);

    if ($row = mysqli_fetch_assoc($res_check)) {
        // UPDATE aspirasi yang sudah ada
        $update_stmt = mysqli_prepare($conn, "UPDATE aspirasi SET status = ?, feedback = ?, tanggal_feedback = ? WHERE id_pelaporan = ?");
        mysqli_stmt_bind_param($update_stmt, "sssi", $status, $feedback, $tanggal_feedback, $id_pelaporan);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['flash_success'] = "Status laporan #{$id_pelaporan} berhasil diubah menjadi \"{$status}\" beserta catatan feedback!";
        } else {
            $_SESSION['flash_error'] = "Gagal memperbarui data: " . mysqli_error($conn);
        }
    } else {
        // INSERT jika belum ada record aspirasi (fallback)
        // Ambil id_kategori dari input_aspirasi
        $kat_query = mysqli_query($conn, "SELECT id_kategori FROM input_aspirasi WHERE id_pelaporan = $id_pelaporan");
        $kat_row = mysqli_fetch_assoc($kat_query);
        $id_kategori = $kat_row ? $kat_row['id_kategori'] : 1;

        $ins_stmt = mysqli_prepare($conn, "INSERT INTO aspirasi (id_pelaporan, status, id_kategori, feedback, tanggal_feedback) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($ins_stmt, "isiss", $id_pelaporan, $status, $id_kategori, $feedback, $tanggal_feedback);
        
        if (mysqli_stmt_execute($ins_stmt)) {
            $_SESSION['flash_success'] = "Tanggapan berhasil disimpan untuk laporan #{$id_pelaporan}!";
        } else {
            $_SESSION['flash_error'] = "Gagal menyimpan data: " . mysqli_error($conn);
        }
    }

    // Redirect kembali ke halaman admin
    header("Location: admin_dashboard.php");
    exit();
}

header("Location: admin_dashboard.php");
exit();
