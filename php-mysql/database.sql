-- ============================================================================
-- DATABASE SKEMA: APLIKASI PENGADUAN SARANA SEKOLAH
-- Standar Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `db_pengaduan_sarana` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_pengaduan_sarana`;

-- ----------------------------------------------------------------------------
-- 1. TABEL ADMIN
-- Menyimpan kredensial login akun administrator / petugas sarpras
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `nama_petugas` VARCHAR(100) DEFAULT 'Administrator Sarpras',
  PRIMARY KEY (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kredensial default admin: username=admin, password=password123 (juga didukung plain/hash)
INSERT INTO `admin` (`username`, `password`, `nama_petugas`) VALUES
('admin', 'password123', 'Bpk. Hendra Wijaya, S.Kom (Kepala Sarpras)'),
('petugas', 'password123', 'Ibu Ratna Dewi (Staff Teknisi Sarpras)');


-- ----------------------------------------------------------------------------
-- 2. TABEL SISWA
-- Menyimpan identitas siswa (nis sebagai primary key, nama, kelas)
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `siswa`;
CREATE TABLE `siswa` (
  `nis` INT(11) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `kelas` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`nis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `siswa` (`nis`, `nama`, `kelas`) VALUES
(1024001, 'Ahmad Fauzi', 'XII RPL 1'),
(1024002, 'Siti Nurhaliza', 'XII RPL 2'),
(1024003, 'Budi Santoso', 'XI RPL 1'),
(1024004, 'Dewi Lestari', 'XI RPL 2'),
(1024005, 'Rian Pratama', 'X PPLG 1');


-- ----------------------------------------------------------------------------
-- 3. TABEL KATEGORI
-- Menyimpan daftar kategori sarana & prasarana sekolah
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `kategori`;
CREATE TABLE `kategori` (
  `id_kategori` INT(11) NOT NULL AUTO_INCREMENT,
  `ket_kategori` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `kategori` (`id_kategori`, `ket_kategori`) VALUES
(1, 'Ruang Kelas & Mebelair'),
(2, 'Laboratorium Komputer & Jaringan'),
(3, 'Toilet & Sanitasi Siswa'),
(4, 'Perpustakaan'),
(5, 'Lapangan Olahraga & Outdoor'),
(6, 'Listrik & Pendingin Ruangan (AC)'),
(7, 'Kantin & Area Terbuka Hijau');


-- ----------------------------------------------------------------------------
-- 4. TABEL INPUT ASPIRASI
-- Menyimpan data pelaporan pengaduan sarana yang dikirim siswa
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `input_aspirasi`;
CREATE TABLE `input_aspirasi` (
  `id_pelaporan` INT(11) NOT NULL AUTO_INCREMENT,
  `nis` INT(11) NOT NULL,
  `id_kategori` INT(11) NOT NULL,
  `lokasi` VARCHAR(150) NOT NULL,
  `ket` TEXT NOT NULL,
  `tanggal_pelaporan` DATE NOT NULL,
  PRIMARY KEY (`id_pelaporan`),
  KEY `fk_input_siswa` (`nis`),
  KEY `fk_input_kategori` (`id_kategori`),
  CONSTRAINT `fk_input_siswa` FOREIGN KEY (`nis`) REFERENCES `siswa` (`nis`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_input_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `input_aspirasi` (`id_pelaporan`, `nis`, `id_kategori`, `lokasi`, `ket`, `tanggal_pelaporan`) VALUES
(1001, 1024001, 1, 'Ruang Kelas XII RPL 1 (Gedung B Lt. 2)', 'Kipas angin plafon mati total dan 3 unit stop kontak di dinding samping tidak mengalirkan daya listrik.', '2026-10-15'),
(1002, 1024002, 3, 'Toilet Siswa Putri Lantai 1 Sayap Timur', 'Kran air pada wastafel kedua patah dan saluran pembuangan air sedikit tersumbat menyebabkan air meluap.', '2026-10-18'),
(1003, 1024003, 2, 'Laboratorium Komputer 2 (Lab RPL)', 'Kabel LAN pada meja nomor 12 sampai 15 putus pada konektor RJ-45 sehingga komputer tidak dapat tersambung ke jaringan.', '2026-10-22'),
(1004, 1024004, 6, 'Perpustakaan Utama Lantai 2', 'Unit AC di sudut ruang baca mengeluarkan tetesan air cukup deras dan terdengar suara gemuruh kecil.', '2026-10-27'),
(1005, 1024001, 5, 'Lapangan Basket Outdoor Depan Aula', 'Jaring ring basket sebelah utara robek dan tiang pelindung busa terlepas.', '2026-10-29');


-- ----------------------------------------------------------------------------
-- 5. TABEL ASPIRASI
-- Menyimpan status penyelesaian, umpan balik (feedback), dan relasi pelaporan
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `aspirasi`;
CREATE TABLE `aspirasi` (
  `id_aspirasi` INT(11) NOT NULL AUTO_INCREMENT,
  `id_pelaporan` INT(11) NOT NULL,
  `status` ENUM('Menunggu', 'Proses', 'Selesai') NOT NULL DEFAULT 'Menunggu',
  `id_kategori` INT(11) NOT NULL,
  `feedback` TEXT DEFAULT NULL,
  `tanggal_feedback` DATE DEFAULT NULL,
  PRIMARY KEY (`id_aspirasi`),
  KEY `fk_aspirasi_pelaporan` (`id_pelaporan`),
  KEY `fk_aspirasi_kategori` (`id_kategori`),
  CONSTRAINT `fk_aspirasi_pelaporan` FOREIGN KEY (`id_pelaporan`) REFERENCES `input_aspirasi` (`id_pelaporan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_aspirasi_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `aspirasi` (`id_aspirasi`, `id_pelaporan`, `status`, `id_kategori`, `feedback`, `tanggal_feedback`) VALUES
(5001, 1001, 'Selesai', 1, 'Tim teknisi telah mengganti unit kapasitor kipas angin dan mengencangkan sambungan terminal stop kontak. Sarana sudah normal kembali.', '2026-10-17'),
(5002, 1002, 'Proses', 3, 'Kran wastafel pengganti sedang dipesan oleh bagian logistik. Petugas kebersihan telah membersihkan sumbatan sementara.', '2026-10-19'),
(5003, 1003, 'Proses', 2, 'Tool crimping dan konektor baru sedang dipersiapkan oleh laboran jaringan untuk pemasangan ulang.', '2026-10-23'),
(5004, 1004, 'Menunggu', 6, 'Laporan telah diterima dan masuk dalam jadwal servis pendingin ruangan tim teknisi minggu ini.', '2026-10-28'),
(5005, 1005, 'Menunggu', 5, 'Belum ada tanggapan teknisi (Menunggu verifikasi lapangan).', NULL);
