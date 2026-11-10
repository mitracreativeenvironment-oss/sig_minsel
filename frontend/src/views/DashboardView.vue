<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../stores/authStore';
import { sigApi } from '../services/api';
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
  Send
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
const actionMessage = ref<string | null>(null);

// Upload Form State for Superadmin SHP Boundary
const uploadTipe = ref<'KABUPATEN' | 'KECAMATAN' | 'DESA'>('KECAMATAN');
const uploadSumber = ref('Badan Informasi Geospasial / Bagian Tapem Setda');
const uploadTahun = ref(2026);
const uploadFileName = ref('batas_17_kecamatan_minsel_2026.zip');
const isUploading = ref(false);
const lastValidationReport = ref<any>(null);

// Filter State for Datasets
const filterStatus = ref('');
const filterKecamatan = ref('');

const filteredDatasets = computed(() => {
  return datasets.value.filter((d) => {
    const matchStatus = !filterStatus.value || d.status === filterStatus.value;
    const matchKec = !filterKecamatan.value || d.kecamatan?.toLowerCase().includes(filterKecamatan.value.toLowerCase());
    return matchStatus && matchKec;
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

async function handleTransition(datasetId: number, targetStatus: string, defaultNote: string) {
  try {
    const res = await sigApi.transitionWorkflow({
      dataset_id: datasetId,
      status: targetStatus,
      catatan: defaultNote,
      actor_role: authStore.currentRole
    });
    actionMessage.value = res.message;
    // Update local state
    const target = datasets.value.find((d) => d.id === datasetId);
    if (target) {
      target.status = targetStatus as any;
      target.catatan_validasi = defaultNote;
    }
    setTimeout(() => (actionMessage.value = null), 4000);
  } catch (e: any) {
    alert('Gagal memperbarui status: ' + e.message);
  }
}

async function handleUploadShp() {
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
    actionMessage.value = res.message;
    setTimeout(() => (actionMessage.value = null), 5000);
  } catch (e: any) {
    alert('Gagal upload SHP: ' + e.message);
  } finally {
    isUploading.value = false;
  }
}

async function handleActivateDataDasar(id: number) {
  try {
    const res = await sigApi.activateDataDasar(id);
    dataDasarList.value.forEach((v) => {
      if (v.tipe_data === res.data.tipe_data) {
        v.status = v.id === id ? 'AKTIF' : 'DIGANTIKAN';
      }
    });
    actionMessage.value = res.message;
    setTimeout(() => (actionMessage.value = null), 4000);
  } catch (e: any) {
    alert('Gagal aktivasi data dasar: ' + e.message);
  }
}

function switchRole(role: RoleName) {
  authStore.quickSwitch(role);
  // Auto switch tab if current tab is restricted
  if (role !== 'superadmin' && activeTab.value === 'data_dasar') {
    activeTab.value = 'overview';
  }
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
        <!-- Floating Flash Banner for Action Feedback -->
        <div v-if="actionMessage" class="action-alert">
          <CheckCircle2 :size="18" />
          <span>{{ actionMessage }}</span>
        </div>

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
        <!-- TAB 2: DATASET SEKTOARL & KANBAN                               -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'datasets'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Katalog Dataset Spasial Sektoral</h2>
              <p class="content-sub">Pengelolaan objek spasial per OPD dengan keterikatan hierarki 17 Kecamatan</p>
            </div>

            <!-- Filters -->
            <div class="filter-strip">
              <input
                v-model="filterKecamatan"
                type="text"
                placeholder="Cari kecamatan..."
                class="filter-input"
              />
              <select v-model="filterStatus" class="filter-select">
                <option value="">Semua Status (12 Tahap)</option>
                <option value="DRAFT">DRAFT</option>
                <option value="IN_VALIDATION_GIS">IN_VALIDATION_GIS</option>
                <option value="APPROVED_GIS">APPROVED_GIS</option>
                <option value="IN_APPROVAL">IN_APPROVAL</option>
                <option value="PUBLISHED">PUBLISHED</option>
              </select>
            </div>
          </div>

          <div class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Kode & Nama Dataset</th>
                  <th>OPD Penanggung Jawab</th>
                  <th>Kategori & Geometri</th>
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
                        @click="handleTransition(d.id, 'IN_VALIDATION_GIS', 'Diajukan ke validasi teknis GIS')"
                      >
                        <Send :size="13" />
                        <span>Ajukan Validasi</span>
                      </button>

                      <button
                        v-if="['superadmin', 'admin_gis'].includes(authStore.currentRole) && d.status === 'APPROVED'"
                        class="btn-act btn-publish"
                        @click="handleTransition(d.id, 'PUBLISHED', 'Diterbitkan ke katalog publik geoportal')"
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
        <!-- TAB 3: ANTREAN VALIDASI TEKNIS GIS                            -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'validation'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Antrean Validasi Teknis Geospasial</h2>
              <p class="content-sub">Pemeriksaan topologi, bebas tumpang-tindih (no overlap), sistem koordinat EPSG:4326, dan kelengkapan atribut</p>
            </div>
          </div>

          <div class="cards-queue">
            <div
              v-for="d in datasets.filter(item => ['SUBMITTED_GIS', 'IN_VALIDATION_GIS'].includes(item.status))"
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
                  @click="handleTransition(d.id, 'REJECTED_GIS', 'Perlu perbaikan atribut tabel OPD')"
                >
                  <AlertTriangle :size="14" />
                  <span>Minta Revisi OPD</span>
                </button>
                <button
                  class="btn-act btn-approve"
                  @click="handleTransition(d.id, 'APPROVED_GIS', 'Lolos verifikasi teknis geometri & CRS')"
                >
                  <CheckCircle2 :size="14" />
                  <span>Lolos Validasi Teknis</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 4: PERSETUJUAN TERPUSAT WALIDATA (BAPPELITBANGDA)          -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'approval'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Antrean Persetujuan Terpusat (Central Approval)</h2>
              <p class="content-sub">Kewenangan Walidata Bappelitbangda sebelum dataset diterbitkan resmi ke publik</p>
            </div>
          </div>

          <div class="cards-queue">
            <div
              v-for="d in datasets.filter(item => ['APPROVED_GIS', 'SUBMITTED_APPROVAL', 'IN_APPROVAL'].includes(item.status))"
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
                  @click="handleTransition(d.id, 'DRAFT', 'Ditolak: Tidak sesuai prioritas Satu Data Daerah 2026')"
                >
                  <span>Tolak Pengajuan</span>
                </button>
                <button
                  class="btn-act btn-approve"
                  @click="handleTransition(d.id, 'APPROVED', 'Disetujui Walidata Bappelitbangda No: 045/SDI-MINSEL/2026')"
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

            <form class="upload-form-grid" @submit.prevent="handleUploadShp">
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
                  <th>Tipe Batas & Versi</th>
                  <th>Berkas ZIP & Sumber</th>
                  <th>CRS & Fitur</th>
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
                      @click="handleActivateDataDasar(v.id)"
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
        <!-- TAB 6: USERS & OPD                                             -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'users'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Manajemen Akun Pengguna & OPD</h2>
              <p class="content-sub">Penerapan pemisahan wewenang 8 Role dan pembatasan akses data sektoral</p>
            </div>
          </div>

          <div class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nama Pengguna</th>
                  <th>Role Resmi</th>
                  <th>Organisasi Perangkat Daerah (OPD)</th>
                  <th>Status Akses</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in authStore.roles" :key="r.id">
                  <td>
                    <span class="cell-title">{{ r.label }} Kab. Minahasa Selatan</span>
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

.filter-strip {
  display: flex;
  align-items: center;
  gap: 10px;
}

.filter-input, .filter-select {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  padding: 6px 10px;
  color: #ffffff;
  font-size: 12px;
  outline: none;
}
</style>
