/**
 * DATA & MOCK DATABASE SIMULATION
 * Aplikasi Pengaduan Sarana Sekolah (UKK Rekayasa Perangkat Lunak)
 * 
 * Sesuai Rancangan Entity Relationship Diagram (ERD):
 * 1. Tabel Admin         : Username, password
 * 2. Tabel Siswa         : nis (integer), kelas (varchar)
 * 3. Tabel Kategori      : id_kategori (integer), ket_kategori (varchar)
 * 4. Tabel Input Aspirasi: id_pelaporan (integer), nis (integer), id_kategori (integer), lokasi (varchar), ket (varchar)
 * 5. Tabel Aspirasi      : id_aspirasi (integer), id_pelaporan (integer), status (Menunggu, Proses, Selesai), id_kategori (integer), feedback (varchar/text)
 */

const STORAGE_KEY = 'UKK_PENGADUAN_SARANA_V1';

// Initial Seed Data (Data Standar Uji Kompetensi Keahlian)
const INITIAL_DATA = {
  // Tabel Admin
  admin: [
    { username: 'admin', password: 'password123', nama: 'Administrator Sarpras' },
    { username: 'petugas_sarpras', password: 'password123', nama: 'Staff Sarana & Prasarana' }
  ],

  // Tabel Siswa
  siswa: [
    { nis: 1024001, nama: 'Ahmad Fauzi', kelas: 'XII RPL 1' },
    { nis: 1024002, nama: 'Siti Nurhaliza', kelas: 'XII RPL 2' },
    { nis: 1024003, nama: 'Budi Santoso', kelas: 'XI RPL 1' },
    { nis: 1024004, nama: 'Dewi Lestari', kelas: 'XI RPL 2' },
    { nis: 1024005, nama: 'Rian Pratama', kelas: 'X PPLG 1' }
  ],

  // Tabel Kategori
  kategori: [
    { id_kategori: 1, ket_kategori: 'Ruang Kelas & Mebelair' },
    { id_kategori: 2, ket_kategori: 'Laboratorium Komputer & Jaringan' },
    { id_kategori: 3, ket_kategori: 'Toilet & Sanitasi' },
    { id_kategori: 4, ket_kategori: 'Perpustakaan' },
    { id_kategori: 5, ket_kategori: 'Lapangan Olahraga' },
    { id_kategori: 6, ket_kategori: 'Listrik & Pendingin Ruangan (AC)' },
    { id_kategori: 7, ket_kategori: 'Kantin & Area Terbuka' }
  ],

  // Tabel Input Aspirasi (id_pelaporan, nis, id_kategori, lokasi, ket, tanggal_pelaporan)
  input_aspirasi: [
    {
      id_pelaporan: 1001,
      nis: 1024001,
      id_kategori: 1,
      lokasi: 'Ruang Kelas XII RPL 1 (Gedung B Lt. 2)',
      ket: 'Kipas angin plafon mati total dan 3 unit stop kontak di dinding samping tidak mengalirkan daya listrik.',
      tanggal_pelaporan: '2026-10-15'
    },
    {
      id_pelaporan: 1002,
      nis: 1024002,
      id_kategori: 3,
      lokasi: 'Toilet Siswa Putri Lantai 1 Sayap Timur',
      ket: 'Kran air pada wastafel kedua patah dan saluran pembuangan air sedikit tersumbat menyebabkan air meluap.',
      tanggal_pelaporan: '2026-10-18'
    },
    {
      id_pelaporan: 1003,
      nis: 1024003,
      id_kategori: 2,
      lokasi: 'Laboratorium Komputer 2 (Lab RPL)',
      ket: 'Kabel LAN pada meja nomor 12 sampai 15 putus pada konektor RJ-45 sehingga komputer tidak dapat tersambung ke jaringan.',
      tanggal_pelaporan: '2026-10-22'
    },
    {
      id_pelaporan: 1004,
      nis: 1024004,
      id_kategori: 6,
      lokasi: 'Perpustakaan Utama Lantai 2',
      ket: 'Unit AC di sudut ruang baca mengeluarkan tetesan air cukup deras dan terdengar suara gemuruh kecil.',
      tanggal_pelaporan: '2026-10-27'
    },
    {
      id_pelaporan: 1005,
      nis: 1024001,
      id_kategori: 5,
      lokasi: 'Lapangan Basket Outdoor Depan Aula',
      ket: 'Jaring ring basket sebelah utara robek dan tiang pelindung busa terlepas.',
      tanggal_pelaporan: '2026-10-29'
    }
  ],

  // Tabel Aspirasi (id_aspirasi, id_pelaporan, status, id_kategori, feedback, tanggal_feedback)
  // Status: "Menunggu" | "Proses" | "Selesai"
  aspirasi: [
    {
      id_aspirasi: 5001,
      id_pelaporan: 1001,
      status: 'Selesai',
      id_kategori: 1,
      feedback: 'Tim teknisi telah mengganti unit kapasitor kipas angin dan mengencangkan sambungan terminal stop kontak. Sarana sudah normal kembali.',
      tanggal_feedback: '2026-10-17'
    },
    {
      id_aspirasi: 5002,
      id_pelaporan: 1002,
      status: 'Proses',
      id_kategori: 3,
      feedback: 'Kran wastafel pengganti sedang dipesan oleh bagian logistik. Petugas kebersihan telah membersihkan sumbatan sementara.',
      tanggal_feedback: '2026-10-19'
    },
    {
      id_aspirasi: 5003,
      id_pelaporan: 1003,
      status: 'Proses',
      id_kategori: 2,
      feedback: 'Tool crimping dan konektor baru sedang dipersiapkan oleh laboran jaringan untuk pemasangan ulang.',
      tanggal_feedback: '2026-10-23'
    },
    {
      id_aspirasi: 5004,
      id_pelaporan: 1004,
      status: 'Menunggu',
      id_kategori: 6,
      feedback: 'Laporan telah diterima dan masuk dalam jadwal servis pendingin ruangan tim teknisi minggu ini.',
      tanggal_feedback: '2026-10-28'
    },
    {
      id_aspirasi: 5005,
      id_pelaporan: 1005,
      status: 'Menunggu',
      id_kategori: 5,
      feedback: 'Belum ada tanggapan teknisi (Menunggu verifikasi lapangan).',
      tanggal_feedback: null
    }
  ]
};

/**
 * State Management Database Helper
 * Memastikan data tersimpan di LocalStorage atau menggunakan initial seed data
 */
class MockDatabase {
  constructor() {
    this.data = this.loadData();
  }

  loadData() {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) {
      try {
        return JSON.parse(raw);
      } catch (e) {
        console.error('Gagal membaca storage, menggunakan initial seed data:', e);
      }
    }
    this.saveData(INITIAL_DATA);
    return JSON.parse(JSON.stringify(INITIAL_DATA));
  }

  saveData(newData) {
    this.data = newData || this.data;
    localStorage.setItem(STORAGE_KEY, JSON.stringify(this.data));
  }

  resetData() {
    localStorage.removeItem(STORAGE_KEY);
    this.data = JSON.parse(JSON.stringify(INITIAL_DATA));
    this.saveData(this.data);
    return this.data;
  }

  // --- QUERY & DATA MANIPULATION FUNCTIONS ---

  getAdminList() {
    return this.data.admin;
  }

  getSiswaList() {
    return this.data.siswa;
  }

  findSiswaByNis(nis) {
    return this.data.siswa.find(s => Number(s.nis) === Number(nis));
  }

  getKategoriList() {
    return this.data.kategori;
  }

  findKategoriById(id) {
    return this.data.kategori.find(k => Number(k.id_kategori) === Number(id));
  }

  /**
   * Mengambil data pengaduan gabungan (JOIN Input Aspirasi + Aspirasi + Siswa + Kategori)
   * Merefleksikan query SQL JOIN pada standar UKK RPL
   */
  getAllAspirasiJoined() {
    return this.data.input_aspirasi.map(pelaporan => {
      const aspirasiStatus = this.data.aspirasi.find(a => a.id_pelaporan === pelaporan.id_pelaporan) || {
        id_aspirasi: null,
        status: 'Menunggu',
        feedback: 'Belum ada tanggapan.',
        tanggal_feedback: null
      };

      const siswaInfo = this.findSiswaByNis(pelaporan.nis) || {
        nis: pelaporan.nis,
        nama: 'Siswa Tidak Dikenal',
        kelas: '-'
      };

      const kategoriInfo = this.findKategoriById(pelaporan.id_kategori) || {
        id_kategori: pelaporan.id_kategori,
        ket_kategori: 'Lainnya'
      };

      return {
        id_pelaporan: pelaporan.id_pelaporan,
        id_aspirasi: aspirasiStatus.id_aspirasi,
        nis: pelaporan.nis,
        nama_siswa: siswaInfo.nama,
        kelas: siswaInfo.kelas,
        id_kategori: pelaporan.id_kategori,
        ket_kategori: kategoriInfo.ket_kategori,
        lokasi: pelaporan.lokasi,
        ket: pelaporan.ket,
        tanggal_pelaporan: pelaporan.tanggal_pelaporan,
        status: aspirasiStatus.status,
        feedback: aspirasiStatus.feedback,
        tanggal_feedback: aspirasiStatus.tanggal_feedback
      };
    }).sort((a, b) => b.id_pelaporan - a.id_pelaporan); // Urutkan dari pengaduan terbaru
  }

  /**
   * Mengambil aspirasi khusus untuk satu siswa (berdasarkan NIS)
   */
  getAspirasiByNis(nis) {
    return this.getAllAspirasiJoined().filter(item => Number(item.nis) === Number(nis));
  }

  /**
   * Prosedur Menambah Input Aspirasi Baru oleh Siswa
   */
  createAspirasi(nis, id_kategori, lokasi, ket) {
    const nextPelaporanId = this.data.input_aspirasi.length > 0 
      ? Math.max(...this.data.input_aspirasi.map(p => p.id_pelaporan)) + 1 
      : 1001;

    const nextAspirasiId = this.data.aspirasi.length > 0
      ? Math.max(...this.data.aspirasi.map(a => a.id_aspirasi)) + 1
      : 5001;

    const today = new Date().toISOString().split('T')[0];

    const newPelaporan = {
      id_pelaporan: nextPelaporanId,
      nis: Number(nis),
      id_kategori: Number(id_kategori),
      lokasi: lokasi.trim(),
      ket: ket.trim(),
      tanggal_pelaporan: today
    };

    const newAspirasi = {
      id_aspirasi: nextAspirasiId,
      id_pelaporan: nextPelaporanId,
      status: 'Menunggu',
      id_kategori: Number(id_kategori),
      feedback: 'Laporan baru diterima. Menunggu verifikasi tim sarpras.',
      tanggal_feedback: today
    };

    this.data.input_aspirasi.push(newPelaporan);
    this.data.aspirasi.push(newAspirasi);
    this.saveData();

    return {
      pelaporan: newPelaporan,
      aspirasi: newAspirasi
    };
  }

  /**
   * Prosedur Update Status & Feedback oleh Admin
   */
  updateAspirasiStatusAndFeedback(id_pelaporan, status, feedback) {
    const aspIndex = this.data.aspirasi.findIndex(a => Number(a.id_pelaporan) === Number(id_pelaporan));
    const today = new Date().toISOString().split('T')[0];

    if (aspIndex !== -1) {
      this.data.aspirasi[aspIndex].status = status;
      this.data.aspirasi[aspIndex].feedback = feedback.trim();
      this.data.aspirasi[aspIndex].tanggal_feedback = today;
      this.saveData();
      return true;
    }
    return false;
  }
}

// Instance global mock database
const db = new MockDatabase();
