# 🏫 Aplikasi Pengaduan Sarana Sekolah (UKK Rekayasa Perangkat Lunak)

Aplikasi web interaktif untuk pengelolaan dan pemantauan pengaduan fasilitas sarana & prasarana sekolah. Memenuhi seluruh spesifikasi soal **Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak (RPL)**.

---

## 🚀 Pilihan Cara Menjalankan

Aplikasi ini disediakan dalam **dua varian lengkap**:

### 1. Varian Web Interaktif Mandiri (Langsung Buka di Browser)
*Tidak memerlukan instalasi server atau database!*
* Cukup buka file [`index.html`](index.html) menggunakan peramban web (Google Chrome, Microsoft Edge, Firefox).
* Gunakan tombol **"Ganti Mode / Login"** di kanan atas untuk berpindah antara tampilan **Siswa** dan **Admin**.
* Dilengkapi penyimpanan lokal (*LocalStorage*) dan tombol **"🔄 Reset Demo"** untuk mengembalikan data awal.

### 2. Varian PHP Native & MySQL (XAMPP / Laragon)
*Lengkap dengan database relational dan server-side handling.*
1. Nyalakan service **Apache** dan **MySQL** di XAMPP/Laragon.
2. Buat database `db_pengaduan_sarana` di **phpMyAdmin**, lalu impor file [`php-mysql/database.sql`](php-mysql/database.sql).
3. Pindahkan folder `php-mysql/` ke direktori `htdocs/` (misal: `C:\xampp\htdocs\pengaduan-sarana\`).
4. Akses melalui browser: `http://localhost/pengaduan-sarana/`.

---

## 🔑 Kredensial Pengujian (Demo)

### Portal Siswa
* Masuk menggunakan **NIS** terdaftar:
  * `1024001` (Ahmad Fauzi — XII RPL 1)
  * `1024002` (Siti Nurhaliza — XII RPL 2)
  * `1024003` (Budi Santoso — XI RPL 1)
  * `1024004` (Dewi Lestari — XI RPL 2)
  * `1024005` (Rian Pratama — X PPLG 1)

### Portal Admin
* **Username**: `admin`
* **Password**: `password123`

---

## 📑 Fitur Utama Sesuai Spesifikasi UKK

* **Halaman Siswa**:
  * 📝 **Formulir Pengaduan**: Input NIS (otomatis), Kategori Sarana, Lokasi Spesifik, dan Deskripsi Kerusakan.
  * 📋 **Riwayat & Status**: Indikator status (*Menunggu*, *Proses*, *Selesai*), visual progress stepper 3-tahap, dan kotak umpan balik (*feedback*) dari admin.
* **Halaman Admin**:
  * 📊 **Dasbor Analitik**: Metrik total pengaduan masuk, menunggu, proses, selesai, dan persentase penyelesaian.
  * 🔍 **Multi-Filter**: Filter data per tanggal, per bulan, per siswa (NIS), per kategori sarana, dan live keyword search.
  * 💬 **Umpan Balik & Status**: Modal interaktif untuk mengubah status pengerjaan fasilitas dan memberi feedback tertulis.
  * 📜 **Histori & Cetak Laporan**: Log lengkap pengaduan dan template cetak resmi berkop surat sekolah untuk penguji UKK.

---

## 📚 Dokumentasi Teknis Lengkap
Dokumentasi teknis menyeluruh, kamus data (Data Dictionary), diagram ERD, flowchart proses bisnis, dan matriks pengujian UKK dapat dibaca pada file:
👉 **[`DOKUMENTASI_LENGKAP.md`](DOKUMENTASI_LENGKAP.md)**
