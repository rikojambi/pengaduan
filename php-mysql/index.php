<?php
/**
 * INDEX ENTRYPOINT
 * Mengarahkan pengguna sesuai sesi login aktif
 */
session_start();

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit();
    } else if ($_SESSION['role'] === 'siswa') {
        header("Location: siswa_dashboard.php");
        exit();
    }
}

header("Location: login.php");
exit();
