/**
 * APLIKASI PENGADUAN SARANA SEKOLAH - JAVASCRIPT UTAMA
 * Standar Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak
 * 
 * Modul fungsionalitas:
 * - State Management (Role Siswa & Admin)
 * - Prosedur Form Submission Siswa & Validasi
 * - Tampilan Riwayat & Visual Progress Stepper
 * - Prosedur Multi-Filter Admin (Tanggal, Bulan, Siswa, Kategori, Keyword)
 * - Prosedur Umpan Balik & Perubahan Status oleh Admin
 * - Simulasi Login & Role Switcher
 * - Toast Notification System & Print Report
 */

// Global Application State
const appState = {
  currentRole: 'siswa', // 'siswa' atau 'admin'
  currentSiswaNis: 1024001, // default Siswa aktif
  currentAdminUsername: 'admin',
  activeTab: 'siswa-form', // Tab yang sedang aktif
  filters: {
    date: '',
    month: '',
    siswaNis: '',
    kategoriId: '',
    keyword: ''
  },
  selectedPelaporanId: null // Digunakan saat Admin membuka modal tanggapan
};

// ==========================================
// INISIALISASI & SETUP
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
  initDropdowns();
  updateAuthUI();
  renderCurrentView();
  attachEventListeners();
});

/**
 * Mengisi opsi-opsi dropdown kategori & siswa dari mock database
 */
function initDropdowns() {
  const kategoriList = db.getKategoriList();
  const siswaList = db.getSiswaList();

  // Populate Dropdown Kategori di Form Siswa
  const formKategoriSelect = document.getElementById('inputKategori');
  if (formKategoriSelect) {
    formKategoriSelect.innerHTML = '<option value="">-- Pilih Kategori Sarana --</option>' +
      kategoriList.map(k => `<option value="${k.id_kategori}">${k.ket_kategori}</option>`).join('');
  }

  // Populate Filter Kategori di Halaman Admin
  const filterKategori = document.getElementById('filterKategori');
  if (filterKategori) {
    filterKategori.innerHTML = '<option value="">Semua Kategori</option>' +
      kategoriList.map(k => `<option value="${k.id_kategori}">${k.ket_kategori}</option>`).join('');
  }

  // Populate Filter Siswa di Halaman Admin
  const filterSiswa = document.getElementById('filterSiswa');
  if (filterSiswa) {
    filterSiswa.innerHTML = '<option value="">Semua Siswa</option>' +
      siswaList.map(s => `<option value="${s.nis}">${s.nis} - ${s.nama} (${s.kelas})</option>`).join('');
  }

  // Populate Switcher Siswa di Modal Login/Switch
  const switchSiswaSelect = document.getElementById('switchSiswaSelect');
  if (switchSiswaSelect) {
    switchSiswaSelect.innerHTML = siswaList.map(s => 
      `<option value="${s.nis}">${s.nis} - ${s.nama} (${s.kelas})</option>`
    ).join('');
  }
}

/**
 * Update UI Navigasi dan Role Info Header
 */
function updateAuthUI() {
  const roleDisplay = document.getElementById('userRoleDisplay');
  const roleBadge = document.getElementById('userRoleBadge');
  const tabsContainer = document.getElementById('navTabsContainer');

  if (appState.currentRole === 'siswa') {
    const s = db.findSiswaByNis(appState.currentSiswaNis);
    roleDisplay.textContent = s ? `${s.nama} (${s.kelas})` : 'Siswa';
    roleBadge.textContent = 'SISWA';
    roleBadge.className = 'role-badge siswa';

    // Tabs untuk Siswa
    tabsContainer.innerHTML = `
      <li><button class="tab-btn ${appState.activeTab === 'siswa-form' ? 'active' : ''}" data-target="siswa-form-view">📝 Formulir Aspirasi</button></li>
      <li><button class="tab-btn ${appState.activeTab === 'siswa-riwayat' ? 'active' : ''}" data-target="siswa-riwayat-view">📋 Riwayat & Status Pengaduan</button></li>
    `;

    // Pastikan tab aktif valid
    if (appState.activeTab !== 'siswa-form' && appState.activeTab !== 'siswa-riwayat') {
      appState.activeTab = 'siswa-form';
    }

    // Update field NIS readonly pada form siswa
    const nisInput = document.getElementById('inputNis');
    const kelasInput = document.getElementById('inputKelas');
    if (nisInput && s) nisInput.value = s.nis;
    if (kelasInput && s) kelasInput.value = s.kelas;

  } else {
    // Mode Admin
    roleDisplay.textContent = 'Administrator Sarpras';
    roleBadge.textContent = 'ADMIN';
    roleBadge.className = 'role-badge admin';

    // Tabs untuk Admin
    tabsContainer.innerHTML = `
      <li><button class="tab-btn ${appState.activeTab === 'admin-dashboard' ? 'active' : ''}" data-target="admin-dashboard-view">📊 Daftar Aspirasi & Filter</button></li>
      <li><button class="tab-btn ${appState.activeTab === 'admin-histori' ? 'active' : ''}" data-target="admin-histori-view">📜 Histori & Cetak Laporan</button></li>
    `;

    // Pastikan tab aktif valid
    if (appState.activeTab !== 'admin-dashboard' && appState.activeTab !== 'admin-histori') {
      appState.activeTab = 'admin-dashboard';
    }
  }

  // Re-attach tab click listener
  tabsContainer.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      tabsContainer.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      e.currentTarget.classList.add('active');
      const targetView = e.currentTarget.getAttribute('data-target');
      switchView(targetView);
    });
  });
}

/**
 * Mengganti tampilan tab/halaman aktif
 */
function switchView(viewId) {
  document.querySelectorAll('.view-section').forEach(sec => sec.classList.remove('active'));
  const target = document.getElementById(viewId);
  if (target) {
    target.classList.add('active');
  }

  if (viewId === 'siswa-form-view') appState.activeTab = 'siswa-form';
  if (viewId === 'siswa-riwayat-view') {
    appState.activeTab = 'siswa-riwayat';
    renderSiswaRiwayat();
  }
  if (viewId === 'admin-dashboard-view') {
    appState.activeTab = 'admin-dashboard';
    renderAdminDashboard();
  }
  if (viewId === 'admin-histori-view') {
    appState.activeTab = 'admin-histori';
    renderAdminHistori();
  }
}

function renderCurrentView() {
  if (appState.currentRole === 'siswa') {
    if (appState.activeTab === 'siswa-riwayat') {
      switchView('siswa-riwayat-view');
    } else {
      switchView('siswa-form-view');
    }
  } else {
    if (appState.activeTab === 'admin-histori') {
      switchView('admin-histori-view');
    } else {
      switchView('admin-dashboard-view');
    }
  }
}

// ==========================================
// EVENT LISTENERS & HANDLERS
// ==========================================
function attachEventListeners() {
  // 1. Form Aspirasi Siswa Submit
  const aspirasiForm = document.getElementById('formAspirasiSiswa');
  if (aspirasiForm) {
    aspirasiForm.addEventListener('submit', handleSiswaFormSubmit);
  }

  // 2. Switch Role Button
  const btnSwitchRole = document.getElementById('btnSwitchRole');
  if (btnSwitchRole) {
    btnSwitchRole.addEventListener('click', () => {
      openRoleModal();
    });
  }

  // 3. Admin Filters
  const filterDate = document.getElementById('filterDate');
  const filterMonth = document.getElementById('filterMonth');
  const filterSiswa = document.getElementById('filterSiswa');
  const filterKategori = document.getElementById('filterKategori');
  const searchKeyword = document.getElementById('searchKeyword');
  const btnResetFilter = document.getElementById('btnResetFilter');

  if (filterDate) filterDate.addEventListener('change', applyAdminFilters);
  if (filterMonth) filterMonth.addEventListener('change', applyAdminFilters);
  if (filterSiswa) filterSiswa.addEventListener('change', applyAdminFilters);
  if (filterKategori) filterKategori.addEventListener('change', applyAdminFilters);
  if (searchKeyword) searchKeyword.addEventListener('input', debounce(applyAdminFilters, 250));
  if (btnResetFilter) btnResetFilter.addEventListener('click', resetAdminFilters);

  // 4. Modal Tanggapan Admin Submit
  const formFeedbackModal = document.getElementById('formFeedbackModal');
  if (formFeedbackModal) {
    formFeedbackModal.addEventListener('submit', handleFeedbackModalSubmit);
  }

  // 5. Reset All Demo Data Button
  const btnResetDemo = document.getElementById('btnResetDemo');
  if (btnResetDemo) {
    btnResetDemo.addEventListener('click', () => {
      if (confirm('Apakah Anda yakin ingin mengembalikan data ke kondisi awal (Reset Seed Data)? Semua pengaduan baru akan terhapus.')) {
        db.resetData();
        showToast('Data simulasi berhasil dikembalikan ke default!', 'success');
        initDropdowns();
        updateAuthUI();
        renderCurrentView();
      }
    });
  }

  // 6. Cetak Laporan Button
  const btnPrintReport = document.getElementById('btnPrintReport');
  if (btnPrintReport) {
    btnPrintReport.addEventListener('click', () => {
      window.print();
    });
  }
}

// ==========================================
// FITUR HALAMAN SISWA
// ==========================================

/**
 * Prosedur Submit Pengaduan Siswa
 */
function handleSiswaFormSubmit(e) {
  e.preventDefault();

  const nis = appState.currentSiswaNis;
  const idKategori = document.getElementById('inputKategori').value;
  const lokasi = document.getElementById('inputLokasi').value;
  const ket = document.getElementById('inputKeterangan').value;

  // Validasi Input
  if (!idKategori) {
    showToast('Silakan pilih kategori sarana!', 'warning');
    return;
  }
  if (!lokasi.trim()) {
    showToast('Lokasi sarana wajib diisi!', 'warning');
    return;
  }
  if (!ket.trim() || ket.trim().length < 8) {
    showToast('Keterangan kerusakan harus diisi minimal 8 karakter!', 'warning');
    return;
  }

  // Simpan ke state mock database
  const result = db.createAspirasi(nis, idKategori, lokasi, ket);

  showToast(`Pengaduan berhasil dikirim dengan ID Pelaporan #${result.pelaporan.id_pelaporan}!`, 'success');

  // Reset form inputs
  document.getElementById('inputKategori').value = '';
  document.getElementById('inputLokasi').value = '';
  document.getElementById('inputKeterangan').value = '';

  // Alihkan otomatis ke tab riwayat siswa
  setTimeout(() => {
    switchView('siswa-riwayat-view');
    // Update active tab styling
    document.querySelectorAll('.app-tabs .tab-btn').forEach(btn => {
      if (btn.getAttribute('data-target') === 'siswa-riwayat-view') {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
  }, 400);
}

/**
 * Render Riwayat Aspirasi Milik Siswa yang Login
 */
function renderSiswaRiwayat() {
  const container = document.getElementById('siswaRiwayatList');
  const countBadge = document.getElementById('siswaRiwayatCount');
  if (!container) return;

  const list = db.getAspirasiByNis(appState.currentSiswaNis);
  if (countBadge) countBadge.textContent = `${list.length} Pengaduan`;

  if (list.length === 0) {
    container.innerHTML = `
      <div class="empty-state card">
        <div class="empty-state-icon">📭</div>
        <h3>Belum Ada Pengaduan</h3>
        <p>Anda belum pernah mengirimkan pengaduan sarana sekolah. Gunakan menu Formulir Aspirasi untuk menyampaikan masukan.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = list.map(item => {
    const statusBadge = getStatusBadgeHTML(item.status);
    const stepperHTML = getStepperHTML(item.status);

    return `
      <div class="aspirasi-card">
        <div class="aspirasi-header">
          <div>
            <span class="aspirasi-category">${escapeHTML(item.ket_kategori)}</span>
            <div class="aspirasi-location" style="margin-top: 0.4rem;">📍 ${escapeHTML(item.lokasi)}</div>
          </div>
          <div>
            ${statusBadge}
          </div>
        </div>

        <div class="aspirasi-meta">
          <span><strong>ID:</strong> #${item.id_pelaporan}</span>
          <span>•</span>
          <span>📅 Dilaporkan: ${formatTanggal(item.tanggal_pelaporan)}</span>
        </div>

        <div class="aspirasi-desc" style="margin-top: 0.6rem;">
          ${escapeHTML(item.ket)}
        </div>

        <!-- Visual Progress Stepper -->
        ${stepperHTML}

        <!-- Kotak Umpan Balik Admin -->
        <div class="feedback-box">
          <div class="feedback-box-header">
            <span>💬 Tanggapan / Feedback Tim Sarpras:</span>
            ${item.tanggal_feedback ? `<span style="font-weight: normal; font-size: 0.75rem; color: #64748b; margin-left: auto;">(${formatTanggal(item.tanggal_feedback)})</span>` : ''}
          </div>
          <div class="feedback-text">
            "${item.feedback ? escapeHTML(item.feedback) : 'Belum ada catatan umpan balik dari tim sarpras.'}"
          </div>
        </div>
      </div>
    `;
  }).join('');
}

/**
 * Helper Membuat Visual Stepper Progres
 */
function getStepperHTML(status) {
  let step1Class = 'step-item';
  let step2Class = 'step-item';
  let step3Class = 'step-item';
  let progressWidth = '0%';

  if (status === 'Menunggu') {
    step1Class += ' active';
    progressWidth = '15%';
  } else if (status === 'Proses') {
    step1Class += ' completed';
    step2Class += ' active';
    progressWidth = '50%';
  } else if (status === 'Selesai') {
    step1Class += ' completed';
    step2Class += ' completed';
    step3Class += ' completed';
    progressWidth = '100%';
  }

  return `
    <div class="stepper-container">
      <div class="stepper-progress" style="width: ${progressWidth};"></div>
      
      <div class="${step1Class}">
        <div class="step-circle">${status === 'Menunggu' ? '1' : '✓'}</div>
        <div class="step-label">Menunggu Verifikasi</div>
      </div>

      <div class="${step2Class}">
        <div class="step-circle">${status === 'Selesai' ? '✓' : '2'}</div>
        <div class="step-label">Sedang Diproses</div>
      </div>

      <div class="${step3Class}">
        <div class="step-circle">${status === 'Selesai' ? '✓' : '3'}</div>
        <div class="step-label">Perbaikan Selesai</div>
      </div>
    </div>
  `;
}

// ==========================================
// FITUR HALAMAN ADMIN
// ==========================================

/**
 * Render Statistik & Tabel Pengaduan Admin
 */
function renderAdminDashboard() {
  const allData = db.getAllAspirasiJoined();

  // Hitung Metrik Statistik
  const total = allData.length;
  const menungguCount = allData.filter(d => d.status === 'Menunggu').length;
  const prosesCount = allData.filter(d => d.status === 'Proses').length;
  const selesaiCount = allData.filter(d => d.status === 'Selesai').length;
  const percentage = total > 0 ? Math.round((selesaiCount / total) * 100) : 0;

  // Update Kartu Metrik
  document.getElementById('statTotal').textContent = total;
  document.getElementById('statMenunggu').textContent = menungguCount;
  document.getElementById('statProses').textContent = prosesCount;
  document.getElementById('statSelesai').textContent = selesaiCount;
  document.getElementById('statPersen').textContent = `${percentage}%`;

  // Render Tabel dengan Filter Aktif
  renderAdminTable();
}

/**
 * Filter Data Aspirasi Berdasarkan Parameter
 */
function getFilteredAspirasi() {
  let list = db.getAllAspirasiJoined();
  const { date, month, siswaNis, kategoriId, keyword } = appState.filters;

  if (date) {
    list = list.filter(item => item.tanggal_pelaporan === date);
  }

  if (month) {
    // month format: "YYYY-MM" atau "MM"
    list = list.filter(item => {
      if (!item.tanggal_pelaporan) return false;
      return item.tanggal_pelaporan.startsWith(month);
    });
  }

  if (siswaNis) {
    list = list.filter(item => Number(item.nis) === Number(siswaNis));
  }

  if (kategoriId) {
    list = list.filter(item => Number(item.id_kategori) === Number(kategoriId));
  }

  if (keyword.trim()) {
    const q = keyword.toLowerCase().trim();
    list = list.filter(item => 
      item.lokasi.toLowerCase().includes(q) ||
      item.ket.toLowerCase().includes(q) ||
      item.nama_siswa.toLowerCase().includes(q) ||
      item.ket_kategori.toLowerCase().includes(q) ||
      String(item.nis).includes(q) ||
      String(item.id_pelaporan).includes(q)
    );
  }

  return list;
}

/**
 * Render Tabel Aspirasi Admin
 */
function renderAdminTable() {
  const tableBody = document.getElementById('adminTableBody');
  const countLabel = document.getElementById('filterResultCount');
  if (!tableBody) return;

  const filtered = getFilteredAspirasi();
  const totalAll = db.getAllAspirasiJoined().length;

  if (countLabel) {
    countLabel.textContent = `Menampilkan ${filtered.length} dari ${totalAll} aspirasi`;
  }

  if (filtered.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="8" class="empty-state" style="padding: 2.5rem;">
          <div class="empty-state-icon">🔍</div>
          <p>Tidak ada data pengaduan yang sesuai dengan filter atau kata kunci pencarian.</p>
        </td>
      </tr>
    `;
    return;
  }

  tableBody.innerHTML = filtered.map((item, idx) => {
    return `
      <tr>
        <td style="font-weight: 600; text-align: center;">${idx + 1}</td>
        <td>
          <span style="font-weight: 700; color: var(--primary);">#${item.id_pelaporan}</span>
          <div style="font-size: 0.75rem; color: var(--text-muted);">${formatTanggal(item.tanggal_pelaporan)}</div>
        </td>
        <td>
          <div style="font-weight: 600;">${escapeHTML(item.nama_siswa)}</div>
          <div style="font-size: 0.78rem; color: var(--text-muted);">NIS: ${item.nis} (${escapeHTML(item.kelas)})</div>
        </td>
        <td>
          <span class="aspirasi-category">${escapeHTML(item.ket_kategori)}</span>
        </td>
        <td>
          <div style="font-weight: 600; color: var(--text-main);">${escapeHTML(item.lokasi)}</div>
          <div style="font-size: 0.8rem; color: #475569; max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHTML(item.ket)}">
            ${escapeHTML(item.ket)}
          </div>
        </td>
        <td>${getStatusBadgeHTML(item.status)}</td>
        <td style="max-width: 220px;">
          <div style="font-size: 0.8rem; color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHTML(item.feedback)}">
            ${item.feedback ? escapeHTML(item.feedback) : '<span style="color: #94a3b8;">Belum ada tanggapan</span>'}
          </div>
          ${item.tanggal_feedback ? `<div style="font-size: 0.72rem; color: #94a3b8;">Update: ${formatTanggal(item.tanggal_feedback)}</div>` : ''}
        </td>
        <td style="text-align: center;">
          <button class="btn btn-primary btn-sm" onclick="openFeedbackModal(${item.id_pelaporan})" title="Ubah status & feedback">
            ✏️ Tanggapi
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

/**
 * Render Tab Histori & Laporan Admin
 */
function renderAdminHistori() {
  const container = document.getElementById('historiTableBody');
  if (!container) return;

  const all = db.getAllAspirasiJoined();

  container.innerHTML = all.map((item, idx) => {
    return `
      <tr>
        <td style="text-align: center;">${idx + 1}</td>
        <td>#${item.id_pelaporan}</td>
        <td>${formatTanggal(item.tanggal_pelaporan)}</td>
        <td>${item.nis} - ${escapeHTML(item.nama_siswa)} (${escapeHTML(item.kelas)})</td>
        <td>${escapeHTML(item.ket_kategori)}</td>
        <td>${escapeHTML(item.lokasi)}</td>
        <td>${escapeHTML(item.ket)}</td>
        <td>${getStatusBadgeHTML(item.status)}</td>
        <td>${escapeHTML(item.feedback || '-')}</td>
      </tr>
    `;
  }).join('');
}

/**
 * Filter Event Handlers
 */
function applyAdminFilters() {
  appState.filters.date = document.getElementById('filterDate').value;
  appState.filters.month = document.getElementById('filterMonth').value;
  appState.filters.siswaNis = document.getElementById('filterSiswa').value;
  appState.filters.kategoriId = document.getElementById('filterKategori').value;
  appState.filters.keyword = document.getElementById('searchKeyword').value;

  renderAdminTable();
}

function resetAdminFilters() {
  document.getElementById('filterDate').value = '';
  document.getElementById('filterMonth').value = '';
  document.getElementById('filterSiswa').value = '';
  document.getElementById('filterKategori').value = '';
  document.getElementById('searchKeyword').value = '';

  appState.filters = {
    date: '',
    month: '',
    siswaNis: '',
    kategoriId: '',
    keyword: ''
  };

  renderAdminTable();
  showToast('Filter telah direset ke default.', 'info');
}

// ==========================================
// MODAL TANGGAPAN & STATUS ADMIN
// ==========================================

/**
 * Membuka Modal Tindak Lanjut Aspirasi untuk Admin
 */
window.openFeedbackModal = function(idPelaporan) {
  const item = db.getAllAspirasiJoined().find(a => Number(a.id_pelaporan) === Number(idPelaporan));
  if (!item) return;

  appState.selectedPelaporanId = idPelaporan;

  document.getElementById('modalPelaporanId').textContent = `#${item.id_pelaporan}`;
  document.getElementById('modalSiswaDetail').textContent = `${item.nama_siswa} (NIS: ${item.nis} - ${item.kelas})`;
  document.getElementById('modalKategoriLokasi').textContent = `${item.ket_kategori} • Lokasi: ${item.lokasi}`;
  document.getElementById('modalKeluhan').textContent = item.ket;
  document.getElementById('modalTanggalLapor').textContent = formatTanggal(item.tanggal_pelaporan);

  document.getElementById('selectStatusAspirasi').value = item.status;
  document.getElementById('inputFeedbackAdmin').value = item.feedback || '';

  const modal = document.getElementById('feedbackModalOverlay');
  if (modal) modal.classList.add('open');
};

function closeFeedbackModal() {
  const modal = document.getElementById('feedbackModalOverlay');
  if (modal) modal.classList.remove('open');
  appState.selectedPelaporanId = null;
}
window.closeFeedbackModal = closeFeedbackModal;

/**
 * Simpan Tanggapan & Perubahan Status oleh Admin
 */
function handleFeedbackModalSubmit(e) {
  e.preventDefault();
  if (!appState.selectedPelaporanId) return;

  const newStatus = document.getElementById('selectStatusAspirasi').value;
  const feedbackText = document.getElementById('inputFeedbackAdmin').value;

  if (!feedbackText.trim()) {
    showToast('Harap berikan catatan umpan balik / feedback untuk siswa!', 'warning');
    return;
  }

  const success = db.updateAspirasiStatusAndFeedback(appState.selectedPelaporanId, newStatus, feedbackText);

  if (success) {
    showToast(`Status pengaduan #${appState.selectedPelaporanId} berhasil diubah menjadi "${newStatus}"!`, 'success');
    closeFeedbackModal();
    renderAdminDashboard();
  } else {
    showToast('Terjadi kesalahan saat memperbarui status pengaduan.', 'danger');
  }
}

// ==========================================
// MODAL LOGIN & ROLE SWITCHER
// ==========================================

function openRoleModal() {
  const modal = document.getElementById('roleModalOverlay');
  if (modal) modal.classList.add('open');
}

function closeRoleModal() {
  const modal = document.getElementById('roleModalOverlay');
  if (modal) modal.classList.remove('open');
}
window.closeRoleModal = closeRoleModal;

/**
 * Handle Penggantian Peran Siswa/Admin
 */
window.handleApplyRoleSwitch = function() {
  const selectedRole = document.querySelector('input[name="roleChoice"]:checked').value;

  if (selectedRole === 'siswa') {
    const nis = document.getElementById('switchSiswaSelect').value;
    appState.currentRole = 'siswa';
    appState.currentSiswaNis = Number(nis);
    appState.activeTab = 'siswa-form';
    showToast(`Beralih ke mode Siswa (NIS: ${nis})`, 'info');
  } else {
    appState.currentRole = 'admin';
    appState.activeTab = 'admin-dashboard';
    showToast('Beralih ke mode Administrator Sarpras', 'info');
  }

  closeRoleModal();
  updateAuthUI();
  renderCurrentView();
};

// ==========================================
// UTILITY HELPERS
// ==========================================

function getStatusBadgeHTML(status) {
  let badgeClass = 'badge-menunggu';
  if (status === 'Proses') badgeClass = 'badge-proses';
  if (status === 'Selesai') badgeClass = 'badge-selesai';

  return `
    <span class="badge-status ${badgeClass}">
      <span class="badge-dot"></span>
      ${status}
    </span>
  `;
}

function formatTanggal(isoDate) {
  if (!isoDate) return '-';
  const parts = isoDate.split('-');
  if (parts.length !== 3) return isoDate;
  const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  return `${parts[2]} ${bulan[parseInt(parts[1], 10) - 1]} ${parts[0]}`;
}

function escapeHTML(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function debounce(func, delay) {
  let timeout;
  return function(...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), delay);
  };
}

/**
 * Toast Notification Helper
 */
function showToast(message, type = 'info') {
  const container = document.getElementById('toastContainer');
  if (!container) return;

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  
  let icon = 'ℹ️';
  if (type === 'success') icon = '✅';
  if (type === 'danger') icon = '❌';
  if (type === 'warning') icon = '⚠️';

  toast.innerHTML = `
    <span style="font-size: 1.1rem;">${icon}</span>
    <span class="toast-message">${escapeHTML(message)}</span>
  `;

  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(100%)';
    setTimeout(() => toast.remove(), 300);
  }, 3500);
}
window.showToast = showToast;
