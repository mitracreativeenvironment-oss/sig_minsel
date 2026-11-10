<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../stores/authStore';
import { sigApi } from '../services/api';
import { notify, confirmDialog, promptNoteDialog } from '../services/alert';
import type { RoleName, DatasetItem, DataDasarVersion, WorkflowState } from '../types';
import {
  LayoutDashboard,
  Database,
  FileCheck2,
  CheckCheck,
  MapPin,
  Users,
  History,
  LogOut,
  Upload,
  CheckCircle2,
  AlertTriangle,
  FileArchive,
  Layers,
  ArrowRight,
  ShieldAlert,
  Send,
  Search,
  LayoutGrid,
  List,
  X,
  RotateCcw
} from 'lucide-vue-next';

const emit = defineEmits<{
  (e: 'navigate', view: 'geoportal' | 'dashboard'): void;
}>();

const authStore = useAuthStore();

type NavTab = 'overview' | 'datasets' | 'validation' | 'approval' | 'data_dasar' | 'users' | 'audit';
const activeTab = ref<NavTab>('overview');

// Data State
const datasets = ref<DatasetItem[]>([]);
const dataDasarList = ref<DataDasarVersion[]>([]);
const workflowStates = ref<WorkflowState[]>([]);
const isLoading = ref(false);

// View Mode State with LocalStorage Persistence
const viewMode = ref<'grid' | 'list'>(
  (localStorage.getItem('geominsel_view_mode') as 'grid' | 'list') || 'grid'
);

function setViewMode(mode: 'grid' | 'list') {
  viewMode.value = mode;
  localStorage.setItem('geominsel_view_mode', mode);
  notify.info(
    mode === 'grid' ? 'Tampilan Grid Aktif' : 'Tampilan Tabel (List) Aktif',
    `Preferensi tampilan disimpan sebagai default.`
  );
}

// Upload Form State for Superadmin SHP Boundary
const uploadTipe = ref<'KABUPATEN' | 'KECAMATAN' | 'DESA'>('KECAMATAN');
const uploadSumber = ref('Badan Informasi Geospasial / Bagian Tapem Setda');
const uploadTahun = ref(2026);
const uploadFileName = ref('batas_17_kecamatan_minsel_2026.zip');
const isUploading = ref(false);
const lastValidationReport = ref<any>(null);

// Search & Filter State for Datasets (Multi-Kriteria)
const searchQuery = ref('');
const filterStatus = ref('');
const filterKecamatan = ref('');
const filterGeometri = ref('');
const filterOpd = ref('');

// List of 17 Kecamatan in Minahasa Selatan
const daftarKecamatan = [
  'Amurang',
  'Amurang Barat',
  'Amurang Timur',
  'Tumpaan',
  'Tareran',
  'Suluun Tareran',
  'Modoinding',
  'Tompaso Baru',
  'Maesaan',
  'Ranoyapo',
  'Motoling',
  'Motoling Barat',
  'Motoling Timur',
  'Kumelembuai',
  'Sinonsayang',
  'Tenga',
  'Tatapaan'
];

// Computed unique OPDs from dataset list
const uniqueOpds = computed(() => {
  const set = new Set<string>();
  datasets.value.forEach((d) => {
    if (d.opd) set.add(d.opd);
  });
  return Array.from(set).sort();
});

// Computed unique Geometri from dataset list
const uniqueGeometri = computed(() => {
  const set = new Set<string>();
  datasets.value.forEach((d) => {
    if (d.tipe_geometri) set.add(d.tipe_geometri);
  });
  return Array.from(set).sort();
});

// Count active filters
const activeFilterCount = computed(() => {
  let count = 0;
  if (searchQuery.value.trim()) count++;
  if (filterStatus.value) count++;
  if (filterKecamatan.value) count++;
  if (filterGeometri.value) count++;
  if (filterOpd.value) count++;
  return count;
});

function resetFilters() {
  searchQuery.value = '';
  filterStatus.value = '';
  filterKecamatan.value = '';
  filterGeometri.value = '';
  filterOpd.value = '';
  notify.info('Filter Direset', 'Semua kriteria pencarian telah dikosongkan.');
}

// Multi-Criteria Filtered Datasets
const filteredDatasets = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  return datasets.value.filter((d) => {
    const matchSearch =
      !q ||
      d.nama_dataset?.toLowerCase().includes(q) ||
      d.kode_dataset?.toLowerCase().includes(q) ||
      d.opd?.toLowerCase().includes(q) ||
      d.kecamatan?.toLowerCase().includes(q) ||
      d.nama_kategori?.toLowerCase().includes(q) ||
      (d.deskripsi && d.deskripsi.toLowerCase().includes(q));

    const matchStatus = !filterStatus.value || d.status === filterStatus.value;
    const matchKec =
      !filterKecamatan.value ||
      d.kecamatan?.toLowerCase().includes(filterKecamatan.value.toLowerCase());
    const matchGeom = !filterGeometri.value || d.tipe_geometri === filterGeometri.value;
    const matchOpd = !filterOpd.value || d.opd === filterOpd.value;

    return matchSearch && matchStatus && matchKec && matchGeom && matchOpd;
  });
});

// Search & Filter for Validation Queue
const searchValidationQuery = ref('');
const filteredValidationDatasets = computed(() => {
  const q = searchValidationQuery.value.trim().toLowerCase();
  return datasets.value
    .filter((item) => ['SUBMITTED_GIS', 'IN_VALIDATION_GIS'].includes(item.status))
    .filter(
      (d) =>
        !q ||
        d.nama_dataset?.toLowerCase().includes(q) ||
        d.kode_dataset?.toLowerCase().includes(q) ||
        d.opd?.toLowerCase().includes(q) ||
        d.kecamatan?.toLowerCase().includes(q)
    );
});

// Search & Filter for Approval Queue
const searchApprovalQuery = ref('');
const filteredApprovalDatasets = computed(() => {
  const q = searchApprovalQuery.value.trim().toLowerCase();
  return datasets.value
    .filter((item) =>
      ['APPROVED_GIS', 'SUBMITTED_APPROVAL', 'IN_APPROVAL'].includes(item.status)
    )
    .filter(
      (d) =>
        !q ||
        d.nama_dataset?.toLowerCase().includes(q) ||
        d.kode_dataset?.toLowerCase().includes(q) ||
        d.opd?.toLowerCase().includes(q) ||
        d.kecamatan?.toLowerCase().includes(q)
    );
});

// Search & Filter for Users & OPD
const searchUserQuery = ref('');
const filterUserRole = ref('');
const filteredUsers = computed(() => {
  const q = searchUserQuery.value.trim().toLowerCase();
  return authStore.roles.filter((r) => {
    const matchSearch =
      !q ||
      r.label.toLowerCase().includes(q) ||
      r.name.toLowerCase().includes(q) ||
      r.opd.toLowerCase().includes(q);
    const matchRole = !filterUserRole.value || r.name === filterUserRole.value;
    return matchSearch && matchRole;
  });
});

async function loadDashboardData() {
  isLoading.value = true;
  try {
    const [wfRes, dsRes, ddRes] = await Promise.allSettled([
      sigApi.getWorkflowStates(),
      sigApi.getWorkflowDatasets(),
      sigApi.getDataDasar()
    ]);

    if (wfRes.status === 'fulfilled') {
      workflowStates.value = wfRes.value.data;
    }
    if (dsRes.status === 'fulfilled') {
      datasets.value = dsRes.value.data.datasets;
    }
    if (ddRes.status === 'fulfilled') {
      dataDasarList.value = ddRes.value.data.versions;
    }
  } catch (e) {
    console.warn('Gagal memuat data dashboard:', e);
  } finally {
    isLoading.value = false;
  }
}

// SweetAlert2 Interactive Workflow Transition with Top-Right Confirm
async function handleTransitionWithConfirm(
  dataset: DatasetItem,
  targetStatus: string,
  actionName: string,
  defaultNote: string,
  isDangerous = false
) {
  const confirmed = await confirmDialog({
    title: `${actionName}?`,
    text: `Konfirmasi pengalihan status dataset "${dataset.nama_dataset}" (${dataset.kode_dataset}) ke ${targetStatus}.`,
    confirmButtonText: `Ya, ${actionName}`,
    isDangerous: isDangerous,
    icon: isDangerous ? 'warning' : 'question'
  });

  if (!confirmed) return;

  try {
    const res = await sigApi.transitionWorkflow({
      dataset_id: dataset.id,
      status: targetStatus,
      catatan: defaultNote,
      actor_role: authStore.currentRole
    });

    // Update local dataset state
    const target = datasets.value.find((d) => d.id === dataset.id);
    if (target) {
      target.status = targetStatus as any;
      target.catatan_validasi = defaultNote;
    }

    notify.success(`${actionName} Berhasil`, res.message || `Status kini: ${targetStatus}`);
  } catch (e: any) {
    notify.error(`Gagal ${actionName}`, e.message || 'Terjadi kesalahan sistem');
  }
}

// SweetAlert2 Prompt Note for Revision / Rejection
async function handleRejectWithPrompt(
  dataset: DatasetItem,
  targetStatus: 'REJECTED_GIS' | 'DRAFT',
  actionTitle: string
) {
  const note = await promptNoteDialog({
    title: actionTitle,
    text: `Tuliskan catatan teknis atau alasan kebijakan untuk dataset "${dataset.nama_dataset}":`,
    inputPlaceholder: 'Tuliskan catatan revisi/alasan di sini...',
    confirmButtonText: 'Kirim Catatan',
    required: true
  });

  if (note === null) return; // Dibatalkan oleh user

  try {
    const res = await sigApi.transitionWorkflow({
      dataset_id: dataset.id,
      status: targetStatus,
      catatan: note,
      actor_role: authStore.currentRole
    });

    const target = datasets.value.find((d) => d.id === dataset.id);
    if (target) {
      target.status = targetStatus as any;
      target.catatan_validasi = note;
    }

    notify.warning('Catatan Dikirim', res.message || `Dataset dialihkan dengan catatan revisi.`);
  } catch (e: any) {
    notify.error('Gagal Mengirim Catatan', e.message || 'Terjadi kesalahan sistem');
  }
}

// SweetAlert2 Interactive Upload SHP
async function handleUploadShpWithConfirm() {
  const confirmed = await confirmDialog({
    title: 'Validasi & Unggah Paket SHP?',
    text: `Paket data dasar "${uploadFileName.value}" (${uploadTipe.value} - ${uploadTahun.value}) akan diverifikasi topologinya dan disimpan ke sistem.`,
    confirmButtonText: 'Upload & Validasi',
    icon: 'info'
  });

  if (!confirmed) return;

  isUploading.value = true;
  try {
    const res = await sigApi.uploadDataDasar({
      tipe_data: uploadTipe.value,
      sumber_resmi: uploadSumber.value,
      tahun_referensi: uploadTahun.value,
      nama_file: uploadFileName.value
    });
    lastValidationReport.value = res.data.validation_report;
    dataDasarList.value.push(res.data.version_record);
    notify.success('Upload SHP Berhasil', res.message || 'Paket SHP tervalidasi EPSG:4326.');
  } catch (e: any) {
    notify.error('Gagal Upload SHP', e.message || 'Verifikasi paket SHP gagal');
  } finally {
    isUploading.value = false;
  }
}

// SweetAlert2 Interactive Activate Data Dasar
async function handleActivateDataDasarWithConfirm(item: DataDasarVersion) {
  const confirmed = await confirmDialog({
    title: 'Tetapkan Sebagai Rujukan Aktif?',
    text: `Versi "${item.versi}" (${item.label_tipe} - Tahun ${item.tahun_referensi}) akan dijadikan acuan batas resmi aktif untuk seluruh OPD.`,
    confirmButtonText: 'Aktifkan Rujukan',
    icon: 'warning',
    isDangerous: true
  });

  if (!confirmed) return;

  try {
    const res = await sigApi.activateDataDasar(item.id);
    dataDasarList.value.forEach((v) => {
      if (v.tipe_data === res.data.tipe_data) {
        v.status = v.id === item.id ? 'AKTIF' : 'DIGANTIKAN';
      }
    });
    notify.success('Rujukan Diaktifkan', res.message || 'Data dasar wilayah berhasil ditetapkan.');
  } catch (e: any) {
    notify.error('Gagal Aktivasi Batas', e.message || 'Terjadi kesalahan sistem');
  }
}

function switchRole(role: RoleName) {
  authStore.quickSwitch(role);
  if (role !== 'superadmin' && activeTab.value === 'data_dasar') {
    activeTab.value = 'overview';
  }
  const roleObj = authStore.roles.find((r) => r.name === role);
  notify.info(
    'Peran Pengguna Berubah',
    `Kini bertindak sebagai: ${roleObj?.label || role} (${roleObj?.opd || 'Kab. Minahasa Selatan'})`
  );
}

onMounted(() => {
  loadDashboardData();
});
</script>

<template>
  <div class="dashboard-shell">
    <!-- Top Action & Persona Bar -->
    <header class="dash-topbar">
      <div class="topbar-left">
        <div class="dash-brand" @click="emit('navigate', 'geoportal')">
          <div class="dash-logo">
            <img
              src="/images/Lambang_Kabupaten_Minahasa_Selatan.png"
              alt="Lambang Kabupaten Minahasa Selatan"
              class="dash-logo-img"
              width="24"
              height="30"
            />
          </div>
          <div>
            <h1 class="dash-title">GeoMinsel Dashboard</h1>
            <p class="dash-sub">Sistem Informasi Geografis Kab. Minahasa Selatan</p>
          </div>
        </div>

        <!-- Role Quick Switcher for Evaluation -->
        <div class="role-switcher-wrap">
          <span class="switcher-label">Role Aktif:</span>
          <select
            :value="authStore.currentRole"
            @change="switchRole(($event.target as HTMLSelectElement).value as RoleName)"
            class="role-select"
          >
            <option value="superadmin">1. Superadmin (Diskominfo)</option>
            <option value="admin_gis">2. Admin GIS (Diskominfo)</option>
            <option value="admin_opd">3. Admin OPD (Dinas PUPR)</option>
            <option value="operator_opd">4. Operator OPD (Dinas Pertanian)</option>
            <option value="validator_gis">5. Validator GIS (Tim Teknis)</option>
            <option value="approver_data">6. Approver Data (Bappelitbangda)</option>
            <option value="pimpinan">7. Pimpinan Daerah (Bupati/Sekda)</option>
            <option value="publik">8. Publik (Masyarakat)</option>
          </select>
        </div>
      </div>

      <div class="topbar-right">
        <!-- User Badge -->
        <div class="user-chip">
          <div class="user-avatar">
            {{ authStore.user?.nama_lengkap?.charAt(0) || 'U' }}
          </div>
          <div class="user-details">
            <span class="user-name">{{ authStore.user?.nama_lengkap }}</span>
            <span class="user-opd">{{ authStore.opdLabel }}</span>
          </div>
        </div>

        <button class="btn-dash-action" @click="emit('navigate', 'geoportal')">
          <Layers :size="16" />
          <span>Lihat Peta</span>
        </button>

        <button class="btn-dash-logout" @click="authStore.logout(); emit('navigate', 'geoportal');">
          <LogOut :size="16" />
        </button>
      </div>
    </header>

    <!-- Main Workspace Layout -->
    <div class="dash-main">
      <!-- Sidebar Navigation -->
      <aside class="dash-sidebar">
        <nav class="sidebar-nav">
          <button
            class="nav-item"
            :class="{ active: activeTab === 'overview' }"
            @click="activeTab = 'overview'"
          >
            <LayoutDashboard :size="18" />
            <span>Ringkasan Eksekutif</span>
          </button>

          <button
            class="nav-item"
            :class="{ active: activeTab === 'datasets' }"
            @click="activeTab = 'datasets'"
          >
            <Database :size="18" />
            <span>Dataset Sektoral ({{ datasets.length }})</span>
          </button>

          <button
            v-if="['superadmin', 'admin_gis', 'validator_gis'].includes(authStore.currentRole)"
            class="nav-item"
            :class="{ active: activeTab === 'validation' }"
            @click="activeTab = 'validation'"
          >
            <CheckCheck :size="18" />
            <span>Antrean Validasi GIS</span>
          </button>

          <button
            v-if="['superadmin', 'approver_data', 'pimpinan'].includes(authStore.currentRole)"
            class="nav-item"
            :class="{ active: activeTab === 'approval' }"
            @click="activeTab = 'approval'"
          >
            <FileCheck2 :size="18" />
            <span>Persetujuan Terpusat</span>
          </button>

          <!-- Modul Eksklusif Superadmin: Upload SHP Batas Wilayah -->
          <button
            v-if="authStore.currentRole === 'superadmin'"
            class="nav-item item-superadmin"
            :class="{ active: activeTab === 'data_dasar' }"
            @click="activeTab = 'data_dasar'"
          >
            <ShieldAlert :size="18" />
            <span>Data Dasar SHP Wilayah</span>
          </button>

          <button
            v-if="['superadmin', 'admin_opd'].includes(authStore.currentRole)"
            class="nav-item"
            :class="{ active: activeTab === 'users' }"
            @click="activeTab = 'users'"
          >
            <Users :size="18" />
            <span>Pengguna & OPD</span>
          </button>

          <button
            class="nav-item"
            :class="{ active: activeTab === 'audit' }"
            @click="activeTab = 'audit'"
          >
            <History :size="18" />
            <span>Audit Trail & Log</span>
          </button>
        </nav>

        <div class="sidebar-footer">
          <div class="sdi-pill">
            <span class="sdi-dot"></span>
            <span>Satu Data Indonesia Minsel</span>
          </div>
        </div>
      </aside>

      <!-- Main Content Stage -->
      <section class="dash-stage">
        <!-- ============================================================== -->
        <!-- TAB 1: OVERVIEW (RINGKASAN EKSEKUTIF)                          -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'overview'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Ringkasan Tata Kelola Geospasial</h2>
              <p class="content-sub">Pemantauan data spasial 17 Kecamatan Kabupaten Minahasa Selatan</p>
            </div>
          </div>

          <!-- KPI Metrics Row -->
          <div class="kpi-grid">
            <div class="kpi-card">
              <span class="kpi-label">Cakupan Wilayah</span>
              <div class="kpi-val-row">
                <span class="kpi-val text-blue">17 / 17</span>
                <span class="kpi-badge badge-green">100% Aktif</span>
              </div>
              <span class="kpi-sub">Kecamatan terpetakan dalam SRID:4326</span>
            </div>

            <div class="kpi-card">
              <span class="kpi-label">Desa / Kelurahan</span>
              <div class="kpi-val-row">
                <span class="kpi-val">177</span>
                <span class="kpi-badge badge-blue">Terhubung</span>
              </div>
              <span class="kpi-sub">167 Desa dan 10 Kelurahan binaan</span>
            </div>

            <div class="kpi-card">
              <span class="kpi-label">Katalog Kategori</span>
              <div class="kpi-val-row">
                <span class="kpi-val text-amber">28</span>
                <span class="kpi-badge badge-amber">Standar SDI</span>
              </div>
              <span class="kpi-sub">5 Pilar data tematik daerah</span>
            </div>

            <div class="kpi-card">
              <span class="kpi-label">Persetujuan Terpusat</span>
              <div class="kpi-val-row">
                <span class="kpi-val text-emerald">12 Tahap</span>
                <span class="kpi-badge badge-green">Terstandar</span>
              </div>
              <span class="kpi-sub">Walidata Bappelitbangda terintegrasi</span>
            </div>
          </div>

          <!-- 17 Kecamatan Spatial Coverage Matrix -->
          <div class="dash-box">
            <div class="box-header">
              <h3 class="box-title">Matriks Distribusi Spasial 17 Kecamatan</h3>
              <button class="btn-link" @click="emit('navigate', 'geoportal')">Lihat Peta Lengkap</button>
            </div>
            <div class="kec-matrix">
              <div
                v-for="kec in ['Amurang', 'Amurang Barat', 'Amurang Timur', 'Tumpaan', 'Tareran', 'Suluun Tareran', 'Modoinding', 'Tompaso Baru', 'Maesaan', 'Ranoyapo', 'Motoling', 'Motoling Barat', 'Motoling Timur', 'Kumelembuai', 'Sinonsayang', 'Tenga', 'Tatapaan']"
                :key="kec"
                class="kec-pill"
              >
                <MapPin :size="14" class="icon-pin" />
                <span class="kp-name">{{ kec }}</span>
                <span class="kp-status">Tervalidasi</span>
              </div>
            </div>
          </div>

          <!-- Dataset Pipeline Status Strip -->
          <div class="dash-box">
            <div class="box-header">
              <h3 class="box-title">Status Alur Persetujuan Dataset Terpusat</h3>
            </div>
            <div class="status-summary-bar">
              <div class="ss-item">
                <span class="ss-num">{{ datasets.filter(d => d.status === 'DRAFT').length }}</span>
                <span class="ss-label">Draft Operator</span>
              </div>
              <div class="ss-arrow"><ArrowRight :size="14" /></div>
              <div class="ss-item">
                <span class="ss-num">{{ datasets.filter(d => ['SUBMITTED', 'IN_REVIEW_OPD', 'APPROVED_OPD'].includes(d.status)).length }}</span>
                <span class="ss-label">Review OPD</span>
              </div>
              <div class="ss-arrow"><ArrowRight :size="14" /></div>
              <div class="ss-item">
                <span class="ss-num">{{ datasets.filter(d => ['SUBMITTED_GIS', 'IN_VALIDATION_GIS', 'APPROVED_GIS'].includes(d.status)).length }}</span>
                <span class="ss-label">Validasi Teknis</span>
              </div>
              <div class="ss-arrow"><ArrowRight :size="14" /></div>
              <div class="ss-item">
                <span class="ss-num">{{ datasets.filter(d => ['SUBMITTED_APPROVAL', 'IN_APPROVAL', 'APPROVED'].includes(d.status)).length }}</span>
                <span class="ss-label">Persetujuan Pusat</span>
              </div>
              <div class="ss-arrow"><ArrowRight :size="14" /></div>
              <div class="ss-item ss-published">
                <span class="ss-num">{{ datasets.filter(d => d.status === 'PUBLISHED').length }}</span>
                <span class="ss-label">Tayang Geoportal</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 2: DATASET SEKTOARL (GRID & LIST + MULTI-FILTER)           -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'datasets'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Katalog Dataset Spasial Sektoral</h2>
              <p class="content-sub">Pengelolaan objek spasial per OPD dengan keterikatan hierarki 17 Kecamatan</p>
            </div>

            <!-- Controls: Search, Filters, and View Switcher -->
            <div class="header-controls-cluster">
              <!-- View Mode Switcher -->
              <div class="view-mode-toggle" role="group" aria-label="Pilihan Tampilan">
                <button
                  type="button"
                  class="toggle-btn"
                  :class="{ active: viewMode === 'grid' }"
                  @click="setViewMode('grid')"
                  title="Tampilan Grid (Bento Cards)"
                >
                  <LayoutGrid :size="15" />
                  <span>Grid</span>
                </button>
                <button
                  type="button"
                  class="toggle-btn"
                  :class="{ active: viewMode === 'list' }"
                  @click="setViewMode('list')"
                  title="Tampilan Tabel Rinci (List)"
                >
                  <List :size="15" />
                  <span>List</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Multi-Criteria Filter & Search Toolbar -->
          <div class="filter-toolbar-card">
            <div class="toolbar-top-row">
              <!-- Search Input -->
              <div class="search-input-wrap">
                <Search :size="16" class="search-icon" />
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Cari nama dataset, kode, OPD, kecamatan, atau kategori..."
                  class="search-input-field"
                />
                <button
                  v-if="searchQuery"
                  type="button"
                  class="clear-search-btn"
                  @click="searchQuery = ''"
                  title="Hapus pencarian"
                >
                  <X :size="14" />
                </button>
              </div>

              <!-- Reset Filter Button -->
              <button
                v-if="activeFilterCount > 0"
                type="button"
                class="btn-reset-filters"
                @click="resetFilters"
                title="Kembalikan semua filter ke awal"
              >
                <RotateCcw :size="13" />
                <span>Reset Filter ({{ activeFilterCount }})</span>
              </button>
            </div>

            <div class="toolbar-filters-row">
              <div class="filter-group">
                <label class="filter-label">Status Alur:</label>
                <select v-model="filterStatus" class="filter-select-enhanced">
                  <option value="">Semua Status ({{ datasets.length }})</option>
                  <option value="DRAFT">DRAFT</option>
                  <option value="SUBMITTED_GIS">SUBMITTED_GIS</option>
                  <option value="IN_VALIDATION_GIS">IN_VALIDATION_GIS</option>
                  <option value="APPROVED_GIS">APPROVED_GIS</option>
                  <option value="SUBMITTED_APPROVAL">SUBMITTED_APPROVAL</option>
                  <option value="IN_APPROVAL">IN_APPROVAL</option>
                  <option value="APPROVED">APPROVED</option>
                  <option value="PUBLISHED">PUBLISHED</option>
                  <option value="REJECTED_GIS">REJECTED_GIS</option>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label">Kecamatan:</label>
                <select v-model="filterKecamatan" class="filter-select-enhanced">
                  <option value="">Semua Kecamatan (17)</option>
                  <option v-for="kec in daftarKecamatan" :key="kec" :value="kec">
                    {{ kec }}
                  </option>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label">Geometri:</label>
                <select v-model="filterGeometri" class="filter-select-enhanced">
                  <option value="">Semua Geometri</option>
                  <option v-for="g in uniqueGeometri" :key="g" :value="g">
                    {{ g }}
                  </option>
                </select>
              </div>

              <div class="filter-group">
                <label class="filter-label">OPD Produsen:</label>
                <select v-model="filterOpd" class="filter-select-enhanced">
                  <option value="">Semua OPD</option>
                  <option v-for="opd in uniqueOpds" :key="opd" :value="opd">
                    {{ opd }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Empty State (No Search Match) -->
          <div v-if="filteredDatasets.length === 0" class="empty-state-card">
            <Search :size="40" class="empty-icon" />
            <h3 class="empty-title">Tidak Ada Dataset yang Cocok</h3>
            <p class="empty-desc">
              Tidak ditemukan data spasial yang memenuhi kriteria pencarian dan filter aktif.
            </p>
            <button type="button" class="btn-empty-reset" @click="resetFilters">
              <RotateCcw :size="14" />
              <span>Bersihkan Filter &amp; Pencarian</span>
            </button>
          </div>

          <!-- TAMPILAN 1: GRID MODE (BENTO CARDS) -->
          <div v-else-if="viewMode === 'grid'" class="dataset-grid">
            <div
              v-for="d in filteredDatasets"
              :key="d.id"
              class="dataset-bento-card"
            >
              <div class="bento-header">
                <span class="badge-opd">{{ d.opd }}</span>
                <span class="status-pill" :class="'st-' + d.status.toLowerCase()">
                  {{ d.status }}
                </span>
              </div>

              <div class="bento-body">
                <span class="bento-code">{{ d.kode_dataset }}</span>
                <h3 class="bento-title">{{ d.nama_dataset }}</h3>
                <p v-if="d.deskripsi" class="bento-desc">{{ d.deskripsi }}</p>
              </div>

              <div class="bento-meta-chips">
                <span class="chip chip-geom">
                  <Layers :size="12" />
                  <span>{{ d.tipe_geometri }}</span>
                </span>
                <span class="chip chip-features">
                  <span>{{ d.jumlah_fitur }} fitur</span>
                </span>
                <span class="chip chip-kec">
                  <MapPin :size="12" />
                  <span>{{ d.kecamatan || 'Kabupaten' }}</span>
                </span>
                <span class="chip chip-crs">
                  <span>{{ d.crs || 'EPSG:4326' }}</span>
                </span>
              </div>

              <div class="bento-footer">
                <span class="bento-cat-label">{{ d.nama_kategori }}</span>

                <div class="action-btn-group">
                  <button
                    v-if="['superadmin', 'operator_opd', 'admin_opd'].includes(authStore.currentRole) && d.status === 'DRAFT'"
                    class="btn-act btn-submit"
                    @click="handleTransitionWithConfirm(d, 'IN_VALIDATION_GIS', 'Ajukan Validasi', 'Diajukan ke validasi teknis GIS')"
                  >
                    <Send :size="13" />
                    <span>Ajukan Validasi</span>
                  </button>

                  <button
                    v-if="['superadmin', 'admin_gis'].includes(authStore.currentRole) && d.status === 'APPROVED'"
                    class="btn-act btn-publish"
                    @click="handleTransitionWithConfirm(d, 'PUBLISHED', 'Publikasikan ke Geoportal', 'Diterbitkan ke katalog publik geoportal')"
                  >
                    <Layers :size="13" />
                    <span>Publikasikan</span>
                  </button>

                  <span v-if="d.status === 'PUBLISHED'" class="text-published">
                    <CheckCircle2 :size="14" />
                    <span>Tayang Aktif</span>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- TAMPILAN 2: LIST MODE (DETAILED TABLE) -->
          <div v-else class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Kode &amp; Nama Dataset</th>
                  <th>OPD Penanggung Jawab</th>
                  <th>Kategori &amp; Geometri</th>
                  <th>Cakupan Wilayah</th>
                  <th>Status Workflow</th>
                  <th>Tindakan Otoritas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in filteredDatasets" :key="d.id">
                  <td>
                    <div class="cell-main">
                      <span class="cell-title">{{ d.nama_dataset }}</span>
                      <span class="cell-meta">{{ d.kode_dataset }} &bull; {{ d.jumlah_fitur }} fitur</span>
                    </div>
                  </td>
                  <td>
                    <span class="badge-opd">{{ d.opd }}</span>
                  </td>
                  <td>
                    <div class="cell-stacked">
                      <span class="text-cat">{{ d.nama_kategori }}</span>
                      <span class="text-geom">{{ d.tipe_geometri }} ({{ d.crs }})</span>
                    </div>
                  </td>
                  <td>
                    <span class="badge-kec">{{ d.kecamatan || 'Kabupaten' }}</span>
                  </td>
                  <td>
                    <span class="status-pill" :class="'st-' + d.status.toLowerCase()">
                      {{ d.status }}
                    </span>
                  </td>
                  <td>
                    <div class="action-btn-group">
                      <button
                        v-if="['superadmin', 'operator_opd', 'admin_opd'].includes(authStore.currentRole) && d.status === 'DRAFT'"
                        class="btn-act btn-submit"
                        @click="handleTransitionWithConfirm(d, 'IN_VALIDATION_GIS', 'Ajukan Validasi', 'Diajukan ke validasi teknis GIS')"
                      >
                        <Send :size="13" />
                        <span>Ajukan Validasi</span>
                      </button>

                      <button
                        v-if="['superadmin', 'admin_gis'].includes(authStore.currentRole) && d.status === 'APPROVED'"
                        class="btn-act btn-publish"
                        @click="handleTransitionWithConfirm(d, 'PUBLISHED', 'Publikasikan ke Geoportal', 'Diterbitkan ke katalog publik geoportal')"
                      >
                        <Layers :size="13" />
                        <span>Publikasikan</span>
                      </button>

                      <span v-if="d.status === 'PUBLISHED'" class="text-published">
                        <CheckCircle2 :size="14" />
                        <span>Tayang Aktif</span>
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 3: ANTREAN VALIDASI TEKNIS GIS (WITH SEARCH & SWEETALERT)  -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'validation'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Antrean Validasi Teknis Geospasial</h2>
              <p class="content-sub">Pemeriksaan topologi, bebas tumpang-tindih (no overlap), sistem koordinat EPSG:4326, dan kelengkapan atribut</p>
            </div>

            <!-- Validation Search -->
            <div class="search-input-wrap search-sm">
              <Search :size="15" class="search-icon" />
              <input
                v-model="searchValidationQuery"
                type="text"
                placeholder="Cari antrean validasi..."
                class="search-input-field"
              />
              <button
                v-if="searchValidationQuery"
                type="button"
                class="clear-search-btn"
                @click="searchValidationQuery = ''"
              >
                <X :size="13" />
              </button>
            </div>
          </div>

          <div v-if="filteredValidationDatasets.length === 0" class="empty-state-card">
            <CheckCheck :size="40" class="empty-icon text-emerald" />
            <h3 class="empty-title">Tidak Ada Antrean Validasi</h3>
            <p class="empty-desc">Seluruh pengajuan dataset spasial telah tervalidasi atau tidak ada data yang cocok.</p>
          </div>

          <div v-else class="cards-queue">
            <div
              v-for="d in filteredValidationDatasets"
              :key="d.id"
              class="validation-card"
            >
              <div class="vc-header">
                <div>
                  <span class="vc-code">{{ d.kode_dataset }}</span>
                  <h3 class="vc-title">{{ d.nama_dataset }}</h3>
                </div>
                <span class="status-pill st-in_validation_gis">Dalam Pemeriksaan</span>
              </div>

              <div class="vc-body">
                <div class="check-item check-pass">
                  <CheckCircle2 :size="16" />
                  <span>Proyeksi Koordinat: WGS84 EPSG:4326 (Lolos)</span>
                </div>
                <div class="check-item check-pass">
                  <CheckCircle2 :size="16" />
                  <span>Keterikatan Hierarki Wilayah: {{ d.kecamatan }} (Valid)</span>
                </div>
                <div class="check-item check-pass">
                  <CheckCircle2 :size="16" />
                  <span>Integritas Geometri: {{ d.tipe_geometri }} 0 Overlap</span>
                </div>
              </div>

              <div class="vc-footer">
                <button
                  class="btn-act btn-reject"
                  @click="handleRejectWithPrompt(d, 'REJECTED_GIS', 'Minta Catatan Revisi OPD')"
                >
                  <AlertTriangle :size="14" />
                  <span>Minta Revisi OPD</span>
                </button>
                <button
                  class="btn-act btn-approve"
                  @click="handleTransitionWithConfirm(d, 'APPROVED_GIS', 'Loloskan Validasi Teknis', 'Lolos verifikasi teknis geometri & CRS EPSG:4326')"
                >
                  <CheckCircle2 :size="14" />
                  <span>Lolos Validasi Teknis</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 4: PERSETUJUAN TERPUSAT WALIDATA (WITH SEARCH & ALERT)     -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'approval'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Antrean Persetujuan Terpusat (Central Approval)</h2>
              <p class="content-sub">Kewenangan Walidata Bappelitbangda sebelum dataset diterbitkan resmi ke publik</p>
            </div>

            <!-- Approval Search -->
            <div class="search-input-wrap search-sm">
              <Search :size="15" class="search-icon" />
              <input
                v-model="searchApprovalQuery"
                type="text"
                placeholder="Cari dataset pengajuan..."
                class="search-input-field"
              />
              <button
                v-if="searchApprovalQuery"
                type="button"
                class="clear-search-btn"
                @click="searchApprovalQuery = ''"
              >
                <X :size="13" />
              </button>
            </div>
          </div>

          <div v-if="filteredApprovalDatasets.length === 0" class="empty-state-card">
            <FileCheck2 :size="40" class="empty-icon text-blue" />
            <h3 class="empty-title">Tidak Ada Antrean Persetujuan</h3>
            <p class="empty-desc">Semua pengajuan dataset telah disetujui Walidata Bappelitbangda atau belum ada antrean baru.</p>
          </div>

          <div v-else class="cards-queue">
            <div
              v-for="d in filteredApprovalDatasets"
              :key="d.id"
              class="approval-card"
            >
              <div class="ac-top">
                <div class="ac-tags">
                  <span class="badge-opd">{{ d.opd }}</span>
                  <span class="badge-kec">{{ d.kecamatan }}</span>
                </div>
                <span class="ac-date">{{ d.updated_at }}</span>
              </div>

              <h3 class="ac-title">{{ d.nama_dataset }}</h3>
              <p class="ac-note"><strong>Catatan Teknis GIS:</strong> {{ d.catatan_validasi }}</p>

              <div class="ac-actions">
                <button
                  class="btn-act btn-reject"
                  @click="handleRejectWithPrompt(d, 'DRAFT', 'Tolak Pengajuan Dataset')"
                >
                  <AlertTriangle :size="14" />
                  <span>Tolak Pengajuan</span>
                </button>
                <button
                  class="btn-act btn-approve"
                  @click="handleTransitionWithConfirm(d, 'APPROVED', 'Beri Persetujuan Walidata', 'Disetujui Walidata Bappelitbangda No: 045/SDI-MINSEL/2026')"
                >
                  <CheckCircle2 :size="14" />
                  <span>Beri Persetujuan Terpusat</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 5: DATA DASAR SHP BATAS WILAYAH (KHUSUS SUPERADMIN)         -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'data_dasar'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Modul Data Dasar Geospasial (Batas Administrasi)</h2>
              <p class="content-sub">Khusus Superadmin: Unggah dan aktivasi file ZIP Shapefile resmi batas Kabupaten, 17 Kecamatan, dan 167 Desa/Kelurahan</p>
            </div>
          </div>

          <!-- Upload Box -->
          <div class="upload-panel-card">
            <h3 class="panel-box-title">Unggah Paket Data Dasar ZIP (SHP, SHX, DBF, PRJ)</h3>
            <p class="panel-box-sub">
              Sistem secara otomatis memverifikasi keberadaan 4 komponen Shapefile wajib dan memastikan 
              sistem proyeksi menggunakan <strong>EPSG:4326 (WGS84)</strong>.
            </p>

            <form class="upload-form-grid" @submit.prevent="handleUploadShpWithConfirm">
              <div class="form-cell">
                <label class="form-lbl">Tingkatan Batas Administrasi</label>
                <select v-model="uploadTipe" class="form-input-box">
                  <option value="KABUPATEN">Batas Kabupaten Minahasa Selatan (1 Fitur)</option>
                  <option value="KECAMATAN">Batas 17 Kecamatan se-Minsel (17 Fitur)</option>
                  <option value="DESA">Batas 167 Desa & 10 Kelurahan (177 Fitur)</option>
                </select>
              </div>

              <div class="form-cell">
                <label class="form-lbl">Sumber Resmi / Dasar Hukum</label>
                <input
                  v-model="uploadSumber"
                  type="text"
                  class="form-input-box"
                  placeholder="Contoh: BIG / Permendagri / Perda RT-RW"
                  required
                />
              </div>

              <div class="form-cell">
                <label class="form-lbl">Tahun Referensi</label>
                <input v-model.number="uploadTahun" type="number" class="form-input-box" required />
              </div>

              <div class="form-cell">
                <label class="form-lbl">Nama Berkas ZIP</label>
                <input v-model="uploadFileName" type="text" class="form-input-box" required />
              </div>

              <div class="form-submit-cell">
                <button type="submit" class="btn-upload" :disabled="isUploading">
                  <Upload :size="16" />
                  <span>{{ isUploading ? 'Memeriksa & Menyimpan...' : 'Upload & Validasi SHP ZIP' }}</span>
                </button>
              </div>
            </form>

            <!-- Verification Inspector Result Box -->
            <div v-if="lastValidationReport" class="verification-report-box">
              <div class="vr-title">
                <CheckCircle2 :size="18" class="text-green" />
                <span>Hasil Pemeriksaan Otomatis Berkas SHP:</span>
              </div>
              <div class="vr-grid">
                <div class="vr-item"><span>.shp Geometri:</span> <strong>Lengkap</strong></div>
                <div class="vr-item"><span>.shx Index:</span> <strong>Lengkap</strong></div>
                <div class="vr-item"><span>.dbf Atribut:</span> <strong>Lengkap</strong></div>
                <div class="vr-item"><span>.prj Proyeksi:</span> <strong>EPSG:4326 (Valid)</strong></div>
                <div class="vr-item"><span>Topologi:</span> <strong>Bebas Overlap</strong></div>
                <div class="vr-item"><span>Jumlah Fitur:</span> <strong>{{ lastValidationReport.features }}</strong></div>
              </div>
            </div>
          </div>

          <!-- Existing Versions Table -->
          <div class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Tipe Batas &amp; Versi</th>
                  <th>Berkas ZIP &amp; Sumber</th>
                  <th>CRS &amp; Fitur</th>
                  <th>Status Acuan</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="v in dataDasarList" :key="v.id">
                  <td>
                    <div class="cell-main">
                      <span class="cell-title">{{ v.label_tipe }}</span>
                      <span class="cell-meta">Versi: {{ v.versi }} &bull; {{ v.tahun_referensi }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="cell-stacked">
                      <span class="text-zip"><FileArchive :size="13" /> {{ v.nama_file_zip }}</span>
                      <span class="text-source">{{ v.sumber_resmi }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="cell-stacked">
                      <span>{{ v.crs_terdeteksi }}</span>
                      <span class="text-geom">{{ v.jumlah_fitur }} Polygon</span>
                    </div>
                  </td>
                  <td>
                    <span class="status-pill" :class="v.status === 'AKTIF' ? 'st-published' : 'st-draft'">
                      {{ v.status }}
                    </span>
                  </td>
                  <td>
                    <button
                      v-if="v.status !== 'AKTIF'"
                      class="btn-act btn-approve"
                      @click="handleActivateDataDasarWithConfirm(v)"
                    >
                      <span>Aktifkan Acuan</span>
                    </button>
                    <span v-else class="text-active-mark">
                      <CheckCircle2 :size="14" />
                      <span>Rujukan Utama</span>
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 6: USERS & OPD (WITH SEARCH & ROLE FILTER)                 -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'users'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Manajemen Akun Pengguna &amp; OPD</h2>
              <p class="content-sub">Penerapan pemisahan wewenang 8 Role dan pembatasan akses data sektoral</p>
            </div>

            <!-- User Search & Role Filter -->
            <div class="header-controls-cluster">
              <div class="search-input-wrap search-sm">
                <Search :size="15" class="search-icon" />
                <input
                  v-model="searchUserQuery"
                  type="text"
                  placeholder="Cari nama, role, atau OPD..."
                  class="search-input-field"
                />
                <button
                  v-if="searchUserQuery"
                  type="button"
                  class="clear-search-btn"
                  @click="searchUserQuery = ''"
                >
                  <X :size="13" />
                </button>
              </div>

              <select v-model="filterUserRole" class="filter-select-enhanced">
                <option value="">Semua Peran (8 Role)</option>
                <option v-for="r in authStore.roles" :key="r.name" :value="r.name">
                  {{ r.label }}
                </option>
              </select>
            </div>
          </div>

          <div class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nama Pengguna &amp; Peran</th>
                  <th>Role Teknis</th>
                  <th>Organisasi Perangkat Daerah (OPD)</th>
                  <th>Status Akses</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in filteredUsers" :key="r.id">
                  <td>
                    <div class="cell-main">
                      <span class="cell-title">{{ r.label }}</span>
                      <span class="cell-meta">ID Pengguna: #{{ r.id }} &bull; Kab. Minahasa Selatan</span>
                    </div>
                  </td>
                  <td>
                    <span class="badge-role">{{ r.name }}</span>
                  </td>
                  <td>{{ r.opd }}</td>
                  <td>
                    <span class="status-pill st-published">Aktif</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 7: AUDIT LOG                                              -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'audit'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Jejak Audit & Riwayat Perubahan (Auditability)</h2>
              <p class="content-sub">Rekaman lengkap setiap aksi validasi teknis, persetujuan Walidata, dan penerbitan dataset</p>
            </div>
          </div>

          <div class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Waktu (WITA)</th>
                  <th>Aktor & Role</th>
                  <th>Objek / Dataset</th>
                  <th>Tindakan</th>
                  <th>Catatan Resmi</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>2026-11-10 10:15</td>
                  <td>Validator GIS</td>
                  <td>Peta Titik Rawan Banjir DAS Ranoyapo</td>
                  <td>Validasi Teknis</td>
                  <td>Topologi poligon dan proyeksi SRID 4326 terverifikasi valid.</td>
                </tr>
                <tr>
                  <td>2026-11-09 16:30</td>
                  <td>Approver Data (Bappelitbangda)</td>
                  <td>Sentra Komoditas Holtikultura Modoinding</td>
                  <td>Persetujuan Terpusat</td>
                  <td>Penerbitan surat persetujuan Walidata No: 045/SDI-MINSEL/2026.</td>
                </tr>
                <tr>
                  <td>2026-11-09 17:00</td>
                  <td>Admin GIS</td>
                  <td>Sentra Komoditas Holtikultura Modoinding</td>
                  <td>Publikasi Geoportal</td>
                  <td>Layer aktif di portal publik untuk kategori Pertanian.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.dashboard-shell {
  display: flex;
  flex-direction: column;
  width: 100vw;
  height: 100vh;
  background-color: #0b132b;
  color: #f8fafc;
  overflow: hidden;
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
}

/* TOPBAR */
.dash-topbar {
  height: 64px;
  background: #0f172a;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  z-index: 30;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 24px;
}

.dash-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.dash-logo {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  border: 1px solid rgba(245, 158, 11, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3px;
  flex-shrink: 0;
}

.dash-logo-img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.4));
}

.dash-title {
  font-size: 14px;
  font-weight: 800;
  margin: 0;
  color: #ffffff;
}

.dash-sub {
  font-size: 11px;
  color: #94a3b8;
  margin: 0;
}

.role-switcher-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.05);
  padding: 4px 10px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.switcher-label {
  font-size: 11px;
  color: #94a3b8;
  font-weight: 600;
}

.role-select {
  background: transparent;
  border: none;
  color: #38bdf8;
  font-size: 12px;
  font-weight: 700;
  outline: none;
  cursor: pointer;
}

.role-select option {
  background: #0f172a;
  color: #f8fafc;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 16px;
}

.user-chip {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: #2563eb;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 13px;
}

.user-details {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: 12px;
  font-weight: 700;
  color: #f8fafc;
}

.user-opd {
  font-size: 10px;
  color: #94a3b8;
}

.btn-dash-action {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #f8fafc;
  font-size: 12px;
  font-weight: 600;
  padding: 6px 14px;
  border-radius: 6px;
  cursor: pointer;
}

.btn-dash-action:hover {
  background: rgba(255, 255, 255, 0.15);
}

.btn-dash-logout {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-dash-logout:hover {
  color: #ef4444;
}

/* MAIN BODY */
.dash-main {
  display: flex;
  flex: 1;
  height: calc(100vh - 64px);
  overflow: hidden;
}

/* SIDEBAR */
.dash-sidebar {
  width: 250px;
  background: #0d1527;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 16px 12px;
  box-sizing: border-box;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 13px;
  font-weight: 500;
  padding: 10px 14px;
  border-radius: 8px;
  cursor: pointer;
  text-align: left;
  transition: all 0.15s ease;
}

.nav-item:hover {
  color: #f8fafc;
  background: rgba(255, 255, 255, 0.05);
}

.nav-item.active {
  background: rgba(56, 189, 248, 0.12);
  color: #38bdf8;
  font-weight: 600;
}

.nav-item.item-superadmin {
  color: #f59e0b;
}

.nav-item.item-superadmin.active {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
}

.sidebar-footer {
  padding-top: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.sdi-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #64748b;
}

.sdi-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
}

/* STAGE */
.dash-stage {
  flex: 1;
  padding: 24px 32px;
  overflow-y: auto;
  box-sizing: border-box;
  position: relative;
}

.action-alert {
  position: sticky;
  top: 0;
  z-index: 40;
  display: flex;
  align-items: center;
  gap: 10px;
  background: #065f46;
  border: 1px solid #10b981;
  color: #ecfdf5;
  padding: 12px 18px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.tab-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.content-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  padding-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.content-title {
  font-size: 22px;
  font-weight: 800;
  color: #ffffff;
  margin: 0 0 4px 0;
}

.content-sub {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}

/* KPI GRID */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.kpi-card {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.kpi-label {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.kpi-val-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.kpi-val {
  font-size: 26px;
  font-weight: 800;
  font-family: 'JetBrains Mono', monospace;
  color: #ffffff;
}

.text-blue { color: #38bdf8; }
.text-amber { color: #f59e0b; }
.text-emerald { color: #10b981; }

.kpi-badge {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 9999px;
}

.badge-green { background: rgba(16, 185, 129, 0.15); color: #10b981; }
.badge-blue { background: rgba(56, 189, 248, 0.15); color: #38bdf8; }
.badge-amber { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }

.kpi-sub {
  font-size: 11px;
  color: #64748b;
}

/* DASH BOX */
.dash-box {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  padding: 20px;
}

.box-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}

.box-title {
  font-size: 15px;
  font-weight: 700;
  color: #ffffff;
}

.btn-link {
  background: transparent;
  border: none;
  color: #38bdf8;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

/* KECAMATAN MATRIX */
.kec-matrix {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 10px;
}

.kec-pill {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  padding: 8px 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.icon-pin {
  color: #38bdf8;
}

.kp-name {
  font-size: 12px;
  font-weight: 600;
  color: #f8fafc;
  flex: 1;
}

.kp-status {
  font-size: 10px;
  color: #10b981;
}

/* STATUS SUMMARY BAR */
.status-summary-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(255, 255, 255, 0.03);
  padding: 16px 20px;
  border-radius: 10px;
}

.ss-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}

.ss-num {
  font-size: 20px;
  font-weight: 800;
  color: #ffffff;
  font-family: 'JetBrains Mono', monospace;
}

.ss-label {
  font-size: 11px;
  color: #94a3b8;
}

.ss-published .ss-num {
  color: #10b981;
}

.ss-arrow {
  color: #475569;
}

/* TABLE STYLING */
.table-card {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.data-table th {
  background: rgba(255, 255, 255, 0.04);
  color: #94a3b8;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 12px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.data-table td {
  padding: 14px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  font-size: 13px;
  color: #cbd5e1;
}

.cell-main {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.cell-title {
  font-weight: 600;
  color: #f8fafc;
}

.cell-meta {
  font-size: 11px;
  color: #64748b;
}

.cell-stacked {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.text-cat {
  font-weight: 600;
  color: #ffffff;
}

.text-geom {
  font-size: 11px;
  color: #94a3b8;
}

.badge-opd {
  background: rgba(14, 165, 233, 0.12);
  color: #38bdf8;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
}

.badge-kec {
  background: rgba(255, 255, 255, 0.06);
  color: #cbd5e1;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 11px;
}

.badge-role {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 700;
}

.status-pill {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 700;
}

.st-draft { background: rgba(148, 163, 184, 0.15); color: #94a3b8; }
.st-in_validation_gis { background: rgba(139, 92, 246, 0.15); color: #a78bfa; }
.st-approved_gis { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.st-in_approval { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
.st-published { background: rgba(37, 99, 235, 0.2); color: #60a5fa; }

.action-btn-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-act {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-submit { background: #0284c7; color: #ffffff; }
.btn-publish { background: #16a34a; color: #ffffff; }
.btn-reject { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); }
.btn-approve { background: #059669; color: #ffffff; }

.text-published {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #10b981;
  font-size: 12px;
  font-weight: 600;
}

/* QUEUES (VALIDATION & APPROVAL) */
.cards-queue {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(440px, 1fr));
  gap: 16px;
}

.validation-card, .approval-card {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.vc-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.vc-code {
  font-size: 11px;
  color: #64748b;
  font-family: 'JetBrains Mono', monospace;
}

.vc-title {
  font-size: 15px;
  font-weight: 700;
  color: #ffffff;
  margin: 2px 0 0 0;
}

.vc-body {
  display: flex;
  flex-direction: column;
  gap: 6px;
  background: rgba(255, 255, 255, 0.03);
  padding: 12px;
  border-radius: 8px;
}

.check-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
}

.check-pass {
  color: #10b981;
}

.vc-footer, .ac-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
}

.ac-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.ac-tags {
  display: flex;
  gap: 6px;
}

.ac-date {
  font-size: 11px;
  color: #64748b;
}

.ac-title {
  font-size: 16px;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
}

.ac-note {
  font-size: 12px;
  color: #cbd5e1;
  background: rgba(245, 158, 11, 0.08);
  border-left: 3px solid #f59e0b;
  padding: 8px 12px;
  border-radius: 4px;
  margin: 0;
}

/* SUPERADMIN SHP UPLOAD PANEL */
.upload-panel-card {
  background: #0f172a;
  border: 1px solid rgba(245, 158, 11, 0.3);
  border-radius: 14px;
  padding: 24px;
}

.panel-box-title {
  font-size: 16px;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 6px 0;
}

.panel-box-sub {
  font-size: 13px;
  color: #94a3b8;
  margin: 0 0 20px 0;
  line-height: 1.5;
}

.upload-form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.form-cell {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-lbl {
  font-size: 12px;
  font-weight: 600;
  color: #cbd5e1;
}

.form-input-box {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  padding: 10px 12px;
  color: #ffffff;
  font-size: 13px;
  outline: none;
}

.form-input-box:focus {
  border-color: #f59e0b;
}

.form-submit-cell {
  grid-column: span 2;
  display: flex;
  justify-content: flex-end;
}

.btn-upload {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #d97706;
  color: #ffffff;
  border: none;
  font-size: 13px;
  font-weight: 700;
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-upload:hover {
  background: #b45309;
}

.verification-report-box {
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.3);
  border-radius: 10px;
  padding: 16px;
}

.vr-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #10b981;
  margin-bottom: 12px;
}

.vr-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  font-size: 12px;
  color: #cbd5e1;
}

.vr-item span {
  color: #94a3b8;
}

.vr-item strong {
  color: #ffffff;
  margin-left: 4px;
}

.text-zip {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 600;
  color: #ffffff;
}

.text-source {
  font-size: 11px;
  color: #94a3b8;
}

.text-active-mark {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #10b981;
  font-size: 12px;
  font-weight: 700;
}

/* HEADER CONTROLS & VIEW TOGGLE */
.header-controls-cluster {
  display: flex;
  align-items: center;
  gap: 12px;
}

.view-mode-toggle {
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  padding: 3px;
  gap: 2px;
}

.toggle-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: transparent;
  border: none;
  border-radius: 6px;
  color: #94a3b8;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.toggle-btn:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.05);
}

.toggle-btn.active {
  background: #0284c7;
  color: #ffffff;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* FILTER & SEARCH TOOLBAR */
.filter-toolbar-card {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 16px 20px;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.toolbar-top-row {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
}

.search-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
  flex: 1;
}

.search-input-wrap.search-sm {
  width: 280px;
  flex: none;
}

.search-icon {
  position: absolute;
  left: 12px;
  color: #64748b;
  pointer-events: none;
}

.search-input-field {
  width: 100%;
  padding: 9px 36px 9px 38px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #ffffff;
  font-size: 13px;
  outline: none;
  transition: all 0.2s;
  box-sizing: border-box;
}

.search-input-field:focus {
  border-color: #38bdf8;
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
}

.clear-search-btn {
  position: absolute;
  right: 10px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  cursor: pointer;
}

.clear-search-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
}

.btn-reset-filters {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.2s;
}

.btn-reset-filters:hover {
  background: rgba(239, 68, 68, 0.2);
  color: #fca5a5;
}

.toolbar-filters-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.filter-label {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.filter-select-enhanced {
  width: 100%;
  padding: 8px 12px;
  background: #0b132b;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  color: #f1f5f9;
  font-size: 12px;
  font-weight: 500;
  outline: none;
  cursor: pointer;
  box-sizing: border-box;
}

.filter-select-enhanced:focus {
  border-color: #38bdf8;
}

.filter-select-enhanced option {
  background: #0f172a;
  color: #f8fafc;
}

/* EMPTY STATE */
.empty-state-card {
  background: #0f172a;
  border: 1px dashed rgba(255, 255, 255, 0.15);
  border-radius: 14px;
  padding: 48px 24px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 8px;
}

.empty-icon {
  color: #64748b;
  margin-bottom: 8px;
}

.empty-title {
  font-size: 17px;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
}

.empty-desc {
  font-size: 13px;
  color: #94a3b8;
  max-width: 440px;
  margin: 0 0 12px 0;
}

.btn-empty-reset {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: #0284c7;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-empty-reset:hover {
  background: #0369a1;
}

/* GRID MODE (BENTO CARDS) */
.dataset-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 18px;
}

.dataset-bento-card {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  padding: 20px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 14px;
  transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
  position: relative;
}

.dataset-bento-card:hover {
  transform: translateY(-2px);
  border-color: rgba(56, 189, 248, 0.3);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.2);
}

.bento-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.bento-body {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.bento-code {
  font-size: 11px;
  font-family: 'JetBrains Mono', monospace;
  color: #64748b;
  letter-spacing: 0.5px;
}

.bento-title {
  font-size: 15px;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.35;
  margin: 0;
}

.bento-desc {
  font-size: 12px;
  color: #94a3b8;
  line-height: 1.4;
  margin: 4px 0 0 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.bento-meta-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 500;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
}

.chip-geom {
  color: #38bdf8;
  border-color: rgba(56, 189, 248, 0.2);
}

.chip-features {
  color: #a78bfa;
}

.chip-kec {
  color: #34d399;
}

.chip-crs {
  font-family: 'JetBrains Mono', monospace;
  color: #94a3b8;
}

.bento-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  gap: 10px;
}

.bento-cat-label {
  font-size: 11px;
  font-weight: 600;
  color: #f59e0b;
}

@media (max-width: 1024px) {
  .toolbar-filters-row {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .toolbar-filters-row {
    grid-template-columns: 1fr;
  }
  .toolbar-top-row {
    flex-direction: column;
  }
  .search-input-wrap.search-sm {
    width: 100%;
  }
}
</style>
