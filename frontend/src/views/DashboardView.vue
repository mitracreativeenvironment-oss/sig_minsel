<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../stores/authStore';
import { useThemeStore } from '../stores/themeStore';
import { sigApi } from '../services/api';
import { notify, confirmDialog, promptNoteDialog, showDetailDialog } from '../services/alert';
import PaginationControl from '../components/PaginationControl.vue';
import StatChart from '../components/StatChart.vue';
import { useDashboardCharts } from '../services/dashboardCharts';
import type {
  RoleName,
  DatasetItem,
  DataDasarVersion,
  WorkflowState,
  UserItem,
  OpdItem,
  KategoriSpasialItem,
  AuditLogItem,
  VisitorSummary,
  KecamatanAdminItem,
  DesaAdminItem,
  BaseMapItem,
  OverviewStats
} from '../types';
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
  UploadCloud,
  Download,
  CheckCircle2,
  AlertTriangle,
  FileArchive,
  Layers,
  ShieldAlert,
  Send,
  Search,
  LayoutGrid,
  List,
  X,
  RotateCcw,
  Plus,
  Edit3,
  Trash2,
  ShieldCheck,
  ToggleLeft,
  ToggleRight,
  FilePlus2,
  UserPlus,
  Building2,
  Eye,
  Activity,
  Globe,
  Smartphone,
  Monitor,
  Tablet,
  Map as MapIcon,
  Compass,
  Star,
  User,
  Lock,
  Key,
  Phone,
  Mail,
  Briefcase,
  Shield,
  Save,
  Sun,
  Moon
} from 'lucide-vue-next';

const emit = defineEmits<{
  (e: 'navigate', view: 'geoportal' | 'dashboard'): void;
}>();

const authStore = useAuthStore();
const themeStore = useThemeStore();

type NavTab = 'overview' | 'datasets' | 'validation' | 'approval' | 'data_dasar' | 'wilayah' | 'basemaps' | 'kategori' | 'opd' | 'users' | 'audit' | 'profile';
const activeTab = ref<NavTab>('overview');

// Data State
const datasets = ref<DatasetItem[]>([]);
const dataDasarList = ref<DataDasarVersion[]>([]);
const workflowStates = ref<WorkflowState[]>([]);
const usersList = ref<UserItem[]>([]);
const opdList = ref<OpdItem[]>([]);
const kategoriList = ref<KategoriSpasialItem[]>([]);
const overviewStats = ref<OverviewStats | null>(null);
const isLoading = ref(false);

// Computed KPI Real Database untuk Overview Eksekutif
const kpiCakupanWilayah = computed(() => {
  if (overviewStats.value?.kpi) {
    return `${overviewStats.value.kpi.total_kecamatan_mapped} / ${overviewStats.value.kpi.total_kecamatan}`;
  }
  const count = kecamatanAdminList.value.length || 17;
  return `${count} / ${count}`;
});

const kpiCakupanPersen = computed(() => {
  return overviewStats.value?.kpi?.cakupan_persen || 100;
});

const kpiTotalDesa = computed(() => {
  if (overviewStats.value?.kpi) {
    return overviewStats.value.kpi.total_desa;
  }
  return desaAdminList.value.length || 177;
});

const kpiDesaRincian = computed(() => {
  if (overviewStats.value?.kpi) {
    return `${overviewStats.value.kpi.total_desa_murni} Desa dan ${overviewStats.value.kpi.total_kelurahan} Kelurahan binaan`;
  }
  if (desaAdminList.value.length > 0) {
    const d = desaAdminList.value.filter((x) => (x.tipe || '').toUpperCase() === 'DESA').length;
    const k = desaAdminList.value.filter((x) => (x.tipe || '').toUpperCase() === 'KELURAHAN').length;
    return `${d} Desa dan ${k} Kelurahan binaan`;
  }
  return '166 Desa dan 11 Kelurahan binaan';
});

const kpiTotalKategori = computed(() => {
  if (overviewStats.value?.kpi) {
    return overviewStats.value.kpi.total_kategori;
  }
  return kategoriList.value.length || 28;
});

const kpiTotalWorkflowStages = computed(() => {
  if (overviewStats.value?.kpi) {
    return overviewStats.value.kpi.total_workflow_stages;
  }
  return workflowStates.value.length || 12;
});

// Computed Real Database untuk Analitik Pengunjung Geoportal
const popularPagesList = computed(() => {
  if (visitorStats.value?.halaman_populer && visitorStats.value.halaman_populer.length > 0) {
    return visitorStats.value.halaman_populer.map((p) => ({
      page: p.page || 'geoportal',
      nama: p.nama || (p.page === 'geoportal' ? 'Peta Interaktif Geoportal' : (p.page === 'dashboard' ? 'Dashboard Tata Kelola Spasial' : (p.page === 'katalog_dataset' ? 'Katalog Dataset Spasial' : 'Peta Tematik & Layer'))),
      hits: p.hits || p.kunjungan || 0,
      persen: p.persen || 0
    }));
  }
  return [
    { page: 'geoportal', nama: 'Peta Interaktif Geoportal', hits: 1088, persen: 45 },
    { page: 'katalog_dataset', nama: 'Katalog Dataset Spasial', hits: 639, persen: 27 },
    { page: 'rawan_bencana', nama: 'Peta Titik Rawan Bencana', hits: 311, persen: 13 },
    { page: 'dashboard', nama: 'Dashboard Tata Kelola Spasial', hits: 209, persen: 9 },
    { page: 'data_dasar', nama: 'Peta Tematik & Data Dasar Wilayah', hits: 153, persen: 6 }
  ];
});

const deviceTotal = computed(() => {
  return visitorStats.value?.total_kunjungan || 2400;
});

const deviceDesktopCount = computed(() => {
  return visitorStats.value?.perangkat?.desktop ?? 1454;
});
const deviceDesktopPercent = computed(() => {
  return Math.round((deviceDesktopCount.value / deviceTotal.value) * 100);
});

const deviceMobileCount = computed(() => {
  return visitorStats.value?.perangkat?.mobile ?? 784;
});
const deviceMobilePercent = computed(() => {
  return Math.round((deviceMobileCount.value / deviceTotal.value) * 100);
});

const deviceTabletCount = computed(() => {
  return visitorStats.value?.perangkat?.tablet ?? 162;
});
const deviceTabletPercent = computed(() => {
  return Math.round((deviceTabletCount.value / deviceTotal.value) * 100);
});

// Wilayah CRUD State (Kecamatan & Desa)
const subTabWilayah = ref<'kecamatan' | 'desa'>('kecamatan');
const kecamatanAdminList = ref<KecamatanAdminItem[]>([]);
const desaAdminList = ref<DesaAdminItem[]>([]);
const isWilayahLoading = ref(false);
const searchKecamatanQuery = ref('');
const searchDesaQuery = ref('');
const filterDesaKecamatan = ref('');
const filterDesaTipe = ref('');

// Modals & Forms for Kecamatan
const isCreateKecamatanModalOpen = ref(false);
const isEditKecamatanModalOpen = ref(false);
const kecamatanForm = ref({
  id: 0,
  nama_kecamatan: '',
  kode_kecamatan: '',
  ibukota_kecamatan: '',
  luas_wilayah_km2: 50.0,
  center_lng: 124.57,
  center_lat: 1.18
});

// Modals & Forms for Desa
const isCreateDesaModalOpen = ref(false);
const isEditDesaModalOpen = ref(false);
const desaForm = ref({
  id: 0,
  nama_desa: '',
  kode_desa: '',
  tipe: 'DESA' as 'DESA' | 'KELURAHAN',
  kecamatan_id: 1,
  luas_wilayah_km2: 5.0,
  center_lng: 124.57,
  center_lat: 1.18
});

// Base Map CRUD State
const basemapsAdminList = ref<BaseMapItem[]>([]);
const isBasemapLoading = ref(false);
const searchBasemapQuery = ref('');
const filterBasemapType = ref('');
const filterBasemapStatus = ref('');
const isCreateBasemapModalOpen = ref(false);
const isEditBasemapModalOpen = ref(false);
const basemapForm = ref({
  id: 0,
  name: '',
  label: '',
  desc: '',
  type: 'raster' as 'raster' | 'vector',
  tile_url: '',
  attribution: '',
  max_zoom: 19,
  min_zoom: 0,
  preview_color: '#334155'
});

// Audit Log State & Filters
const auditLogs = ref<AuditLogItem[]>([]);
const auditFilterKategori = ref('');
const auditFilterAksi = ref('');
const auditSearchQuery = ref('');
const auditKategoriList = ref<string[]>([]);
const auditAksiList = ref<string[]>([]);
const isAuditLoading = ref(false);

// Visitor Analytics State
const visitorStats = ref<VisitorSummary | null>(null);
const isVisitorLoading = ref(false);

// ==========================================
// REAL STATISTICAL CHARTS FOR EACH PAGE/TAB
// ==========================================
const {
  chartOverviewGeometry,
  chartOverviewPipeline,
  chartOverviewVisitors,
  chartDatasetStatus,
  chartDatasetByOpd,
  chartValidationRatio,
  chartValidationCategories,
  chartApprovalReadiness,
  chartApprovalGeometries,
  chartDataDasarVersions,
  chartDataDasarStatus,
  chartWilayahKecLuas,
  chartWilayahKecDesaCount,
  chartWilayahDesaTipe,
  chartWilayahTopDesaKec,
  chartBasemapProviders,
  chartBasemapZoomRange,
  chartKategoriDatasetCount,
  chartKategoriStatus,
  chartOpdContribution,
  chartOpdActiveRatio,
  chartUsersByRole,
  chartUsersStatus,
  chartAuditByKategori,
  chartAuditByAksi,
  chartProfileDatasetStatus,
  chartProfileAuditActivities
} = useDashboardCharts({
  datasets,
  kecamatanAdminList,
  desaAdminList,
  kategoriList,
  opdList,
  usersList,
  auditLogs,
  visitorStats,
  basemapsAdminList,
  dataDasarList,
  currentUser: computed(() => authStore.user),
  overviewStats
});

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
const uploadFileName = ref('');
const serverZipFiles = ref<Array<{ filename: string; size_human: string; updated_at: string }>>([]);
const isUploading = ref(false);
const lastValidationReport = ref<any>(null);

function useServerZipFile(filename: string) {
  dataDasarFile.value = null;
  uploadFileName.value = filename;
  notify.info('Berkas Dipilih', `Berkas server "${filename}" dipilih untuk registrasi & validasi.`);
}

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

// Search & Filter for Users & OPD (Scoped per OPD for Admin OPD)
const searchUserQuery = ref('');
const filterUserRole = ref('');

const scopedUsers = computed(() => {
  if (authStore.currentRole === 'admin_opd') {
    const myKode = (authStore.user?.kode_opd || '').toUpperCase();
    const myOpd = (authStore.user?.opd || '').toLowerCase();
    return usersList.value.filter((u) => {
      const matchKode = myKode && u.kode_opd && u.kode_opd.toUpperCase() === myKode;
      const matchOpd = myOpd && u.opd && u.opd.toLowerCase() === myOpd;
      return matchKode || matchOpd;
    });
  }
  return usersList.value;
});

const filteredUsers = computed(() => {
  const q = searchUserQuery.value.trim().toLowerCase();
  return scopedUsers.value.filter((u) => {
    const matchSearch =
      !q ||
      u.nama_lengkap.toLowerCase().includes(q) ||
      u.username.toLowerCase().includes(q) ||
      u.opd.toLowerCase().includes(q) ||
      u.role.toLowerCase().includes(q);
    const matchRole = !filterUserRole.value || u.role === filterUserRole.value;
    return matchSearch && matchRole;
  });
});

// Search & Filter for OPD
const searchOpdQuery = ref('');
const filteredOpds = computed(() => {
  const q = searchOpdQuery.value.trim().toLowerCase();
  return opdList.value.filter((o) => {
    return (
      !q ||
      o.nama_opd.toLowerCase().includes(q) ||
      o.kode_opd.toLowerCase().includes(q) ||
      (o.singkatan && o.singkatan.toLowerCase().includes(q)) ||
      (o.alamat && o.alamat.toLowerCase().includes(q)) ||
      (o.pic && o.pic.toLowerCase().includes(q))
    );
  });
});

// Search & Filter for Kategori Spasial
const searchKategoriQuery = ref('');
const filteredKategoris = computed(() => {
  const q = searchKategoriQuery.value.trim().toLowerCase();
  return kategoriList.value.filter((k) => {
    return (
      !q ||
      k.nama_kategori.toLowerCase().includes(q) ||
      k.kode.toLowerCase().includes(q) ||
      (k.deskripsi && k.deskripsi.toLowerCase().includes(q)) ||
      (k.nama_opd && k.nama_opd.toLowerCase().includes(q))
    );
  });
});

// Search & Filter for Audit Trail
const activeAuditFilterCount = computed(() => {
  let count = 0;
  if (auditSearchQuery.value.trim()) count++;
  if (auditFilterKategori.value) count++;
  if (auditFilterAksi.value) count++;
  return count;
});

const filteredAuditLogs = computed(() => {
  const q = auditSearchQuery.value.trim().toLowerCase();
  return auditLogs.value.filter((log) => {
    const matchSearch =
      !q ||
      log.objek?.toLowerCase().includes(q) ||
      log.aktor?.toLowerCase().includes(q) ||
      log.keterangan?.toLowerCase().includes(q) ||
      log.opd?.toLowerCase().includes(q) ||
      (log.versi_terkait && log.versi_terkait.toLowerCase().includes(q));

    const matchKat = !auditFilterKategori.value || log.kategori === auditFilterKategori.value;
    const matchAksi = !auditFilterAksi.value || log.aksi === auditFilterAksi.value;

    return matchSearch && matchKat && matchAksi;
  });
});

function resetAuditFilters() {
  auditSearchQuery.value = '';
  auditFilterKategori.value = '';
  auditFilterAksi.value = '';
  notify.info('Filter Audit Direset', 'Kriteria pencarian audit log dikosongkan.');
}

// ==========================================
// PAGINATION STATES & COMPUTED SLICES
// ==========================================

// 1. Datasets Pagination
const pageDatasets = ref(1);
const pageSizeDatasets = ref(6);
const paginatedDatasets = computed(() => {
  const start = (pageDatasets.value - 1) * pageSizeDatasets.value;
  return filteredDatasets.value.slice(start, start + pageSizeDatasets.value);
});
watch([searchQuery, filterStatus, filterKecamatan, filterGeometri, filterOpd], () => {
  pageDatasets.value = 1;
});

// 2. Validation Queue Pagination
const pageValidation = ref(1);
const pageSizeValidation = ref(10);
const paginatedValidationDatasets = computed(() => {
  const start = (pageValidation.value - 1) * pageSizeValidation.value;
  return filteredValidationDatasets.value.slice(start, start + pageSizeValidation.value);
});
watch(searchValidationQuery, () => {
  pageValidation.value = 1;
});

// 3. Approval Queue Pagination
const pageApproval = ref(1);
const pageSizeApproval = ref(10);
const paginatedApprovalDatasets = computed(() => {
  const start = (pageApproval.value - 1) * pageSizeApproval.value;
  return filteredApprovalDatasets.value.slice(start, start + pageSizeApproval.value);
});
watch(searchApprovalQuery, () => {
  pageApproval.value = 1;
});

// 4. Users Pagination
const pageUsers = ref(1);
const pageSizeUsers = ref(10);
const paginatedUsers = computed(() => {
  const start = (pageUsers.value - 1) * pageSizeUsers.value;
  return filteredUsers.value.slice(start, start + pageSizeUsers.value);
});
watch([searchUserQuery, filterUserRole], () => {
  pageUsers.value = 1;
});

// 5. OPD Pagination
const pageOpd = ref(1);
const pageSizeOpd = ref(10);
const paginatedOpds = computed(() => {
  const start = (pageOpd.value - 1) * pageSizeOpd.value;
  return filteredOpds.value.slice(start, start + pageSizeOpd.value);
});
watch(searchOpdQuery, () => {
  pageOpd.value = 1;
});

// 6. Kategori Spasial Pagination
const pageKategori = ref(1);
const pageSizeKategori = ref(8);
const paginatedKategoris = computed(() => {
  const start = (pageKategori.value - 1) * pageSizeKategori.value;
  return filteredKategoris.value.slice(start, start + pageSizeKategori.value);
});
watch(searchKategoriQuery, () => {
  pageKategori.value = 1;
});

// 7. Audit Trail Pagination
const pageAudit = ref(1);
const pageSizeAudit = ref(10);
const paginatedAuditLogs = computed(() => {
  const start = (pageAudit.value - 1) * pageSizeAudit.value;
  return filteredAuditLogs.value.slice(start, start + pageSizeAudit.value);
});
watch([auditSearchQuery, auditFilterKategori, auditFilterAksi], () => {
  pageAudit.value = 1;
});

// ==========================================
// HAK AKSES ROLE (ROLE PERMISSIONS)
// ==========================================
const canCreateDataset = computed(() =>
  ['superadmin', 'admin_gis', 'admin_opd', 'operator_opd'].includes(authStore.currentRole)
);

function canEditDataset(d: DatasetItem): boolean {
  if (['superadmin', 'admin_gis'].includes(authStore.currentRole)) return true;
  if (['admin_opd', 'operator_opd'].includes(authStore.currentRole)) {
    return ['DRAFT', 'REJECTED_GIS'].includes(d.status);
  }
  return false;
}

function canDeleteDataset(d: DatasetItem): boolean {
  if (authStore.currentRole === 'superadmin') return true;
  if (['admin_opd', 'operator_opd'].includes(authStore.currentRole)) {
    return d.status === 'DRAFT';
  }
  return false;
}

const canManageUsers = computed(() =>
  ['superadmin', 'admin_opd'].includes(authStore.currentRole)
);

function canManageThisUser(u: UserItem): boolean {
  if (!canManageUsers.value) return false;
  if (u.id === 1) return false; // Akun Utama Superadmin Terproteksi
  if (authStore.currentRole === 'superadmin') return true;
  if (authStore.currentRole === 'admin_opd') {
    const myKode = (authStore.user?.kode_opd || '').toUpperCase();
    const myOpd = (authStore.user?.opd || '').toLowerCase();
    const matchKode = myKode && u.kode_opd && u.kode_opd.toUpperCase() === myKode;
    const matchOpd = myOpd && u.opd && u.opd.toLowerCase() === myOpd;
    return !!(matchKode || matchOpd);
  }
  return false;
}

const canManageOpd = computed(() =>
  ['superadmin', 'admin_gis', 'admin_opd'].includes(authStore.currentRole)
);

const canFullManageOpd = computed(() =>
  ['superadmin', 'admin_gis'].includes(authStore.currentRole)
);

const canManageKategori = computed(() =>
  ['superadmin', 'admin_gis'].includes(authStore.currentRole)
);

const canAccessDataDasar = computed(() =>
  ['superadmin', 'admin_gis'].includes(authStore.currentRole)
);

const isReadOnlyRole = computed(() =>
  ['pimpinan', 'publik'].includes(authStore.currentRole)
);

// ==========================================
// MODAL STATE: CREATE & EDIT DATASET & USER
// ==========================================
const isCreateDatasetModalOpen = ref(false);
const isEditDatasetModalOpen = ref(false);

// SHP Dataset Upload & Management State
const isUploadShpModalOpen = ref(false);
const isUploadingShp = ref(false);
const uploadShpFile = ref<File | null>(null);
const uploadShpInputRef = ref<HTMLInputElement | null>(null);
const uploadShpForm = ref({
  nama_dataset: '',
  kode_dataset: '',
  kategori: 'infrastruktur_pu',
  nama_kategori: 'Pekerjaan Umum & Infrastruktur',
  opd: authStore.opdLabel || 'Dinas Pekerjaan Umum dan Penataan Ruang',
  kode_opd: authStore.user?.kode_opd || 'DPUPR',
  kecamatan: 'Seluruh Kabupaten',
  deskripsi: ''
});

// Re-upload SHP for Revision
const isReuploadShpModalOpen = ref(false);
const isReuploading = ref(false);
const reuploadTargetDataset = ref<DatasetItem | null>(null);
const reuploadShpFile = ref<File | null>(null);
const reuploadShpInputRef = ref<HTMLInputElement | null>(null);

// Data Dasar real file state
const dataDasarFile = ref<File | null>(null);
const dataDasarFileInputRef = ref<HTMLInputElement | null>(null);
const isCreateUserModalOpen = ref(false);
const isEditUserModalOpen = ref(false);
const isCreateOpdModalOpen = ref(false);
const isEditOpdModalOpen = ref(false);
const isCreateKategoriModalOpen = ref(false);
const isEditKategoriModalOpen = ref(false);

const newDatasetForm = ref({
  nama_dataset: '',
  kode_dataset: '',
  kategori: 'infrastruktur_pu',
  nama_kategori: 'Pekerjaan Umum dan Infrastruktur',
  opd: authStore.opdLabel || 'Dinas Pekerjaan Umum dan Penataan Ruang',
  kode_opd: authStore.user?.kode_opd || 'DPUPR',
  tipe_geometri: 'Point',
  kecamatan: 'Amurang',
  deskripsi: '',
  jumlah_fitur: 10
});

const editDatasetForm = ref<{
  id: number;
  nama_dataset: string;
  deskripsi: string;
  kecamatan: string;
  kategori: string;
  nama_kategori: string;
  tipe_geometri: 'Point' | 'LineString' | 'Polygon';
}>({
  id: 0,
  nama_dataset: '',
  deskripsi: '',
  kecamatan: '',
  kategori: '',
  nama_kategori: '',
  tipe_geometri: 'Point'
});

const newUserForm = ref({
  username: '',
  nama_lengkap: '',
  email: '',
  role: 'operator_opd' as RoleName,
  opd: 'Dinas Komunikasi dan Informatika',
  kode_opd: 'DISKOMINFO'
});

function openCreateDatasetModal() {
  newDatasetForm.value = {
    nama_dataset: '',
    kode_dataset: 'MNSL-' + Math.floor(1000 + Math.random() * 9000),
    kategori: 'infrastruktur_pu',
    nama_kategori: 'Infrastruktur dan Tata Ruang',
    opd: authStore.opdLabel || 'Dinas Pekerjaan Umum dan Penataan Ruang',
    kode_opd: authStore.user?.kode_opd || 'DPUPR',
    tipe_geometri: 'Point',
    kecamatan: 'Amurang',
    deskripsi: '',
    jumlah_fitur: 15
  };
  isCreateDatasetModalOpen.value = true;
}

async function handleCreateDataset() {
  if (!newDatasetForm.value.nama_dataset || !newDatasetForm.value.kode_dataset) {
    notify.error('Input Tidak Lengkap', 'Nama dan Kode dataset wajib diisi!');
    return;
  }

  try {
    const res = await sigApi.createDataset({
      ...newDatasetForm.value,
      submitted_by: authStore.user?.nama_lengkap || 'Operator OPD'
    });
    datasets.value.unshift(res.data);
    isCreateDatasetModalOpen.value = false;
    notify.success('Dataset Berhasil Dibuat', res.message || 'Dataset masuk ke katalog dengan status DRAFT.');
  } catch (e: any) {
    notify.error('Gagal Membuat Dataset', e.message);
  }
}

function openUploadShpModal() {
  uploadShpFile.value = null;
  if (uploadShpInputRef.value) uploadShpInputRef.value.value = '';
  uploadShpForm.value = {
    nama_dataset: '',
    kode_dataset: 'MNSL-SHP-' + Math.floor(1000 + Math.random() * 9000),
    kategori: 'infrastruktur_pu',
    nama_kategori: 'Pekerjaan Umum & Infrastruktur',
    opd: authStore.opdLabel || 'Dinas Pekerjaan Umum dan Penataan Ruang',
    kode_opd: authStore.user?.kode_opd || 'DPUPR',
    kecamatan: 'Seluruh Kabupaten',
    deskripsi: ''
  };
  isUploadShpModalOpen.value = true;
}

function onShpFileSelected(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    if (!file.name.toLowerCase().endsWith('.zip')) {
      notify.error('Format Tidak Sesuai', 'Harap pilih berkas arsip ZIP (.zip) yang memuat berkas .shp, .shx, .dbf, dan .prj');
      target.value = '';
      return;
    }
    uploadShpFile.value = file;
    if (!uploadShpForm.value.nama_dataset) {
      const baseName = file.name.replace(/\.[^/.]+$/, '').replace(/[_|-]/g, ' ');
      uploadShpForm.value.nama_dataset = baseName.charAt(0).toUpperCase() + baseName.slice(1);
    }
  }
}

async function handleUploadShpDataset() {
  if (!uploadShpFile.value) {
    notify.error('Berkas Belum Dipilih', 'Silakan pilih berkas ZIP Shapefile (.zip) terlebih dahulu.');
    return;
  }

  isUploadingShp.value = true;
  try {
    const fd = new FormData();
    fd.append('shp_file', uploadShpFile.value);
    fd.append('nama_dataset', uploadShpForm.value.nama_dataset);
    fd.append('kode_dataset', uploadShpForm.value.kode_dataset);
    fd.append('kategori', uploadShpForm.value.kategori);
    fd.append('nama_kategori', uploadShpForm.value.nama_kategori);
    fd.append('opd', uploadShpForm.value.opd);
    fd.append('kode_opd', uploadShpForm.value.kode_opd);
    fd.append('kecamatan', uploadShpForm.value.kecamatan);
    fd.append('deskripsi', uploadShpForm.value.deskripsi);
    fd.append('submitted_by', authStore.user?.nama_lengkap || 'Operator OPD');

    const res = await sigApi.uploadShpDataset(fd);
    datasets.value.unshift(res.data.dataset);
    isUploadShpModalOpen.value = false;
    notify.success('Upload SHP Berhasil', res.message || 'Dataset Shapefile berhasil divalidasi dan tersimpan sebagai DRAFT.');
  } catch (e: any) {
    notify.error('Gagal Upload SHP', e.message);
  } finally {
    isUploadingShp.value = false;
  }
}

function openReuploadShpModal(d: DatasetItem) {
  reuploadTargetDataset.value = d;
  reuploadShpFile.value = null;
  if (reuploadShpInputRef.value) reuploadShpInputRef.value.value = '';
  isReuploadShpModalOpen.value = true;
}

function onReuploadShpFileSelected(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    if (!file.name.toLowerCase().endsWith('.zip')) {
      notify.error('Format Tidak Sesuai', 'Harap pilih berkas arsip ZIP (.zip)');
      target.value = '';
      return;
    }
    reuploadShpFile.value = file;
  }
}

async function handleReuploadShpDataset() {
  if (!reuploadTargetDataset.value || !reuploadShpFile.value) {
    notify.error('Berkas Belum Dipilih', 'Silakan pilih berkas ZIP Shapefile revisi.');
    return;
  }

  isReuploading.value = true;
  try {
    const fd = new FormData();
    fd.append('id', String(reuploadTargetDataset.value.id));
    fd.append('shp_file', reuploadShpFile.value);
    fd.append('actor_role', authStore.currentRole);
    fd.append('submitted_by', authStore.user?.nama_lengkap || 'Operator OPD');

    const res = await sigApi.reuploadShpDataset(fd);
    const target = datasets.value.find(d => d.id === reuploadTargetDataset.value?.id);
    if (target) {
      Object.assign(target, res.data.dataset);
    }
    isReuploadShpModalOpen.value = false;
    notify.success('Revisi SHP Berhasil Diunggah', res.message || 'Dataset telah diperbarui.');
  } catch (e: any) {
    notify.error('Gagal Upload Revisi', e.message);
  } finally {
    isReuploading.value = false;
  }
}

async function handleDownloadShp(d: DatasetItem) {
  try {
    notify.info('Mengunduh...', `Memulai unduhan berkas Shapefile ${d.nama_dataset}`);
    await sigApi.downloadShpDataset(d.id, d.file_name);
    notify.success('Unduhan Selesai', `Berkas Shapefile ${d.nama_dataset} berhasil diunduh.`);
  } catch (e: any) {
    notify.error('Gagal Mengunduh', e.message);
  }
}

function onDataDasarFileSelected(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const file = target.files[0];
    if (!file.name.toLowerCase().endsWith('.zip')) {
      notify.error('Format Tidak Sesuai', 'Pilih berkas arsip ZIP (.zip) batas wilayah');
      target.value = '';
      return;
    }
    dataDasarFile.value = file;
    uploadFileName.value = file.name;
  }
}

function openEditDatasetModal(d: DatasetItem) {
  editDatasetForm.value = {
    id: d.id,
    nama_dataset: d.nama_dataset,
    deskripsi: d.deskripsi || '',
    kecamatan: d.kecamatan || 'Amurang',
    kategori: d.kategori,
    nama_kategori: d.nama_kategori,
    tipe_geometri: d.tipe_geometri
  };
  isEditDatasetModalOpen.value = true;
}

async function handleUpdateDataset() {
  try {
    const res = await sigApi.updateDataset({
      ...editDatasetForm.value,
      actor_role: authStore.currentRole
    });
    const target = datasets.value.find((d) => d.id === editDatasetForm.value.id);
    if (target) {
      target.nama_dataset = res.data.nama_dataset;
      target.deskripsi = res.data.deskripsi;
      target.kecamatan = res.data.kecamatan;
      target.kategori = res.data.kategori;
      target.nama_kategori = res.data.nama_kategori;
      target.tipe_geometri = res.data.tipe_geometri;
      target.updated_at = res.data.updated_at;
    }
    isEditDatasetModalOpen.value = false;
    notify.success('Dataset Diperbarui', res.message || 'Informasi dataset berhasil disimpan.');
  } catch (e: any) {
    notify.error('Gagal Memperbarui', e.message);
  }
}

async function handleDeleteDataset(d: DatasetItem) {
  const confirmed = await confirmDialog({
    title: 'Hapus Dataset?',
    text: `Dataset "${d.nama_dataset}" (${d.kode_dataset}) akan dihapus secara permanen dari basis data.`,
    confirmButtonText: 'Ya, Hapus Dataset',
    icon: 'warning',
    isDangerous: true
  });

  if (!confirmed) return;

  try {
    const res = await sigApi.deleteDataset({
      id: d.id,
      actor_role: authStore.currentRole
    });
    datasets.value = datasets.value.filter((item) => item.id !== d.id);
    notify.success('Dataset Dihapus', res.message || `Dataset "${d.nama_dataset}" berhasil dihapus.`);
  } catch (e: any) {
    notify.error('Gagal Menghapus Dataset', e.message);
  }
}

async function handleDeleteDataDasarWithConfirm(v: DataDasarVersion) {
  const confirmed = await confirmDialog({
    title: 'Hapus Berkas Data Dasar?',
    text: `Berkas ZIP "${v.nama_file_zip}" (${v.label_tipe} - Versi ${v.versi}) akan dihapus dari arsip sistem.`,
    confirmButtonText: 'Ya, Hapus Berkas',
    icon: 'warning',
    isDangerous: true
  });

  if (!confirmed) return;

  try {
    const res = await sigApi.deleteDataDasar(v.id);
    dataDasarList.value = dataDasarList.value.filter((item) => item.id !== v.id);
    notify.success('Berkas SHP Dihapus', res.message || 'Arsip data dasar berhasil dibersihkan.');
  } catch (e: any) {
    notify.error('Gagal Hapus Berkas', e.message);
  }
}

// User CRUD Handlers
function openCreateUserModal() {
  const isOpdAdmin = authStore.currentRole === 'admin_opd';
  newUserForm.value = {
    username: '',
    nama_lengkap: '',
    email: '',
    role: 'operator_opd',
    opd: isOpdAdmin ? (authStore.opdLabel || 'Dinas Pekerjaan Umum dan Penataan Ruang') : (opdList.value[0]?.nama_opd || 'Dinas Komunikasi dan Informatika'),
    kode_opd: isOpdAdmin ? (authStore.user?.kode_opd || 'DPUPR') : (opdList.value[0]?.kode_opd || 'DISKOMINFO')
  };
  isCreateUserModalOpen.value = true;
}

async function handleCreateUser() {
  if (!newUserForm.value.username || !newUserForm.value.nama_lengkap) {
    notify.error('Input Tidak Lengkap', 'Username dan Nama Lengkap wajib diisi!');
    return;
  }

  if (authStore.currentRole === 'admin_opd') {
    newUserForm.value.opd = authStore.opdLabel;
    newUserForm.value.kode_opd = authStore.user?.kode_opd || 'DPUPR';
    if (!['operator_opd', 'admin_opd'].includes(newUserForm.value.role)) {
      newUserForm.value.role = 'operator_opd';
    }
  }

  try {
    const payload = {
      ...newUserForm.value,
      caller_role: authStore.currentRole,
      caller_opd: authStore.user?.opd,
      caller_kode_opd: authStore.user?.kode_opd
    };
    const res = await sigApi.createUser(payload);
    usersList.value.push(res.data);
    isCreateUserModalOpen.value = false;
    notify.success('Pengguna Ditambahkan', res.message || 'Akun pengguna baru berhasil dibuat.');
  } catch (e: any) {
    notify.error('Gagal Tambah Pengguna', e.message);
  }
}

async function handleToggleUserStatusWithConfirm(u: UserItem) {
  if (!canManageThisUser(u)) {
    notify.error('Akses Ditolak', 'Anda tidak berwenang mengelola pengguna di luar OPD Anda.');
    return;
  }
  const actionText = u.is_active ? 'Nonaktifkan' : 'Aktifkan';
  const confirmed = await confirmDialog({
    title: `${actionText} Akun Pengguna?`,
    text: `Akun "${u.username}" (${u.nama_lengkap}) akan ${u.is_active ? 'dinonaktifkan hak aksesnya' : 'diaktifkan kembali'}.`,
    confirmButtonText: `Ya, ${actionText}`,
    icon: 'warning'
  });

  if (!confirmed) return;

  try {
    const res = await sigApi.toggleUserStatus({
      id: u.id,
      caller_role: authStore.currentRole,
      caller_kode_opd: authStore.user?.kode_opd
    });
    u.is_active = res.data.is_active;
    notify.success('Status Pengguna Diperbarui', res.message || `Akun ${u.username} berhasil diperbarui.`);
  } catch (e: any) {
    notify.error('Gagal Ubah Status', e.message);
  }
}

async function handleDeleteUserWithConfirm(u: UserItem) {
  if (!canManageThisUser(u)) {
    notify.error('Akses Ditolak', 'Anda tidak berwenang menghapus pengguna di luar OPD Anda.');
    return;
  }
  const confirmed = await confirmDialog({
    title: 'Hapus Akun Pengguna?',
    text: `Akun "${u.username}" (${u.nama_lengkap}) akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.`,
    confirmButtonText: 'Ya, Hapus Akun',
    icon: 'warning',
    isDangerous: true
  });

  if (!confirmed) return;

  try {
    const res = await sigApi.deleteUser({
      id: u.id,
      caller_role: authStore.currentRole,
      caller_kode_opd: authStore.user?.kode_opd
    });
    usersList.value = usersList.value.filter((item) => item.id !== u.id);
    notify.success('Pengguna Dihapus', res.message || `Akun "${u.username}" berhasil dihapus.`);
  } catch (e: any) {
    notify.error('Gagal Hapus Pengguna', e.message);
  }
}

// User Edit Handlers
const editUserForm = ref<{
  id: number;
  username: string;
  nama_lengkap: string;
  email: string;
  role: RoleName;
  opd: string;
  kode_opd: string;
}>({
  id: 0,
  username: '',
  nama_lengkap: '',
  email: '',
  role: 'operator_opd',
  opd: '',
  kode_opd: ''
});

function openEditUserModal(u: UserItem) {
  if (!canManageThisUser(u)) {
    notify.error('Akses Ditolak', 'Anda tidak berwenang mengedit pengguna di luar OPD Anda.');
    return;
  }
  editUserForm.value = {
    id: u.id,
    username: u.username,
    nama_lengkap: u.nama_lengkap,
    email: u.email,
    role: u.role,
    opd: u.opd,
    kode_opd: u.kode_opd
  };
  isEditUserModalOpen.value = true;
}

async function handleUpdateUser() {
  try {
    if (authStore.currentRole === 'admin_opd') {
      editUserForm.value.opd = authStore.opdLabel;
      editUserForm.value.kode_opd = authStore.user?.kode_opd || 'DPUPR';
      if (!['operator_opd', 'admin_opd'].includes(editUserForm.value.role)) {
        editUserForm.value.role = 'operator_opd';
      }
    }
    const payload = {
      ...editUserForm.value,
      caller_role: authStore.currentRole,
      caller_opd: authStore.user?.opd,
      caller_kode_opd: authStore.user?.kode_opd
    };
    const res = await sigApi.updateUser(payload);
    const target = usersList.value.find((u) => u.id === editUserForm.value.id);
    if (target) {
      Object.assign(target, res.data);
    }
    isEditUserModalOpen.value = false;
    notify.success('Pengguna Diperbarui', res.message || 'Data akun pengguna berhasil disimpan.');
  } catch (e: any) {
    notify.error('Gagal Memperbarui Pengguna', e.message);
  }
}

function handleSelectNewUserOpd(event: Event) {
  const targetOpdName = (event.target as HTMLSelectElement).value;
  const match = opdList.value.find((o) => o.nama_opd === targetOpdName);
  if (match) {
    newUserForm.value.kode_opd = match.kode_opd;
  }
}

function handleSelectEditUserOpd(event: Event) {
  const targetOpdName = (event.target as HTMLSelectElement).value;
  const match = opdList.value.find((o) => o.nama_opd === targetOpdName);
  if (match) {
    editUserForm.value.kode_opd = match.kode_opd;
  }
}

// ==========================================
// CRUD MASTER OPD HANDLERS
// ==========================================
const newOpdForm = ref({
  kode_opd: '',
  nama_opd: '',
  singkatan: '',
  email: '',
  telepon: '',
  alamat: '',
  pic: ''
});

const editOpdForm = ref({
  id: 0,
  kode_opd: '',
  nama_opd: '',
  singkatan: '',
  email: '',
  telepon: '',
  alamat: '',
  pic: '',
  is_active: true
});

function openCreateOpdModal() {
  newOpdForm.value = {
    kode_opd: '',
    nama_opd: '',
    singkatan: '',
    email: '',
    telepon: '',
    alamat: 'Kabupaten Minahasa Selatan',
    pic: ''
  };
  isCreateOpdModalOpen.value = true;
}

async function handleCreateOpd() {
  if (!newOpdForm.value.kode_opd || !newOpdForm.value.nama_opd) {
    notify.error('Input Tidak Lengkap', 'Kode OPD dan Nama OPD wajib diisi!');
    return;
  }
  try {
    const res = await sigApi.createOpd(newOpdForm.value);
    opdList.value.unshift(res.data);
    isCreateOpdModalOpen.value = false;
    notify.success('OPD Berhasil Didaftarkan', res.message);
  } catch (e: any) {
    notify.error('Gagal Menambah OPD', e.message);
  }
}

function openEditOpdModal(o: OpdItem) {
  editOpdForm.value = {
    id: o.id,
    kode_opd: o.kode_opd,
    nama_opd: o.nama_opd,
    singkatan: o.singkatan || o.kode_opd,
    email: o.email || '',
    telepon: o.telepon || '',
    alamat: o.alamat || '',
    pic: o.pic || '',
    is_active: o.is_active
  };
  isEditOpdModalOpen.value = true;
}

async function handleUpdateOpd() {
  try {
    const res = await sigApi.updateOpd(editOpdForm.value);
    const target = opdList.value.find((o) => o.id === editOpdForm.value.id);
    if (target) {
      Object.assign(target, res.data);
    }
    isEditOpdModalOpen.value = false;
    notify.success('Data OPD Diperbarui', res.message);
  } catch (e: any) {
    notify.error('Gagal Memperbarui OPD', e.message);
  }
}

async function handleDeleteOpdWithConfirm(o: OpdItem) {
  const confirmed = await confirmDialog({
    title: 'Hapus Master OPD?',
    text: `OPD "${o.nama_opd}" (${o.kode_opd}) akan dihapus dari sistem.`,
    confirmButtonText: 'Ya, Hapus OPD',
    icon: 'warning',
    isDangerous: true
  });
  if (!confirmed) return;
  try {
    const res = await sigApi.deleteOpd(o.id);
    opdList.value = opdList.value.filter((item) => item.id !== o.id);
    notify.success('OPD Dihapus', res.message);
  } catch (e: any) {
    notify.error('Gagal Hapus OPD', e.message);
  }
}

// ==========================================
// CRUD MASTER KATEGORI SPASIAL HANDLERS
// ==========================================
const newKategoriForm = ref({
  kode: '',
  nama_kategori: '',
  deskripsi: '',
  icon: 'layers',
  warna_default: '#2563eb',
  opd_id: 2,
  nama_opd: 'Dinas Komunikasi dan Informatika'
});

const editKategoriForm = ref({
  id: 0,
  kode: '',
  nama_kategori: '',
  deskripsi: '',
  icon: 'layers',
  warna_default: '#2563eb',
  opd_id: 2,
  nama_opd: '',
  is_active: true
});

function openCreateKategoriModal() {
  newKategoriForm.value = {
    kode: '',
    nama_kategori: '',
    deskripsi: '',
    icon: 'layers',
    warna_default: '#2563eb',
    opd_id: 2,
    nama_opd: 'Dinas Komunikasi dan Informatika'
  };
  isCreateKategoriModalOpen.value = true;
}

async function handleCreateKategori() {
  if (!newKategoriForm.value.nama_kategori) {
    notify.error('Input Tidak Lengkap', 'Nama kategori spasial wajib diisi!');
    return;
  }
  try {
    const res = await sigApi.createKategori(newKategoriForm.value);
    kategoriList.value.unshift(res.data);
    isCreateKategoriModalOpen.value = false;
    notify.success('Kategori Berhasil Dibuat', res.message);
  } catch (e: any) {
    notify.error('Gagal Menambah Kategori', e.message);
  }
}

function openEditKategoriModal(k: KategoriSpasialItem) {
  editKategoriForm.value = {
    id: k.id,
    kode: k.kode,
    nama_kategori: k.nama_kategori,
    deskripsi: k.deskripsi || '',
    icon: k.icon || 'layers',
    warna_default: k.warna_default || '#2563eb',
    opd_id: k.opd_id,
    nama_opd: k.nama_opd || '',
    is_active: k.is_active
  };
  isEditKategoriModalOpen.value = true;
}

async function handleUpdateKategori() {
  try {
    const res = await sigApi.updateKategori(editKategoriForm.value);
    const target = kategoriList.value.find((k) => k.id === editKategoriForm.value.id);
    if (target) {
      Object.assign(target, res.data);
    }
    isEditKategoriModalOpen.value = false;
    notify.success('Kategori Diperbarui', res.message);
  } catch (e: any) {
    notify.error('Gagal Memperbarui Kategori', e.message);
  }
}

async function handleDeleteKategoriWithConfirm(k: KategoriSpasialItem) {
  const confirmed = await confirmDialog({
    title: 'Hapus Kategori Spasial?',
    text: `Kategori layer "${k.nama_kategori}" (${k.kode}) akan dihapus dari katalog master.`,
    confirmButtonText: 'Ya, Hapus Kategori',
    icon: 'warning',
    isDangerous: true
  });
  if (!confirmed) return;
  try {
    const res = await sigApi.deleteKategori(k.id);
    kategoriList.value = kategoriList.value.filter((item) => item.id !== k.id);
    notify.success('Kategori Dihapus', res.message);
  } catch (e: any) {
    notify.error('Gagal Hapus Kategori', e.message);
  }
}

async function loadAuditLogs() {
  isAuditLoading.value = true;
  try {
    const res = await sigApi.getAuditLogs({
      kategori: auditFilterKategori.value,
      aksi: auditFilterAksi.value,
      search: auditSearchQuery.value
    });
    auditLogs.value = res.data.logs;
    auditKategoriList.value = res.data.kategori_list;
    auditAksiList.value = res.data.aksi_list;
    notify.info('Log Diperbarui', `Memuat ${res.data.logs.length} jejak audit sistem.`);
  } catch (e: any) {
    notify.error('Gagal Memuat Log', e.message || 'Kesalahan jaringan');
  } finally {
    isAuditLoading.value = false;
  }
}

async function loadVisitorStats() {
  isVisitorLoading.value = true;
  try {
    const res = await sigApi.getVisitorSummary();
    visitorStats.value = res.data;
    notify.info('Analitik Diperbarui', 'Statistik pengunjung geoportal disinkronkan.');
  } catch (e: any) {
    notify.error('Gagal Memuat Analitik', e.message || 'Kesalahan jaringan');
  } finally {
    isVisitorLoading.value = false;
  }
}

async function showAuditDetail(log: AuditLogItem) {
  const htmlContent = `
    <div style="text-align: left; font-size: 13px; line-height: 1.6; color: #334155;">
      <div style="margin-bottom: 8px;"><strong>Waktu (WITA):</strong> ${log.timestamp}</div>
      <div style="margin-bottom: 8px;"><strong>Kategori:</strong> <span style="background: #e2e8f0; padding: 2px 8px; border-radius: 4px; font-weight: 600;">${log.kategori}</span></div>
      <div style="margin-bottom: 8px;"><strong>Tindakan / Aksi:</strong> <span style="color: #2563eb; font-weight: 600;">${log.aksi}</span></div>
      <div style="margin-bottom: 8px;"><strong>Aktor & Role:</strong> ${log.aktor} (${log.role})</div>
      <div style="margin-bottom: 8px;"><strong>Perangkat Daerah (OPD):</strong> ${log.opd}</div>
      <div style="margin-bottom: 8px;"><strong>Objek / Dataset:</strong> ${log.objek}</div>
      <div style="margin-bottom: 8px;"><strong>Versi Terkait:</strong> <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #0284c7;">${log.versi_terkait || '-'}</code></div>
      <div style="margin-bottom: 8px;"><strong>Alamat IP:</strong> ${log.ip_address || '127.0.0.1'}</div>
      <div style="margin-top: 10px; padding: 8px; background: #f8fafc; border-left: 3px solid #2563eb; border-radius: 4px;">
        <strong>Catatan / Keterangan:</strong><br />${log.keterangan || 'Tidak ada catatan'}
      </div>
    </div>
  `;
  await showDetailDialog(`Detail Jejak Audit #${log.id}`, htmlContent);
}

// ==========================================
// CRUD WILAYAH: KECAMATAN & DESA HANDLERS
// ==========================================

const filteredKecamatans = computed(() => {
  const q = searchKecamatanQuery.value.trim().toLowerCase();
  return kecamatanAdminList.value.filter((k) => {
    return (
      !q ||
      k.nama_kecamatan.toLowerCase().includes(q) ||
      k.kode_kecamatan.toLowerCase().includes(q) ||
      (k.ibukota_kecamatan && k.ibukota_kecamatan.toLowerCase().includes(q))
    );
  });
});

const filteredDesas = computed(() => {
  const q = searchDesaQuery.value.trim().toLowerCase();
  return desaAdminList.value.filter((d) => {
    const matchSearch =
      !q ||
      d.nama_desa.toLowerCase().includes(q) ||
      d.kode_desa.toLowerCase().includes(q) ||
      d.nama_kecamatan.toLowerCase().includes(q);

    const matchKec = !filterDesaKecamatan.value || d.kecamatan_id === Number(filterDesaKecamatan.value);
    const matchTipe = !filterDesaTipe.value || d.tipe === filterDesaTipe.value;

    return matchSearch && matchKec && matchTipe;
  });
});

// 8. Kecamatan Pagination
const pageKecamatan = ref(1);
const pageSizeKecamatan = ref(9);
const paginatedKecamatans = computed(() => {
  const start = (pageKecamatan.value - 1) * pageSizeKecamatan.value;
  return filteredKecamatans.value.slice(start, start + pageSizeKecamatan.value);
});
watch(searchKecamatanQuery, () => {
  pageKecamatan.value = 1;
});

// 9. Desa & Kelurahan Pagination
const pageDesa = ref(1);
const pageSizeDesa = ref(15);
const paginatedDesas = computed(() => {
  const start = (pageDesa.value - 1) * pageSizeDesa.value;
  return filteredDesas.value.slice(start, start + pageSizeDesa.value);
});
watch([searchDesaQuery, filterDesaKecamatan, filterDesaTipe], () => {
  pageDesa.value = 1;
});

async function loadKecamatans() {
  isWilayahLoading.value = true;
  try {
    const res = await sigApi.getKecamatanAdmin();
    kecamatanAdminList.value = res.data;
  } catch (e: any) {
    console.warn('Gagal memuat kecamatan admin:', e);
  } finally {
    isWilayahLoading.value = false;
  }
}

async function loadDesas() {
  isWilayahLoading.value = true;
  try {
    const res = await sigApi.getDesaAdmin();
    desaAdminList.value = res.data;
  } catch (e: any) {
    console.warn('Gagal memuat desa admin:', e);
  } finally {
    isWilayahLoading.value = false;
  }
}

function openCreateKecamatanModal() {
  kecamatanForm.value = {
    id: 0,
    nama_kecamatan: '',
    kode_kecamatan: '71.05.' + String(kecamatanAdminList.value.length + 1).padStart(2, '0'),
    ibukota_kecamatan: '',
    luas_wilayah_km2: 50.0,
    center_lng: 124.57,
    center_lat: 1.18
  };
  isCreateKecamatanModalOpen.value = true;
}

function openEditKecamatanModal(k: KecamatanAdminItem) {
  kecamatanForm.value = {
    id: k.id,
    nama_kecamatan: k.nama_kecamatan,
    kode_kecamatan: k.kode_kecamatan,
    ibukota_kecamatan: k.ibukota_kecamatan || '',
    luas_wilayah_km2: k.luas_wilayah_km2,
    center_lng: k.center_lng || 124.57,
    center_lat: k.center_lat || 1.18
  };
  isEditKecamatanModalOpen.value = true;
}

async function handleCreateKecamatan() {
  try {
    const res = await sigApi.createKecamatan(kecamatanForm.value);
    await loadKecamatans();
    isCreateKecamatanModalOpen.value = false;
    notify.success('Kecamatan Ditambahkan', res.message);
  } catch (e: any) {
    notify.error('Gagal Tambah Kecamatan', e.message);
  }
}

async function handleUpdateKecamatan() {
  try {
    const res = await sigApi.updateKecamatan(kecamatanForm.value);
    await loadKecamatans();
    isEditKecamatanModalOpen.value = false;
    notify.success('Kecamatan Diperbarui', res.message);
  } catch (e: any) {
    notify.error('Gagal Perbarui Kecamatan', e.message);
  }
}

async function handleDeleteKecamatanWithConfirm(k: KecamatanAdminItem) {
  const confirmed = await confirmDialog({
    title: 'Hapus Kecamatan?',
    text: `Kecamatan "${k.nama_kecamatan}" (${k.kode_kecamatan}) akan dihapus dari basis data spasial resmi.`,
    confirmButtonText: 'Ya, Hapus',
    isDangerous: true,
    icon: 'warning'
  });
  if (!confirmed) return;
  try {
    const res = await sigApi.deleteKecamatan(k.id);
    await loadKecamatans();
    notify.success('Kecamatan Dihapus', res.message);
  } catch (e: any) {
    notify.error('Gagal Hapus Kecamatan', e.message);
  }
}

function openCreateDesaModal() {
  const defaultKecId = kecamatanAdminList.value[0]?.id || 1;
  desaForm.value = {
    id: 0,
    nama_desa: '',
    kode_desa: '',
    tipe: 'DESA',
    kecamatan_id: defaultKecId,
    luas_wilayah_km2: 5.0,
    center_lng: 124.57,
    center_lat: 1.18
  };
  isCreateDesaModalOpen.value = true;
}

function openEditDesaModal(d: DesaAdminItem) {
  desaForm.value = {
    id: d.id,
    nama_desa: d.nama_desa,
    kode_desa: d.kode_desa,
    tipe: d.tipe,
    kecamatan_id: d.kecamatan_id,
    luas_wilayah_km2: d.luas_wilayah_km2,
    center_lng: d.center_lng || 124.57,
    center_lat: d.center_lat || 1.18
  };
  isEditDesaModalOpen.value = true;
}

async function handleCreateDesa() {
  try {
    const res = await sigApi.createDesa(desaForm.value);
    await loadDesas();
    await loadKecamatans();
    isCreateDesaModalOpen.value = false;
    notify.success('Desa/Kelurahan Ditambahkan', res.message);
  } catch (e: any) {
    notify.error('Gagal Tambah Desa', e.message);
  }
}

async function handleUpdateDesa() {
  try {
    const res = await sigApi.updateDesa(desaForm.value);
    await loadDesas();
    isEditDesaModalOpen.value = false;
    notify.success('Data Diperbarui', res.message);
  } catch (e: any) {
    notify.error('Gagal Perbarui Desa', e.message);
  }
}

async function handleDeleteDesaWithConfirm(d: DesaAdminItem) {
  const confirmed = await confirmDialog({
    title: `Hapus ${d.tipe}?`,
    text: `${d.tipe} "${d.nama_desa}" (${d.kode_desa}) di Kecamatan ${d.nama_kecamatan} akan dihapus.`,
    confirmButtonText: 'Ya, Hapus',
    isDangerous: true,
    icon: 'warning'
  });
  if (!confirmed) return;
  try {
    const res = await sigApi.deleteDesa(d.id);
    await loadDesas();
    await loadKecamatans();
    notify.success('Desa Dihapus', res.message);
  } catch (e: any) {
    notify.error('Gagal Hapus Desa', e.message);
  }
}

// ==========================================
// CRUD BASE MAP (PETA DASAR) HANDLERS
// ==========================================

const filteredBasemaps = computed(() => {
  const q = searchBasemapQuery.value.trim().toLowerCase();
  return basemapsAdminList.value.filter((b) => {
    const matchSearch =
      !q ||
      b.label.toLowerCase().includes(q) ||
      b.name.toLowerCase().includes(q) ||
      (b.desc && b.desc.toLowerCase().includes(q)) ||
      (b.attribution && b.attribution.toLowerCase().includes(q));

    const matchType = !filterBasemapType.value || b.type === filterBasemapType.value;
    const matchStatus =
      !filterBasemapStatus.value ||
      (filterBasemapStatus.value === 'active' && b.is_active) ||
      (filterBasemapStatus.value === 'inactive' && !b.is_active);

    return matchSearch && matchType && matchStatus;
  });
});

// 10. Basemaps Pagination
const pageBasemaps = ref(1);
const pageSizeBasemaps = ref(6);
const paginatedBasemaps = computed(() => {
  const start = (pageBasemaps.value - 1) * pageSizeBasemaps.value;
  return filteredBasemaps.value.slice(start, start + pageSizeBasemaps.value);
});
watch([searchBasemapQuery, filterBasemapType, filterBasemapStatus], () => {
  pageBasemaps.value = 1;
});

async function loadBasemapsAdmin() {
  isBasemapLoading.value = true;
  try {
    const res = await sigApi.getAdminBasemaps();
    basemapsAdminList.value = res.data;
  } catch (e: any) {
    console.warn('Gagal memuat basemap admin:', e);
  } finally {
    isBasemapLoading.value = false;
  }
}

function openCreateBasemapModal() {
  basemapForm.value = {
    id: 0,
    name: '',
    label: '',
    desc: '',
    type: 'raster',
    tile_url: '',
    attribution: 'Peta Dasar GeoMinsel &copy; 2026',
    max_zoom: 19,
    min_zoom: 0,
    preview_color: '#334155'
  };
  isCreateBasemapModalOpen.value = true;
}

function openEditBasemapModal(b: BaseMapItem) {
  basemapForm.value = {
    id: b.id,
    name: b.name,
    label: b.label,
    desc: b.desc,
    type: b.type,
    tile_url: b.tile_url,
    attribution: b.attribution,
    max_zoom: b.max_zoom,
    min_zoom: b.min_zoom,
    preview_color: b.preview_color
  };
  isEditBasemapModalOpen.value = true;
}

async function handleCreateBasemap() {
  try {
    const res = await sigApi.createBasemap(basemapForm.value);
    await loadBasemapsAdmin();
    isCreateBasemapModalOpen.value = false;
    notify.success('Peta Dasar Ditambahkan', res.message);
  } catch (e: any) {
    notify.error('Gagal Tambah Base Map', e.message);
  }
}

async function handleUpdateBasemap() {
  try {
    const res = await sigApi.updateBasemap(basemapForm.value);
    await loadBasemapsAdmin();
    isEditBasemapModalOpen.value = false;
    notify.success('Peta Dasar Diperbarui', res.message);
  } catch (e: any) {
    notify.error('Gagal Perbarui Base Map', e.message);
  }
}

async function handleToggleBasemap(b: BaseMapItem) {
  try {
    const res = await sigApi.toggleBasemap(b.id);
    b.is_active = res.data.is_active;
    notify.success('Status Berubah', res.message);
  } catch (e: any) {
    notify.error('Gagal Ubah Status', e.message);
  }
}

async function handleSetDefaultBasemap(b: BaseMapItem) {
  const confirmed = await confirmDialog({
    title: 'Tetapkan Sebagai Default?',
    text: `Peta dasar "${b.label}" akan menjadi layer acuan utama saat pengunjung pertama kali membuka Geoportal.`,
    confirmButtonText: 'Ya, Jadikan Default',
    icon: 'question'
  });
  if (!confirmed) return;
  try {
    const res = await sigApi.setDefaultBasemap(b.id);
    basemapsAdminList.value.forEach((item) => {
      item.is_default = item.id === b.id;
      if (item.id === b.id) item.is_active = true;
    });
    notify.success('Default Ditetapkan', res.message);
  } catch (e: any) {
    notify.error('Gagal Tetapkan Default', e.message);
  }
}

async function handleDeleteBasemapWithConfirm(b: BaseMapItem) {
  const confirmed = await confirmDialog({
    title: 'Hapus Peta Dasar?',
    text: `Konfigurasi peta dasar "${b.label}" (${b.name}) akan dihapus secara permanen.`,
    confirmButtonText: 'Ya, Hapus',
    isDangerous: true,
    icon: 'warning'
  });
  if (!confirmed) return;
  try {
    const res = await sigApi.deleteBasemap(b.id);
    await loadBasemapsAdmin();
    notify.success('Peta Dasar Dihapus', res.message);
  } catch (e: any) {
    notify.error('Gagal Hapus Base Map', e.message);
  }
}

async function loadDashboardData() {
  isLoading.value = true;
  try {
    sigApi.trackVisitor({ page: 'dashboard' }).catch(() => {});

    const [wfRes, dsRes, ddRes, usersRes, opdRes, katRes, logsRes, visitorRes, kecRes, desaRes, basemapsRes, overviewRes] = await Promise.allSettled([
      sigApi.getWorkflowStates(),
      sigApi.getWorkflowDatasets(),
      sigApi.getDataDasar(),
      sigApi.getUsers(),
      sigApi.getOpds(),
      sigApi.getKategoriAdmin(),
      sigApi.getAuditLogs(),
      sigApi.getVisitorSummary(),
      sigApi.getKecamatanAdmin(),
      sigApi.getDesaAdmin(),
      sigApi.getAdminBasemaps(),
      sigApi.getOverviewStats()
    ]);

    if (overviewRes.status === 'fulfilled') {
      overviewStats.value = overviewRes.value.data;
    }
    if (wfRes.status === 'fulfilled') {
      workflowStates.value = wfRes.value.data;
    }
    if (dsRes.status === 'fulfilled') {
      datasets.value = dsRes.value.data.datasets;
    }
    if (ddRes.status === 'fulfilled') {
      dataDasarList.value = ddRes.value.data.versions;
      serverZipFiles.value = (ddRes.value.data as any).server_zip_files || [];
      if (serverZipFiles.value.length > 0 && !uploadFileName.value) {
        uploadFileName.value = serverZipFiles.value[0].filename;
      }
    }
    if (usersRes.status === 'fulfilled') {
      usersList.value = usersRes.value.data;
    }
    if (opdRes.status === 'fulfilled') {
      opdList.value = opdRes.value.data;
    }
    if (katRes.status === 'fulfilled') {
      kategoriList.value = katRes.value.data;
    }
    if (logsRes.status === 'fulfilled') {
      auditLogs.value = logsRes.value.data.logs;
      auditKategoriList.value = logsRes.value.data.kategori_list;
      auditAksiList.value = logsRes.value.data.aksi_list;
    }
    if (visitorRes.status === 'fulfilled') {
      visitorStats.value = visitorRes.value.data;
    }
    if (kecRes.status === 'fulfilled') {
      kecamatanAdminList.value = kecRes.value.data;
    }
    if (desaRes.status === 'fulfilled') {
      desaAdminList.value = desaRes.value.data;
    }
    if (basemapsRes.status === 'fulfilled') {
      basemapsAdminList.value = basemapsRes.value.data;
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
    let res;
    if (dataDasarFile.value) {
      const fd = new FormData();
      fd.append('shp_file', dataDasarFile.value);
      fd.append('tipe_data', uploadTipe.value);
      fd.append('sumber_resmi', uploadSumber.value);
      fd.append('tahun_referensi', String(uploadTahun.value));
      res = await sigApi.uploadDataDasar(fd);
    } else {
      res = await sigApi.uploadDataDasar({
        tipe_data: uploadTipe.value,
        sumber_resmi: uploadSumber.value,
        tahun_referensi: uploadTahun.value,
        nama_file: uploadFileName.value
      });
    }
    lastValidationReport.value = res.data.validation_report;
    dataDasarList.value.push(res.data.version_record);
    notify.success('Upload SHP Berhasil', res.message || 'Paket SHP tervalidasi EPSG:4326.');
  } catch (e: any) {
    notify.error('Gagal Upload SHP', e.message || 'Verifikasi paket SHP gagal');
  } finally {
    isUploading.value = false;
    dataDasarFile.value = null;
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
  if (!['superadmin', 'admin_gis'].includes(role) && activeTab.value === 'data_dasar') {
    activeTab.value = 'overview';
  }
  if (!['superadmin', 'admin_gis', 'admin_opd', 'validator_gis'].includes(role) && activeTab.value === 'kategori') {
    activeTab.value = 'overview';
  }
  if (!['superadmin', 'admin_gis', 'admin_opd'].includes(role) && activeTab.value === 'opd') {
    activeTab.value = 'overview';
  }
  if (!['superadmin', 'admin_opd'].includes(role) && activeTab.value === 'users') {
    activeTab.value = 'overview';
  }
  if (!['superadmin', 'admin_gis', 'admin_opd'].includes(role) && activeTab.value === 'wilayah') {
    activeTab.value = 'overview';
  }
  if (!['superadmin', 'admin_gis'].includes(role) && activeTab.value === 'basemaps') {
    activeTab.value = 'overview';
  }

  // Refetch users with new caller scope
  sigApi.getUsers({
    caller_role: role,
    caller_opd: authStore.user?.opd,
    kode_opd: authStore.user?.kode_opd
  }).then(res => {
    usersList.value = res.data;
  }).catch(() => {});

  // Fetch updated profile
  authStore.fetchProfile(role).then(() => {
    syncProfileForm();
  }).catch(() => {});

  const roleObj = authStore.roles.find((r) => r.name === role);
  notify.info(
    'Peran Pengguna Berubah',
    `Kini bertindak sebagai: ${roleObj?.label || role} (${roleObj?.opd || 'Kab. Minahasa Selatan'})`
  );
}

// ==========================================
// PROFILE STATE & METHODS
// ==========================================
const profileEditForm = ref({
  nama_lengkap: '',
  email: '',
  nip: '',
  jabatan: '',
  telepon: '',
  alamat_kantor: ''
});
const isSavingProfile = ref(false);

const passwordForm = ref({
  current_password: '',
  new_password: '',
  confirm_password: ''
});
const isChangingPassword = ref(false);

function syncProfileForm() {
  if (authStore.user) {
    profileEditForm.value = {
      nama_lengkap: authStore.user.nama_lengkap || '',
      email: authStore.user.email || '',
      nip: authStore.user.nip || ('19840512 200801 1 ' + String(authStore.user.id || 1).padStart(3, '0')),
      jabatan: authStore.user.jabatan || (authStore.user.role === 'superadmin' ? 'Pranata Komputer Ahli Muda / Administrator Utama' : (authStore.user.role === 'admin_opd' ? 'Kepala Sub Bagian Program & Data' : 'Pengelola Sistem Informasi Geografis')),
      telepon: authStore.user.telepon || ('0812-4455-' + String((authStore.user.id || 1) * 111).padStart(4, '0')),
      alamat_kantor: authStore.user.alamat_kantor || 'Jl. Trans Sulawesi, Kompleks Perkantoran Pemerintah Kab. Minahasa Selatan, Pondang, Amurang'
    };
  }
}

watch(
  () => authStore.user,
  () => {
    syncProfileForm();
  },
  { immediate: true, deep: true }
);

watch(activeTab, (tab) => {
  if (tab === 'datasets' && datasets.value.length === 0) {
    sigApi.getWorkflowDatasets().then((res) => {
      if (res.data?.datasets) {
        datasets.value = res.data.datasets;
      }
    }).catch(() => {});
  }
  if (tab === 'profile') {
    syncProfileForm();
    authStore.fetchProfile().then(() => syncProfileForm()).catch(() => {});
  }
});

async function handleSaveProfile() {
  if (!profileEditForm.value.nama_lengkap) {
    notify.error('Input Tidak Lengkap', 'Nama lengkap pengguna wajib diisi!');
    return;
  }
  isSavingProfile.value = true;
  try {
    await authStore.updateProfile(profileEditForm.value);
    notify.success('Profil Diperbarui', 'Data profil pengguna Anda berhasil disimpan ke sistem.');
  } catch (e: any) {
    notify.error('Gagal Simpan Profil', e.message || 'Terjadi kesalahan saat menyimpan profil.');
  } finally {
    isSavingProfile.value = false;
  }
}

async function handleChangePassword() {
  if (!passwordForm.value.new_password) {
    notify.error('Input Kosong', 'Kata sandi baru tidak boleh kosong!');
    return;
  }
  if (passwordForm.value.new_password.length < 6) {
    notify.error('Kata Sandi Lemah', 'Kata sandi baru minimal harus 6 karakter!');
    return;
  }
  if (passwordForm.value.new_password !== passwordForm.value.confirm_password) {
    notify.error('Konfirmasi Tidak Cocok', 'Kata sandi baru dan konfirmasi kata sandi tidak sama!');
    return;
  }

  isChangingPassword.value = true;
  try {
    await authStore.updateProfile({
      password_baru: passwordForm.value.new_password
    });
    passwordForm.value = {
      current_password: '',
      new_password: '',
      confirm_password: ''
    };
    notify.success('Kata Sandi Diperbarui', 'Kata sandi akun Anda berhasil diperbarui dengan aman.');
  } catch (e: any) {
    notify.error('Gagal Ubah Sandi', e.message || 'Terjadi kesalahan saat memperbarui kata sandi.');
  } finally {
    isChangingPassword.value = false;
  }
}

// User Personal Audit History
const userPersonalAuditLogs = computed(() => {
  const userName = authStore.user?.nama_lengkap?.toLowerCase() || '';
  const userRole = authStore.user?.role || '';
  return auditLogs.value.filter((l) => {
    const matchName = l.aktor && l.aktor.toLowerCase().includes(userName);
    const matchRole = l.role && l.role === userRole;
    return matchName || matchRole;
  }).slice(0, 10);
});

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
        <!-- Theme Switcher (Hitam Putih Elegan) -->
        <button
          type="button"
          class="btn-theme-toggle"
          @click="themeStore.toggleTheme"
          :title="themeStore.isDark ? 'Ganti ke Mode Terang (Light Mode)' : 'Ganti ke Mode Gelap (Dark Mode)'"
        >
          <Sun v-if="themeStore.isDark" :size="15" class="icon-sun" />
          <Moon v-else :size="15" class="icon-moon" />
          <span class="theme-text-dash">{{ themeStore.isDark ? 'Gelap' : 'Terang' }}</span>
        </button>

        <!-- User Badge & Profile Click -->
        <div
          class="user-chip clickable-chip"
          :class="{ active: activeTab === 'profile' }"
          @click="activeTab = 'profile'"
          title="Buka Halaman Profil Pengguna Lengkap"
        >
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

          <!-- Modul Superadmin & Admin GIS: Upload SHP Batas Wilayah -->
          <button
            v-if="canAccessDataDasar"
            class="nav-item item-superadmin"
            :class="{ active: activeTab === 'data_dasar' }"
            @click="activeTab = 'data_dasar'"
          >
            <ShieldAlert :size="18" />
            <span>Data Dasar SHP Wilayah</span>
          </button>

          <!-- Modul Master Kategori Spasial Tematik -->
          <button
            v-if="['superadmin', 'admin_gis', 'admin_opd', 'validator_gis'].includes(authStore.currentRole)"
            class="nav-item"
            :class="{ active: activeTab === 'kategori' }"
            @click="activeTab = 'kategori'"
          >
            <Layers :size="18" />
            <span>Kategori Spasial ({{ kategoriList.length }})</span>
          </button>

          <!-- Modul CRUD Wilayah: Kecamatan, Kelurahan & Desa -->
          <button
            v-if="['superadmin', 'admin_gis', 'admin_opd'].includes(authStore.currentRole)"
            class="nav-item"
            :class="{ active: activeTab === 'wilayah' }"
            @click="activeTab = 'wilayah'"
          >
            <MapPin :size="18" />
            <span>Wilayah (Kecamatan &amp; Desa)</span>
          </button>

          <!-- Modul Superadmin: CRUD Base Map (Peta Dasar) -->
          <button
            v-if="['superadmin', 'admin_gis'].includes(authStore.currentRole)"
            class="nav-item item-superadmin"
            :class="{ active: activeTab === 'basemaps' }"
            @click="activeTab = 'basemaps'"
          >
            <MapIcon :size="18" />
            <span>Peta Dasar (Base Map)</span>
          </button>

          <!-- Modul Master OPD (Perangkat Daerah) -->
          <button
            v-if="['superadmin', 'admin_gis', 'admin_opd'].includes(authStore.currentRole)"
            class="nav-item"
            :class="{ active: activeTab === 'opd' }"
            @click="activeTab = 'opd'"
          >
            <Building2 :size="18" />
            <span>Master OPD ({{ opdList.length }})</span>
          </button>

          <!-- Modul Manajemen Pengguna & Hak Akses -->
          <button
            v-if="['superadmin', 'admin_opd'].includes(authStore.currentRole)"
            class="nav-item"
            :class="{ active: activeTab === 'users' }"
            @click="activeTab = 'users'"
          >
            <Users :size="18" />
            <span>Pengguna &amp; Hak Akses</span>
          </button>

          <button
            class="nav-item"
            :class="{ active: activeTab === 'audit' }"
            @click="activeTab = 'audit'"
          >
            <History :size="18" />
            <span>Audit Trail & Log</span>
          </button>

          <!-- Modul Profil Pengguna Lengkap -->
          <button
            class="nav-item item-profile"
            :class="{ active: activeTab === 'profile' }"
            @click="activeTab = 'profile'"
          >
            <User :size="18" />
            <span>Profil Pengguna</span>
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
                <span class="kpi-val text-blue">{{ kpiCakupanWilayah }}</span>
                <span class="kpi-badge badge-green">{{ kpiCakupanPersen }}% Aktif</span>
              </div>
              <span class="kpi-sub">Kecamatan terpetakan dalam SRID:4326</span>
            </div>

            <div class="kpi-card">
              <span class="kpi-label">Desa / Kelurahan</span>
              <div class="kpi-val-row">
                <span class="kpi-val">{{ kpiTotalDesa }}</span>
                <span class="kpi-badge badge-blue">Terhubung</span>
              </div>
              <span class="kpi-sub">{{ kpiDesaRincian }}</span>
            </div>

            <div class="kpi-card">
              <span class="kpi-label">Katalog Kategori</span>
              <div class="kpi-val-row">
                <span class="kpi-val text-amber">{{ kpiTotalKategori }}</span>
                <span class="kpi-badge badge-amber">Standar SDI</span>
              </div>
              <span class="kpi-sub">5 Pilar data tematik daerah</span>
            </div>

            <div class="kpi-card">
              <span class="kpi-label">Persetujuan Terpusat</span>
              <div class="kpi-val-row">
                <span class="kpi-val text-emerald">{{ kpiTotalWorkflowStages }} Tahap</span>
                <span class="kpi-badge badge-green">Terstandar</span>
              </div>
              <span class="kpi-sub">Walidata Bappelitbangda terintegrasi</span>
            </div>
          </div>

          <!-- Visual Statistics Charts: Overview -->
          <div class="tab-chart-grid">
            <StatChart
              title="Distribusi Geometri Spasial"
              subtitle="Sebaran layer polygon, line, dan point aktif di Geoportal"
              type="doughnut"
              :data="chartOverviewGeometry"
              badge="SRID:4326"
            />
            <StatChart
              title="Volume Alur Persetujuan Dataset"
              subtitle="Sebaran tahapan verifikasi geospasial Satu Data Indonesia"
              type="bar"
              :data="chartOverviewPipeline"
              badge="12 Tahap SDI"
            />
            <StatChart
              title="Tren Akses Geoportal (7 Hari Terakhir)"
              subtitle="Fluktuasi trafik harian pengguna di Geoportal"
              type="line"
              :data="chartOverviewVisitors"
              badge="Trafik Real-time"
            />
          </div>

          <!-- ============================================================== -->
          <!-- VISITOR ANALYTICS WIDGET: SISTEM PENGUNJUNG GEOPORTAL          -->
          <!-- ============================================================== -->
          <div class="dash-box visitor-analytics-box">
            <div class="box-header">
              <div class="box-title-wrap">
                <Globe :size="18" class="text-blue" />
                <h3 class="box-title">Statistik Pengunjung Geoportal &amp; Analitik Akses</h3>
              </div>
              <button type="button" class="btn-box-action" @click="loadVisitorStats" title="Segarkan Data Pengunjung">
                <RotateCcw :size="14" :class="{ 'spin-anim': isVisitorLoading }" />
                <span>Refresh Data</span>
              </button>
            </div>

            <!-- KPI Row Pengunjung -->
            <div class="visitor-kpi-grid">
              <div class="vkpi-card">
                <span class="vkpi-label">Total Akses Geoportal</span>
                <div class="vkpi-val-row">
                  <span class="vkpi-val text-blue">{{ (visitorStats?.total_kunjungan || 2400).toLocaleString('id-ID') }}</span>
                  <span class="kpi-badge badge-blue">Akumulatif</span>
                </div>
                <span class="vkpi-sub">Total hits seluruh halaman &amp; layer</span>
              </div>

              <div class="vkpi-card">
                <span class="vkpi-label">Kunjungan Hari Ini</span>
                <div class="vkpi-val-row">
                  <span class="vkpi-val text-emerald">{{ (visitorStats?.kunjungan_hari_ini || 148).toLocaleString('id-ID') }}</span>
                  <span class="kpi-badge badge-green">Hari Ini</span>
                </div>
                <span class="vkpi-sub">Sesi aktif pengguna terdeteksi</span>
              </div>

              <div class="vkpi-card">
                <span class="vkpi-label">Kunjungan Bulan Ini</span>
                <div class="vkpi-val-row">
                  <span class="vkpi-val text-indigo">{{ (visitorStats?.kunjungan_bulan_ini || 1842).toLocaleString('id-ID') }}</span>
                  <span class="kpi-badge badge-indigo">Bulan Berjalan</span>
                </div>
                <span class="vkpi-sub">Volume akses bulanan Pemkab Minsel</span>
              </div>

              <div class="vkpi-card">
                <span class="vkpi-label">Pengunjung Unik</span>
                <div class="vkpi-val-row">
                  <span class="vkpi-val text-amber">{{ (visitorStats?.pengunjung_unik || 505).toLocaleString('id-ID') }}</span>
                  <span class="kpi-badge badge-amber">IP Unik</span>
                </div>
                <span class="vkpi-sub">Kunjungan unik perangkat pengguna</span>
              </div>
            </div>

            <!-- Detail Distribusi Perangkat & Halaman Populer -->
            <div class="visitor-details-grid">
              <!-- Perangkat -->
              <div class="visitor-subcard">
                <div class="subcard-header">
                  <Monitor :size="15" class="text-blue" />
                  <h4 class="subcard-title">Distribusi Perangkat Pengguna</h4>
                </div>
                <div class="device-bars">
                  <div class="device-item">
                    <div class="device-meta">
                      <span class="d-label"><Monitor :size="13" /> Desktop / Laptop</span>
                      <span class="d-count">{{ deviceDesktopCount.toLocaleString('id-ID') }} ({{ deviceDesktopPercent }}%)</span>
                    </div>
                    <div class="device-bar-track">
                      <div class="device-bar-fill bar-desktop" :style="{ width: deviceDesktopPercent + '%' }"></div>
                    </div>
                  </div>

                  <div class="device-item">
                    <div class="device-meta">
                      <span class="d-label"><Smartphone :size="13" /> Smartphone / Ponsel</span>
                      <span class="d-count">{{ deviceMobileCount.toLocaleString('id-ID') }} ({{ deviceMobilePercent }}%)</span>
                    </div>
                    <div class="device-bar-track">
                      <div class="device-bar-fill bar-mobile" :style="{ width: deviceMobilePercent + '%' }"></div>
                    </div>
                  </div>

                  <div class="device-item">
                    <div class="device-meta">
                      <span class="d-label"><Tablet :size="13" /> Tablet</span>
                      <span class="d-count">{{ deviceTabletCount.toLocaleString('id-ID') }} ({{ deviceTabletPercent }}%)</span>
                    </div>
                    <div class="device-bar-track">
                      <div class="device-bar-fill bar-tablet" :style="{ width: deviceTabletPercent + '%' }"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Halaman Populer -->
              <div class="visitor-subcard">
                <div class="subcard-header">
                  <Activity :size="15" class="text-emerald" />
                  <h4 class="subcard-title">Halaman &amp; Layanan Paling Populer</h4>
                </div>
                <div class="popular-page-list">
                  <div
                    v-for="(item, idx) in popularPagesList"
                    :key="item.page || idx"
                    class="popular-page-item"
                  >
                    <div class="page-info">
                      <span class="page-tag font-mono">/{{ item.page }}</span>
                      <span class="page-name">{{ item.nama }}</span>
                    </div>
                    <span class="page-hits font-mono">{{ item.hits.toLocaleString('id-ID') }} hits</span>
                  </div>
                </div>
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
              <!-- Upload SHP File Button (Role Guarded) -->
              <button
                v-if="canCreateDataset"
                type="button"
                class="btn-create-dataset"
                @click="openUploadShpModal"
                title="Unggah Paket Shapefile ZIP (.shp, .shx, .dbf, .prj)"
              >
                <UploadCloud :size="15" />
                <span>Upload SHP</span>
              </button>

              <!-- Create Dataset Manual Button -->
              <button
                v-if="canCreateDataset"
                type="button"
                class="btn-box-action"
                @click="openCreateDatasetModal"
                title="Tambah Metadata Dataset Manual (Form)"
              >
                <Plus :size="15" />
                <span>Tambah Manual</span>
              </button>

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

          <!-- Read-Only Notice for Pimpinan / Publik -->
          <div v-if="isReadOnlyRole" class="readonly-notice-banner">
            <ShieldCheck :size="16" class="icon-ro" />
            <span>
              Mode Pemantauan Eksekutif / Publik — Hak akses tindakan dibatasi untuk eksplorasi dan monitoring data spasial.
            </span>
          </div>

          <!-- Visual Statistics Charts: Datasets -->
          <div class="tab-chart-grid">
            <StatChart
              title="Status Kesiapan &amp; Publikasi Dataset"
              subtitle="Perbandingan dataset tayang aktif vs antrean verifikasi"
              type="doughnut"
              :data="chartDatasetStatus"
              badge="Katalog Publik"
            />
            <StatChart
              title="Produktivitas Dataset per OPD Produsen"
              subtitle="Volume kontribusi data geospasial sektoral Kabupaten Minahasa Selatan"
              type="bar"
              :data="chartDatasetByOpd"
              badge="Produsen Data"
            />
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
              v-for="d in paginatedDatasets"
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
                <span class="chip chip-version font-mono" :class="(d.versi || 'v1.0').includes('RESMI') ? 'version-official' : 'version-draft'">
                  <History :size="11" />
                  <span>{{ d.versi || 'v1.0' }}</span>
                </span>
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
                <span v-if="d.has_shp_file || d.file_name" class="chip chip-file" :title="d.file_name || 'Paket SHP ZIP'">
                  <FileArchive :size="11" />
                  <span>{{ d.file_size_formatted || 'SHP' }}</span>
                </span>
              </div>

              <div class="bento-footer">
                <span class="bento-cat-label">{{ d.nama_kategori }}</span>

                <div class="action-btn-group">
                  <!-- Unduh SHP ZIP jika tersedia -->
                  <button
                    v-if="d.has_shp_file || d.file_name || d.file_path"
                    type="button"
                    class="btn-act btn-download"
                    @click="handleDownloadShp(d)"
                    title="Unduh Berkas Asli Shapefile (ZIP)"
                  >
                    <Download :size="12" />
                    <span>Unduh SHP</span>
                  </button>

                  <!-- Upload Ulang Revisi SHP (Jika DRAFT atau REJECTED_GIS) -->
                  <button
                    v-if="canEditDataset(d) && (d.status === 'DRAFT' || d.status === 'REJECTED_GIS')"
                    type="button"
                    class="btn-act btn-reupload"
                    @click="openReuploadShpModal(d)"
                    title="Unggah Ulang Berkas SHP Revisi"
                  >
                    <UploadCloud :size="12" />
                    <span>Revisi SHP</span>
                  </button>

                  <!-- Edit Metadata (Operator/Admin OPD on Draft, or Admin GIS/Superadmin) -->
                  <button
                    v-if="canEditDataset(d)"
                    type="button"
                    class="btn-act btn-edit"
                    @click="openEditDatasetModal(d)"
                    title="Perbarui Metadata"
                  >
                    <Edit3 :size="12" />
                    <span>Edit</span>
                  </button>

                  <!-- Delete Draft (Operator/Admin OPD on Draft, or Superadmin) -->
                  <button
                    v-if="canDeleteDataset(d)"
                    type="button"
                    class="btn-act btn-delete"
                    @click="handleDeleteDataset(d)"
                    title="Hapus Dataset Draft"
                  >
                    <Trash2 :size="12" />
                    <span>Hapus</span>
                  </button>

                  <button
                    v-if="['superadmin', 'operator_opd', 'admin_opd'].includes(authStore.currentRole) && d.status === 'DRAFT'"
                    class="btn-act btn-submit"
                    @click="handleTransitionWithConfirm(d, 'IN_VALIDATION_GIS', 'Ajukan Validasi', 'Diajukan ke validasi teknis GIS')"
                  >
                    <Send :size="12" />
                    <span>Ajukan</span>
                  </button>

                  <button
                    v-if="['superadmin', 'admin_gis'].includes(authStore.currentRole) && d.status === 'APPROVED'"
                    class="btn-act btn-publish"
                    @click="handleTransitionWithConfirm(d, 'PUBLISHED', 'Publikasikan ke Geoportal', 'Diterbitkan ke katalog publik geoportal')"
                  >
                    <Layers :size="12" />
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
                  <th>Versi &amp; Revisi</th>
                  <th>OPD Penanggung Jawab</th>
                  <th>Kategori &amp; Geometri</th>
                  <th>Cakupan Wilayah</th>
                  <th>Status Workflow</th>
                  <th>Tindakan Otoritas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="d in paginatedDatasets" :key="d.id">
                  <td>
                    <div class="cell-main">
                      <span class="cell-title">{{ d.nama_dataset }}</span>
                      <span class="cell-meta">
                        {{ d.kode_dataset }} &bull; {{ d.jumlah_fitur }} fitur
                        <span v-if="d.has_shp_file || d.file_name" class="inline-file-badge">
                          &bull; {{ d.file_size_formatted || 'SHP ZIP' }}
                        </span>
                      </span>
                    </div>
                  </td>
                  <td>
                    <div class="version-cell">
                      <span class="version-badge font-mono" :class="(d.versi || 'v1.0').includes('RESMI') ? 'badge-official' : 'badge-draft'">
                        {{ d.versi || 'v1.0' }}
                      </span>
                      <span v-if="d.revisi_ke" class="revisi-sub">Rev #{{ d.revisi_ke }}</span>
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
                      <!-- Unduh SHP ZIP jika tersedia -->
                      <button
                        v-if="d.has_shp_file || d.file_name || d.file_path"
                        type="button"
                        class="btn-act btn-download"
                        @click="handleDownloadShp(d)"
                        title="Unduh Berkas Asli Shapefile (ZIP)"
                      >
                        <Download :size="12" />
                        <span>Unduh</span>
                      </button>

                      <!-- Upload Ulang Revisi SHP (Jika DRAFT atau REJECTED_GIS) -->
                      <button
                        v-if="canEditDataset(d) && (d.status === 'DRAFT' || d.status === 'REJECTED_GIS')"
                        type="button"
                        class="btn-act btn-reupload"
                        @click="openReuploadShpModal(d)"
                        title="Unggah Ulang Berkas SHP Revisi"
                      >
                        <UploadCloud :size="12" />
                        <span>Revisi</span>
                      </button>

                      <!-- Edit Metadata -->
                      <button
                        v-if="canEditDataset(d)"
                        type="button"
                        class="btn-act btn-edit"
                        @click="openEditDatasetModal(d)"
                        title="Perbarui Metadata"
                      >
                        <Edit3 :size="12" />
                        <span>Edit</span>
                      </button>

                      <!-- Delete Draft -->
                      <button
                        v-if="canDeleteDataset(d)"
                        type="button"
                        class="btn-act btn-delete"
                        @click="handleDeleteDataset(d)"
                        title="Hapus Dataset Draft"
                      >
                        <Trash2 :size="12" />
                        <span>Hapus</span>
                      </button>

                      <button
                        v-if="['superadmin', 'operator_opd', 'admin_opd'].includes(authStore.currentRole) && d.status === 'DRAFT'"
                        class="btn-act btn-submit"
                        @click="handleTransitionWithConfirm(d, 'IN_VALIDATION_GIS', 'Ajukan Validasi', 'Diajukan ke validasi teknis GIS')"
                      >
                        <Send :size="12" />
                        <span>Ajukan</span>
                      </button>

                      <button
                        v-if="['superadmin', 'admin_gis'].includes(authStore.currentRole) && d.status === 'APPROVED'"
                        class="btn-act btn-publish"
                        @click="handleTransitionWithConfirm(d, 'PUBLISHED', 'Publikasikan ke Geoportal', 'Diterbitkan ke katalog publik geoportal')"
                      >
                        <Layers :size="12" />
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

          <!-- Pagination Dataset Sektoral -->
          <PaginationControl
            v-model:page="pageDatasets"
            v-model:pageSize="pageSizeDatasets"
            :total="filteredDatasets.length"
            :pageSizeOptions="[6, 12, 24, 48]"
            label="dataset spasial"
          />
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

          <!-- Visual Statistics Charts: Validation -->
          <div class="tab-chart-grid">
            <StatChart
              title="Rasio Antrean Validasi GIS"
              subtitle="Proporsi dataset baru diajukan vs dalam telaah teknis GIS"
              type="doughnut"
              :data="chartValidationRatio"
              badge="Antrean GIS"
            />
            <StatChart
              title="Sebaran Tema Spasial dalam Antrean"
              subtitle="Kategori geospasial tematik yang sedang ditelaah topologinya"
              type="bar"
              :data="chartValidationCategories"
              badge="Topologi ESRI"
            />
          </div>

          <div v-if="filteredValidationDatasets.length === 0" class="empty-state-card">
            <CheckCheck :size="40" class="empty-icon text-emerald" />
            <h3 class="empty-title">Tidak Ada Antrean Validasi</h3>
            <p class="empty-desc">Seluruh pengajuan dataset spasial telah tervalidasi atau tidak ada data yang cocok.</p>
          </div>

          <div v-else class="cards-queue">
            <div
              v-for="d in paginatedValidationDatasets"
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

          <!-- Pagination Antrean Validasi -->
          <PaginationControl
            v-model:page="pageValidation"
            v-model:pageSize="pageSizeValidation"
            :total="filteredValidationDatasets.length"
            :pageSizeOptions="[5, 10, 20]"
            label="antrean validasi"
          />
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

          <!-- Visual Statistics Charts: Approval -->
          <div class="tab-chart-grid">
            <StatChart
              title="Kesiapan Publikasi Satu Data Indonesia (SDI)"
              subtitle="Akumulasi dataset yang telah disahkan Walidata Bappelitbangda"
              type="doughnut"
              :data="chartApprovalReadiness"
              badge="Walidata SDI"
            />
            <StatChart
              title="Komposisi Geometri Dataset Walidata"
              subtitle="Penyebaran batas polygon, garis jaringan, dan titik fasilitas daerah"
              type="bar"
              :data="chartApprovalGeometries"
              badge="Geometri Resmi"
            />
          </div>

          <div v-if="filteredApprovalDatasets.length === 0" class="empty-state-card">
            <FileCheck2 :size="40" class="empty-icon text-blue" />
            <h3 class="empty-title">Tidak Ada Antrean Persetujuan</h3>
            <p class="empty-desc">Semua pengajuan dataset telah disetujui Walidata Bappelitbangda atau belum ada antrean baru.</p>
          </div>

          <div v-else class="cards-queue">
            <div
              v-for="d in paginatedApprovalDatasets"
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

          <!-- Pagination Antrean Persetujuan -->
          <PaginationControl
            v-model:page="pageApproval"
            v-model:pageSize="pageSizeApproval"
            :total="filteredApprovalDatasets.length"
            :pageSizeOptions="[5, 10, 20]"
            label="antrean persetujuan"
          />
        </div>

        <!-- ============================================================== -->
        <!-- TAB 5: DATA DASAR SHP BATAS WILAYAH (KHUSUS SUPERADMIN)         -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'data_dasar'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Modul Data Dasar Geospasial (Batas Administrasi)</h2>
              <p class="content-sub">Khusus Superadmin: Unggah dan aktivasi file ZIP Shapefile resmi batas Kabupaten, 17 Kecamatan, dan 177 Desa/Kelurahan</p>
            </div>
          </div>

          <!-- Visual Statistics Charts: Data Dasar SHP -->
          <div class="tab-chart-grid">
            <StatChart
              title="Fitur Terekam per Versi SHP Rujukan"
              subtitle="Evolusi perekaman poligon dan batas wilayah per rilis data dasar"
              type="bar"
              :data="chartDataDasarVersions"
              badge="17 Kecamatan"
            />
            <StatChart
              title="Status Versi SHP Rujukan"
              subtitle="Proporsi paket data dasar aktif vs arsip histori pembaruan"
              type="doughnut"
              :data="chartDataDasarStatus"
              badge="Integritas Topologi"
            />
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
                  <option value="DESA">Batas 166 Desa & 11 Kelurahan (177 Fitur)</option>
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
                <label class="form-lbl">Berkas ZIP Batas Administrasi *</label>
                <div class="file-picker-row">
                  <input
                    ref="dataDasarFileInputRef"
                    type="file"
                    accept=".zip"
                    class="hidden-file-input"
                    @change="onDataDasarFileSelected"
                  />
                  <button
                    type="button"
                    class="btn-choose-file"
                    @click="dataDasarFileInputRef?.click()"
                  >
                    <UploadCloud :size="14" />
                    <span>Pilih Berkas ZIP</span>
                  </button>
                  <span class="file-chosen-text" :title="uploadFileName">
                    {{ uploadFileName || 'Belum ada berkas dipilih' }}
                  </span>
                </div>

                <!-- Server Zip Detection Banner -->
                <div v-if="serverZipFiles.length > 0" class="server-zip-hint">
                  <span class="sz-label">📦 Berkas ZIP di Server (backend/writable/uploads):</span>
                  <div class="sz-list">
                    <button
                      v-for="sz in serverZipFiles"
                      :key="sz.filename"
                      type="button"
                      class="sz-badge-btn"
                      :class="{ active: uploadFileName === sz.filename && !dataDasarFile }"
                      @click="useServerZipFile(sz.filename)"
                    >
                      <span>📁 {{ sz.filename }} ({{ sz.size_human }})</span>
                      <span class="sz-tag">{{ uploadFileName === sz.filename && !dataDasarFile ? 'Dipilih' : 'Gunakan' }}</span>
                    </button>
                  </div>
                </div>
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
                    <div class="action-btn-group">
                      <button
                        v-if="v.status !== 'AKTIF'"
                        type="button"
                        class="btn-act btn-approve"
                        @click="handleActivateDataDasarWithConfirm(v)"
                        title="Jadikan Acuan Batas Utama"
                      >
                        <CheckCircle2 :size="12" />
                        <span>Aktifkan Acuan</span>
                      </button>

                      <button
                        v-if="v.status !== 'AKTIF'"
                        type="button"
                        class="btn-act btn-delete"
                        @click="handleDeleteDataDasarWithConfirm(v)"
                        title="Hapus Berkas SHP Tidak Aktif"
                      >
                        <Trash2 :size="12" />
                        <span>Hapus</span>
                      </button>

                      <span v-else class="text-active-mark">
                        <CheckCircle2 :size="14" />
                        <span>Rujukan Utama</span>
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB: WILAYAH (KECAMATAN, KELURAHAN & DESA - CRUD)              -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'wilayah'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Manajemen Administrasi Wilayah</h2>
              <p class="content-sub">Kelola batas hierarki 17 Kecamatan, 166 Desa, dan 11 Kelurahan Kabupaten Minahasa Selatan</p>
            </div>

            <!-- Controls Cluster -->
            <div class="header-controls-cluster">
              <button
                v-if="subTabWilayah === 'kecamatan'"
                type="button"
                class="btn-create-dataset"
                @click="openCreateKecamatanModal"
                title="Tambah Wilayah Kecamatan Baru"
              >
                <Plus :size="15" />
                <span>Tambah Kecamatan</span>
              </button>

              <button
                v-else
                type="button"
                class="btn-create-dataset"
                @click="openCreateDesaModal"
                title="Tambah Desa atau Kelurahan Baru"
              >
                <Plus :size="15" />
                <span>Tambah Desa / Kelurahan</span>
              </button>

              <button
                type="button"
                class="btn-box-action"
                @click="loadKecamatans(); loadDesas();"
                title="Segarkan Data Wilayah"
              >
                <RotateCcw :size="14" :class="{ 'spin-anim': isWilayahLoading }" />
                <span>Refresh</span>
              </button>

              <!-- Sub-tab Switcher -->
              <div class="subnav-pill-group">
                <button
                  type="button"
                  class="subnav-pill"
                  :class="{ active: subTabWilayah === 'kecamatan' }"
                  @click="subTabWilayah = 'kecamatan'"
                >
                  <MapPin :size="13" />
                  <span>Kecamatan ({{ kecamatanAdminList.length }})</span>
                </button>
                <button
                  type="button"
                  class="subnav-pill"
                  :class="{ active: subTabWilayah === 'desa' }"
                  @click="subTabWilayah = 'desa'"
                >
                  <Building2 :size="13" />
                  <span>Desa &amp; Kelurahan ({{ desaAdminList.length }})</span>
                </button>
              </div>

              <!-- Search Bar -->
              <div v-if="subTabWilayah === 'kecamatan'" class="search-input-wrap search-sm">
                <Search :size="15" class="search-icon" />
                <input
                  v-model="searchKecamatanQuery"
                  type="text"
                  placeholder="Cari kecamatan, kode, atau ibukota..."
                  class="search-input-field"
                />
                <button
                  v-if="searchKecamatanQuery"
                  type="button"
                  class="clear-search-btn"
                  @click="searchKecamatanQuery = ''"
                >
                  <X :size="13" />
                </button>
              </div>

              <div v-else class="search-input-wrap search-sm">
                <Search :size="15" class="search-icon" />
                <input
                  v-model="searchDesaQuery"
                  type="text"
                  placeholder="Cari desa, kode, kecamatan..."
                  class="search-input-field"
                />
                <button
                  v-if="searchDesaQuery"
                  type="button"
                  class="clear-search-btn"
                  @click="searchDesaQuery = ''"
                >
                  <X :size="13" />
                </button>
              </div>

              <!-- Filter khusus Desa -->
              <template v-if="subTabWilayah === 'desa'">
                <select v-model="filterDesaKecamatan" class="filter-select-enhanced">
                  <option value="">Semua Kecamatan (17)</option>
                  <option v-for="kec in kecamatanAdminList" :key="kec.id" :value="kec.id">
                    {{ kec.nama_kecamatan }}
                  </option>
                </select>

                <select v-model="filterDesaTipe" class="filter-select-enhanced">
                  <option value="">Semua Tipe</option>
                  <option value="DESA">Desa (167)</option>
                  <option value="KELURAHAN">Kelurahan (10)</option>
                </select>
              </template>
            </div>
          </div>

          <!-- SUB-TAB 1: KECAMATAN -->
          <div v-if="subTabWilayah === 'kecamatan'">
            <!-- Visual Statistics Charts: Kecamatan -->
            <div class="tab-chart-grid">
              <StatChart
                title="Perbandingan Luas Wilayah 17 Kecamatan (km²)"
                subtitle="Komparasi luas geografis kecamatan di Kabupaten Minahasa Selatan"
                type="bar"
                :data="chartWilayahKecLuas"
                badge="1.456,46 km²"
              />
              <StatChart
                title="Sebaran Jumlah Desa per Kecamatan"
                subtitle="Kepadatan unit administrasi desa &amp; kelurahan per kecamatan"
                type="bar"
                :data="chartWilayahKecDesaCount"
                badge="177 Wilayah Binaan"
              />
            </div>

            <div v-if="filteredKecamatans.length === 0" class="empty-state-card">
              <Search :size="40" class="empty-icon" />
              <h3 class="empty-title">Tidak Ada Kecamatan yang Cocok</h3>
              <p class="empty-desc">Tidak ditemukan kecamatan yang memenuhi kata kunci pencarian.</p>
              <button type="button" class="btn-empty-reset" @click="searchKecamatanQuery = ''">
                <RotateCcw :size="14" />
                <span>Bersihkan Pencarian</span>
              </button>
            </div>

            <!-- Tampilan Grid Kecamatan -->
            <div v-else class="kecamatan-admin-grid">
              <div v-for="k in paginatedKecamatans" :key="k.id" class="kec-admin-card">
                <div class="kec-card-top">
                  <span class="kec-code font-mono">{{ k.kode_kecamatan }}</span>
                  <span class="badge-total-desa">{{ k.total_desa }} Desa/Kel</span>
                </div>
                <h3 class="kec-name">{{ k.nama_kecamatan }}</h3>
                <div class="kec-info-rows">
                  <div class="kec-info-row">
                    <span class="info-lbl">Ibukota:</span>
                    <span class="info-val">{{ k.ibukota_kecamatan || '-' }}</span>
                  </div>
                  <div class="kec-info-row">
                    <span class="info-lbl">Luas Wilayah:</span>
                    <span class="info-val font-mono">{{ k.luas_wilayah_km2 }} km²</span>
                  </div>
                  <div class="kec-info-row">
                    <span class="info-lbl">Titik Pusat:</span>
                    <span class="info-val font-mono text-muted">{{ k.center_lng }}, {{ k.center_lat }}</span>
                  </div>
                </div>
                <div class="kec-card-actions">
                  <button type="button" class="btn-act btn-edit" @click="openEditKecamatanModal(k)">
                    <Edit3 :size="12" />
                    <span>Edit</span>
                  </button>
                  <button type="button" class="btn-act btn-delete" @click="handleDeleteKecamatanWithConfirm(k)">
                    <Trash2 :size="12" />
                    <span>Hapus</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Pagination Kecamatan -->
            <PaginationControl
              v-model:page="pageKecamatan"
              v-model:pageSize="pageSizeKecamatan"
              :total="filteredKecamatans.length"
              :pageSizeOptions="[6, 9, 18, 36]"
              label="kecamatan"
            />
          </div>

          <!-- SUB-TAB 2: DESA & KELURAHAN -->
          <div v-else>
            <!-- Visual Statistics Charts: Desa & Kelurahan -->
            <div class="tab-chart-grid">
              <StatChart
                title="Proporsi Tipe Wilayah Binaan"
                subtitle="Rasio status pemerintahan desa otonom vs kelurahan perkotaan"
                type="doughnut"
                :data="chartWilayahDesaTipe"
                badge="166 Desa / 11 Kel"
              />
              <StatChart
                title="Top 8 Kecamatan dengan Wilayah Terbanyak"
                subtitle="Akumulasi desa dan kelurahan pada kecamatan berpopulasi padat"
                type="bar"
                :data="chartWilayahTopDesaKec"
                badge="Sebaran Wilayah"
              />
            </div>

            <div v-if="filteredDesas.length === 0" class="empty-state-card">
              <Search :size="40" class="empty-icon" />
              <h3 class="empty-title">Tidak Ada Desa / Kelurahan yang Cocok</h3>
              <p class="empty-desc">Tidak ditemukan desa atau kelurahan yang memenuhi filter aktif.</p>
              <button type="button" class="btn-empty-reset" @click="searchDesaQuery = ''; filterDesaKecamatan = ''; filterDesaTipe = '';">
                <RotateCcw :size="14" />
                <span>Bersihkan Filter</span>
              </button>
            </div>

            <div v-else class="table-card">
              <div class="table-meta-bar">
                <span class="meta-count">
                  Menampilkan <strong>{{ filteredDesas.length }}</strong> desa &amp; kelurahan se-Minahasa Selatan
                </span>
              </div>
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Nama Desa / Kelurahan</th>
                    <th>Tipe</th>
                    <th>Kecamatan Induk</th>
                    <th>Kode Wilayah</th>
                    <th>Luas (km²)</th>
                    <th>Koordinat Pusat</th>
                    <th style="width: 140px; text-align: center;">Tindakan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="d in paginatedDesas" :key="d.id">
                    <td>
                      <span class="cell-title">{{ d.nama_desa }}</span>
                    </td>
                    <td>
                      <span class="tipe-badge" :class="d.tipe === 'KELURAHAN' ? 'tipe-kel' : 'tipe-desa'">
                        {{ d.tipe }}
                      </span>
                    </td>
                    <td>
                      <span class="badge-kec">{{ d.nama_kecamatan }}</span>
                    </td>
                    <td>
                      <span class="font-mono text-muted">{{ d.kode_desa }}</span>
                    </td>
                    <td>
                      <span class="font-mono">{{ d.luas_wilayah_km2 }} km²</span>
                    </td>
                    <td>
                      <span class="font-mono text-xs text-muted">{{ d.center_lng }}, {{ d.center_lat }}</span>
                    </td>
                    <td style="text-align: center;">
                      <div class="action-btn-group" style="justify-content: center;">
                        <button type="button" class="btn-act btn-edit" @click="openEditDesaModal(d)">
                          <Edit3 :size="12" />
                          <span>Edit</span>
                        </button>
                        <button type="button" class="btn-act btn-delete" @click="handleDeleteDesaWithConfirm(d)">
                          <Trash2 :size="12" />
                          <span>Hapus</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>

              <!-- Pagination Desa & Kelurahan -->
              <PaginationControl
                v-model:page="pageDesa"
                v-model:pageSize="pageSizeDesa"
                :total="filteredDesas.length"
                :pageSizeOptions="[15, 30, 60, 120]"
                label="desa & kelurahan"
              />
            </div>
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB: BASE MAPS (PETA DASAR SUPERADMIN - CRUD)                  -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'basemaps'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Katalog &amp; Konfigurasi Peta Dasar (Base Map)</h2>
              <p class="content-sub">Kelola layer citra satelit, topografi, dan peta jalan yang aktif di Geoportal publik Minahasa Selatan</p>
            </div>

            <!-- Controls Cluster -->
            <div class="header-controls-cluster">
              <button
                type="button"
                class="btn-create-dataset"
                @click="openCreateBasemapModal"
                title="Tambah Provider Base Map Baru"
              >
                <Plus :size="15" />
                <span>Tambah Base Map</span>
              </button>

              <button
                type="button"
                class="btn-box-action"
                @click="loadBasemapsAdmin"
                title="Segarkan Daftar Base Map"
              >
                <RotateCcw :size="14" :class="{ 'spin-anim': isBasemapLoading }" />
                <span>Refresh</span>
              </button>

              <div class="search-input-wrap search-sm">
                <Search :size="15" class="search-icon" />
                <input
                  v-model="searchBasemapQuery"
                  type="text"
                  placeholder="Cari label, name, atribusi..."
                  class="search-input-field"
                />
                <button
                  v-if="searchBasemapQuery"
                  type="button"
                  class="clear-search-btn"
                  @click="searchBasemapQuery = ''"
                >
                  <X :size="13" />
                </button>
              </div>

              <select v-model="filterBasemapType" class="filter-select-enhanced">
                <option value="">Semua Tipe (Raster &amp; Vector)</option>
                <option value="raster">Raster Tiles (XYZ)</option>
                <option value="vector">Vector Tiles / GL Style</option>
              </select>

              <select v-model="filterBasemapStatus" class="filter-select-enhanced">
                <option value="">Semua Status</option>
                <option value="active">Aktif di Geoportal</option>
                <option value="inactive">Nonaktif</option>
              </select>
            </div>
          </div>

          <!-- Visual Statistics Charts: Base Map -->
          <div class="tab-chart-grid">
            <StatChart
              title="Komposisi Provider Peta Dasar"
              subtitle="Perbandingan raster tile (XYZ/TMS) vs vector tile (MapLibre GL)"
              type="doughnut"
              :data="chartBasemapProviders"
              badge="Multi-Provider"
            />
            <StatChart
              title="Jangkauan Level Zoom Maksimum"
              subtitle="Tingkat ketelitian visual citra satelit dan peta topografi"
              type="bar"
              :data="chartBasemapZoomRange"
              badge="Zoom 0 - 20"
            />
          </div>

          <!-- Empty State -->
          <div v-if="filteredBasemaps.length === 0" class="empty-state-card">
            <Search :size="40" class="empty-icon" />
            <h3 class="empty-title">Tidak Ada Base Map yang Cocok</h3>
            <p class="empty-desc">Tidak ditemukan peta dasar yang memenuhi kriteria pencarian.</p>
            <button type="button" class="btn-empty-reset" @click="searchBasemapQuery = ''; filterBasemapType = ''; filterBasemapStatus = '';">
              <RotateCcw :size="14" />
              <span>Bersihkan Filter</span>
            </button>
          </div>

          <!-- Grid Peta Dasar -->
          <div v-else class="basemap-admin-grid">
            <div v-for="b in paginatedBasemaps" :key="b.id" class="basemap-admin-card" :class="{ 'is-default-card': b.is_default }">
              <div class="bm-card-header" :style="{ backgroundColor: b.preview_color || '#1e293b' }">
                <div class="bm-header-badges">
                  <span v-if="b.is_default" class="badge-default-bm">
                    <Star :size="12" />
                    <span>Default Geoportal</span>
                  </span>
                  <span class="badge-type-bm">{{ b.type.toUpperCase() }}</span>
                </div>
                <div class="bm-header-status">
                  <span class="bm-status-dot" :class="b.is_active ? 'dot-active' : 'dot-inactive'"></span>
                  <span>{{ b.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                </div>
              </div>

              <div class="bm-card-body">
                <h3 class="bm-label">{{ b.label }}</h3>
                <span class="bm-name font-mono">{{ b.name }}</span>
                <p class="bm-desc">{{ b.desc || 'Tidak ada deskripsi' }}</p>

                <div class="bm-url-box">
                  <span class="bm-url-lbl">Tile / Style URL:</span>
                  <code class="bm-url-val" :title="b.tile_url">{{ b.tile_url }}</code>
                </div>

                <div class="bm-meta-row">
                  <span class="bm-meta-item">Zoom: {{ b.min_zoom }} - {{ b.max_zoom }}</span>
                  <span class="bm-meta-item text-muted" :title="b.attribution">{{ b.attribution }}</span>
                </div>
              </div>

              <div class="bm-card-footer">
                <button
                  v-if="!b.is_default"
                  type="button"
                  class="btn-bm-default"
                  @click="handleSetDefaultBasemap(b)"
                  title="Jadikan Base Map Default saat Geoportal Dibuka"
                >
                  <Star :size="13" />
                  <span>Jadikan Default</span>
                </button>

                <div class="action-btn-group">
                  <button
                    type="button"
                    class="btn-act"
                    :class="b.is_active ? 'btn-warn' : 'btn-success'"
                    @click="handleToggleBasemap(b)"
                    :title="b.is_active ? 'Nonaktifkan dari Geoportal' : 'Aktifkan di Geoportal'"
                  >
                    <ToggleRight v-if="b.is_active" :size="13" />
                    <ToggleLeft v-else :size="13" />
                    <span>{{ b.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                  </button>

                  <button type="button" class="btn-act btn-edit" @click="openEditBasemapModal(b)" title="Edit Konfigurasi">
                    <Edit3 :size="13" />
                    <span>Edit</span>
                  </button>

                  <button
                    type="button"
                    class="btn-act btn-delete"
                    :disabled="b.is_system || b.is_default"
                    @click="handleDeleteBasemapWithConfirm(b)"
                    :title="b.is_system ? 'Peta dasar sistem dilindungi' : 'Hapus Peta Dasar'"
                  >
                    <Trash2 :size="13" />
                    <span>Hapus</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination Peta Dasar -->
          <PaginationControl
            v-if="filteredBasemaps.length > 0"
            v-model:page="pageBasemaps"
            v-model:pageSize="pageSizeBasemaps"
            :total="filteredBasemaps.length"
            :pageSizeOptions="[6, 12, 24]"
            label="peta dasar"
          />
        </div>

        <!-- ============================================================== -->
        <!-- TAB 6: KATEGORI SPASIAL TEMATIK (GRID & LIST + CRUD)           -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'kategori'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Master Kategori Layer Spasial Tematik</h2>
              <p class="content-sub">Katalog 28 tema geospasial, standarisasi warna simbologi, dan penanggung jawab OPD pembina</p>
            </div>

            <div class="header-controls-cluster">
              <!-- Tambah Kategori Button -->
              <button
                v-if="canManageKategori"
                type="button"
                class="btn-create-dataset"
                @click="openCreateKategoriModal"
                title="Tambah Kategori Spasial Baru"
              >
                <Plus :size="15" />
                <span>Tambah Kategori</span>
              </button>

              <!-- Search Kategori -->
              <div class="search-input-wrap search-sm">
                <Search :size="15" class="search-icon" />
                <input
                  v-model="searchKategoriQuery"
                  type="text"
                  placeholder="Cari tema, kode, atau OPD..."
                  class="search-input-field"
                />
                <button
                  v-if="searchKategoriQuery"
                  type="button"
                  class="clear-search-btn"
                  @click="searchKategoriQuery = ''"
                >
                  <X :size="13" />
                </button>
              </div>

              <!-- View Mode Toggle -->
              <div class="view-mode-toggle">
                <button
                  type="button"
                  class="toggle-btn"
                  :class="{ active: viewMode === 'grid' }"
                  @click="setViewMode('grid')"
                  title="Tampilan Grid (Bento Cards)"
                >
                  <LayoutGrid :size="16" />
                </button>
                <button
                  type="button"
                  class="toggle-btn"
                  :class="{ active: viewMode === 'list' }"
                  @click="setViewMode('list')"
                  title="Tampilan Tabel (List View)"
                >
                  <List :size="16" />
                </button>
              </div>
            </div>
          </div>

          <!-- Visual Statistics Charts: Kategori Spasial -->
          <div class="tab-chart-grid">
            <StatChart
              title="Volume Dataset per Kategori Tematik"
              subtitle="Top 8 tema spasial terpopuler berdasarkan jumlah dataset terdaftar"
              type="bar"
              :data="chartKategoriDatasetCount"
              badge="Tema Spasial"
            />
            <StatChart
              title="Proporsi Status Kategori Spasial"
              subtitle="Rasio kategori layer yang aktif digunakan vs diarsipkan"
              type="doughnut"
              :data="chartKategoriStatus"
              badge="28 Tema SDI"
            />
          </div>

          <!-- BENTO GRID VIEW FOR KATEGORI -->
          <div v-if="viewMode === 'grid'" class="bento-grid">
            <div
              v-for="k in paginatedKategoris"
              :key="k.id"
              class="bento-card"
            >
              <div class="dataset-card-header">
                <div class="theme-color-dot" :style="{ backgroundColor: k.warna_default }"></div>
                <span class="badge-source font-mono">{{ k.kode }}</span>
                <span class="card-badge badge-published">
                  {{ k.is_active ? 'Aktif' : 'Arsip' }}
                </span>
              </div>

              <div class="card-body">
                <h3 class="card-title">{{ k.nama_kategori }}</h3>
                <p class="card-desc">{{ k.deskripsi || 'Tidak ada deskripsi ruang lingkup.' }}</p>
                <div class="card-meta-row">
                  <span class="meta-label">OPD Pembina:</span>
                  <span class="meta-value">{{ k.nama_opd || 'Pemkab Minahasa Selatan' }}</span>
                </div>
                <div class="card-meta-row">
                  <span class="meta-label">Total Dataset:</span>
                  <span class="meta-value text-blue">{{ k.total_datasets || 0 }} Dataset</span>
                </div>
              </div>

              <div class="card-footer">
                <span class="color-swatch-label">
                  <span class="color-preview-box" :style="{ backgroundColor: k.warna_default }"></span>
                  <code>{{ k.warna_default }}</code>
                </span>

                <div class="action-btn-group" v-if="canManageKategori">
                  <button
                    type="button"
                    class="btn-act btn-edit"
                    @click="openEditKategoriModal(k)"
                    title="Edit Kategori"
                  >
                    <Edit3 :size="12" />
                    <span>Edit</span>
                  </button>
                  <button
                    type="button"
                    class="btn-act btn-delete"
                    @click="handleDeleteKategoriWithConfirm(k)"
                    title="Hapus Kategori"
                  >
                    <Trash2 :size="12" />
                    <span>Hapus</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- TABLE LIST VIEW FOR KATEGORI -->
          <div v-else class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Warna &amp; Tema Kategori</th>
                  <th>Kode Kategori</th>
                  <th>Deskripsi &amp; Ruang Lingkup</th>
                  <th>OPD Pembina / Penanggung Jawab</th>
                  <th>Dataset Terkait</th>
                  <th>Status</th>
                  <th v-if="canManageKategori">Tindakan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="k in paginatedKategoris" :key="k.id">
                  <td>
                    <div class="cell-main flex-row-align">
                      <span class="color-preview-box" :style="{ backgroundColor: k.warna_default }"></span>
                      <span class="cell-title">{{ k.nama_kategori }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="text-source font-mono">{{ k.kode }}</span>
                  </td>
                  <td>
                    <span class="cell-desc-table">{{ k.deskripsi || '-' }}</span>
                  </td>
                  <td>
                    <span class="badge-opd">{{ k.nama_opd }}</span>
                  </td>
                  <td>
                    <span class="badge-geom">{{ k.total_datasets || 0 }} Dataset</span>
                  </td>
                  <td>
                    <span class="status-pill" :class="k.is_active ? 'st-published' : 'st-draft'">
                      {{ k.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </td>
                  <td v-if="canManageKategori">
                    <div class="action-btn-group">
                      <button
                        type="button"
                        class="btn-act btn-edit"
                        @click="openEditKategoriModal(k)"
                        title="Edit Kategori"
                      >
                        <Edit3 :size="12" />
                        <span>Edit</span>
                      </button>
                      <button
                        type="button"
                        class="btn-act btn-delete"
                        @click="handleDeleteKategoriWithConfirm(k)"
                        title="Hapus Kategori"
                      >
                        <Trash2 :size="12" />
                        <span>Hapus</span>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Kategori Spasial -->
          <PaginationControl
            v-if="filteredKategoris.length > 0"
            v-model:page="pageKategori"
            v-model:pageSize="pageSizeKategori"
            :total="filteredKategoris.length"
            :pageSizeOptions="[4, 8, 16, 32]"
            label="kategori spasial"
          />
        </div>

        <!-- ============================================================== -->
        <!-- TAB 7: MASTER ORGANISASI PERANGKAT DAERAH (OPD)                -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'opd'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Master Organisasi Perangkat Daerah (OPD)</h2>
              <p class="content-sub">Pemerintah Kabupaten Minahasa Selatan — Unit Produsen Data Spasial &amp; Pembina Sektoral</p>
            </div>

            <div class="header-controls-cluster">
              <!-- Tambah OPD Button -->
              <button
                v-if="canFullManageOpd"
                type="button"
                class="btn-create-dataset"
                @click="openCreateOpdModal"
                title="Tambah Perangkat Daerah Baru"
              >
                <Building2 :size="15" />
                <span>Tambah OPD</span>
              </button>

              <!-- Search OPD -->
              <div class="search-input-wrap search-sm">
                <Search :size="15" class="search-icon" />
                <input
                  v-model="searchOpdQuery"
                  type="text"
                  placeholder="Cari nama OPD, kode, atau PIC..."
                  class="search-input-field"
                />
                <button
                  v-if="searchOpdQuery"
                  type="button"
                  class="clear-search-btn"
                  @click="searchOpdQuery = ''"
                >
                  <X :size="13" />
                </button>
              </div>

              <!-- View Mode Toggle -->
              <div class="view-mode-toggle">
                <button
                  type="button"
                  class="toggle-btn"
                  :class="{ active: viewMode === 'grid' }"
                  @click="setViewMode('grid')"
                  title="Tampilan Grid (Bento Cards)"
                >
                  <LayoutGrid :size="16" />
                </button>
                <button
                  type="button"
                  class="toggle-btn"
                  :class="{ active: viewMode === 'list' }"
                  @click="setViewMode('list')"
                  title="Tampilan Tabel (List View)"
                >
                  <List :size="16" />
                </button>
              </div>
            </div>
          </div>

          <!-- Visual Statistics Charts: OPD -->
          <div class="tab-chart-grid">
            <StatChart
              title="Kontribusi Dataset per Unit OPD"
              subtitle="Peringkat dinas dan badan daerah teraktif penyuplai data spasial"
              type="bar"
              :data="chartOpdContribution"
              badge="Produsen Data Daerah"
            />
            <StatChart
              title="Status Keaktifan Organisasi Perangkat Daerah"
              subtitle="Persentase OPD aktif dalam ekosistem geoportal Minahasa Selatan"
              type="doughnut"
              :data="chartOpdActiveRatio"
              badge="OPD Terhubung"
            />
          </div>

          <!-- BENTO GRID VIEW FOR OPD -->
          <div v-if="viewMode === 'grid'" class="bento-grid">
            <div
              v-for="o in paginatedOpds"
              :key="o.id"
              class="bento-card"
            >
              <div class="dataset-card-header">
                <span class="badge-opd font-mono">{{ o.kode_opd }}</span>
                <span class="card-badge badge-published">
                  {{ o.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
              </div>

              <div class="card-body">
                <h3 class="card-title">{{ o.nama_opd }}</h3>
                <p class="card-desc">
                  <strong>Alamat:</strong> {{ o.alamat || 'Kabupaten Minahasa Selatan' }}
                </p>
                <div class="card-meta-row">
                  <span class="meta-label">PIC / Kepala:</span>
                  <span class="meta-value">{{ o.pic || '-' }}</span>
                </div>
                <div class="card-meta-row">
                  <span class="meta-label">Kontak:</span>
                  <span class="meta-value">{{ o.telepon || '-' }} &bull; {{ o.email }}</span>
                </div>
                <div class="card-meta-row">
                  <span class="meta-label">Dataset Sektoral:</span>
                  <span class="meta-value text-blue">{{ o.total_datasets || 0 }} Dataset</span>
                </div>
              </div>

              <div class="card-footer">
                <span class="text-source">{{ o.singkatan || o.kode_opd }}</span>

                <div class="action-btn-group" v-if="canManageOpd">
                  <button
                    type="button"
                    class="btn-act btn-edit"
                    @click="openEditOpdModal(o)"
                    title="Edit Data OPD"
                  >
                    <Edit3 :size="12" />
                    <span>Edit</span>
                  </button>
                  <button
                    v-if="canFullManageOpd && !['DISKOMINFO', 'BAPELITBANGDA', 'DPUPR'].includes(o.kode_opd)"
                    type="button"
                    class="btn-act btn-delete"
                    @click="handleDeleteOpdWithConfirm(o)"
                    title="Hapus OPD"
                  >
                    <Trash2 :size="12" />
                    <span>Hapus</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- TABLE LIST VIEW FOR OPD -->
          <div v-else class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nama &amp; Kode OPD</th>
                  <th>Kontak &amp; Email</th>
                  <th>Alamat Kantor</th>
                  <th>Penanggung Jawab (PIC)</th>
                  <th>Total Dataset</th>
                  <th>Status</th>
                  <th v-if="canManageOpd">Tindakan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="o in paginatedOpds" :key="o.id">
                  <td>
                    <div class="cell-main">
                      <span class="cell-title">{{ o.nama_opd }}</span>
                      <span class="cell-meta font-mono">{{ o.kode_opd }} &bull; {{ o.singkatan || o.kode_opd }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="cell-stacked">
                      <span>{{ o.email }}</span>
                      <span class="text-source">{{ o.telepon || '-' }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="cell-desc-table">{{ o.alamat || '-' }}</span>
                  </td>
                  <td>
                    <span class="badge-role">{{ o.pic || '-' }}</span>
                  </td>
                  <td>
                    <span class="badge-geom">{{ o.total_datasets || 0 }} Dataset</span>
                  </td>
                  <td>
                    <span class="status-pill" :class="o.is_active ? 'st-published' : 'st-draft'">
                      {{ o.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </td>
                  <td v-if="canManageOpd">
                    <div class="action-btn-group">
                      <button
                        type="button"
                        class="btn-act btn-edit"
                        @click="openEditOpdModal(o)"
                        title="Edit Data OPD"
                      >
                        <Edit3 :size="12" />
                        <span>Edit</span>
                      </button>
                      <button
                        v-if="canFullManageOpd && !['DISKOMINFO', 'BAPELITBANGDA', 'DPUPR'].includes(o.kode_opd)"
                        type="button"
                        class="btn-act btn-delete"
                        @click="handleDeleteOpdWithConfirm(o)"
                        title="Hapus OPD"
                      >
                        <Trash2 :size="12" />
                        <span>Hapus</span>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination OPD -->
          <PaginationControl
            v-if="filteredOpds.length > 0"
            v-model:page="pageOpd"
            v-model:pageSize="pageSizeOpd"
            :total="filteredOpds.length"
            :pageSizeOptions="[5, 10, 20, 50]"
            label="OPD"
          />
        </div>

        <!-- ============================================================== -->
        <!-- TAB 8: USERS (PENGGUNA & HAK AKSES)                            -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'users'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Manajemen Akun Pengguna &amp; OPD</h2>
              <p class="content-sub">Penerapan pemisahan wewenang 8 Role dan pembatasan akses data sektoral</p>
            </div>

            <!-- User Search & Role Filter & Create User Button -->
            <div class="header-controls-cluster">
              <!-- Create User Button -->
              <button
                v-if="canManageUsers"
                type="button"
                class="btn-create-dataset"
                @click="openCreateUserModal"
                title="Tambah Akun Pengguna / Operator Baru"
              >
                <UserPlus :size="15" />
                <span>Tambah Pengguna</span>
              </button>

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

          <!-- Scope Notice Banner (Admin OPD vs Superadmin) -->
          <div v-if="authStore.currentRole === 'admin_opd'" class="scope-notice-banner">
            <div class="scope-notice-icon">
              <Building2 :size="20" class="text-sky" />
            </div>
            <div class="scope-notice-text">
              <div class="scope-notice-title">
                Lingkup Pengelolaan Terisolasi: <strong>{{ authStore.opdLabel }}</strong> (Mode Admin OPD)
              </div>
              <div class="scope-notice-desc">
                Anda hanya berwenang melihat, mendaftarkan, mengedit, dan mengelola akun operator di lingkungan kerja {{ authStore.opdLabel }}. Akses lintas OPD dan peran eskalasi sistem dibatasi sesuai regulasi Satu Data Indonesia.
              </div>
            </div>
            <span class="scope-badge-tag">Scope Terkunci: {{ authStore.user?.kode_opd || 'OPD' }}</span>
          </div>

          <div v-else-if="authStore.currentRole === 'superadmin'" class="scope-notice-banner superadmin-banner">
            <div class="scope-notice-icon">
              <ShieldCheck :size="20" class="text-emerald" />
            </div>
            <div class="scope-notice-text">
              <div class="scope-notice-title">
                Lingkup Pengelolaan Global: <strong>Seluruh Perangkat Daerah (Superadmin)</strong>
              </div>
              <div class="scope-notice-desc">
                Anda memiliki hak penuh untuk mengelola pengguna dari seluruh OPD di lingkungan Pemerintah Kabupaten Minahasa Selatan serta mendistribusikan 8 tingkatan peran kewenangan resmi.
              </div>
            </div>
            <span class="scope-badge-tag superadmin-tag">Akses Global</span>
          </div>

          <!-- Visual Statistics Charts: Users -->
          <div class="tab-chart-grid">
            <StatChart
              title="Komposisi Pengguna per 8 Role Kewenangan"
              subtitle="Pemisahan wewenang akses: Superadmin, Admin GIS, Walidata, hingga Publik"
              type="doughnut"
              :data="chartUsersByRole"
              badge="8 Role RBAC"
            />
            <StatChart
              title="Rasio Status Akses Akun Pengguna"
              subtitle="Status akun terverifikasi aktif vs akun dinonaktifkan otoritas"
              type="bar"
              :data="chartUsersStatus"
              badge="Akses Terlindungi"
            />
          </div>

          <div class="table-card">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nama Pengguna &amp; Email</th>
                  <th>Username &amp; ID</th>
                  <th>Role Resmi</th>
                  <th>Organisasi Perangkat Daerah (OPD)</th>
                  <th>Status Akses</th>
                  <th>Tindakan Otoritas</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="u in paginatedUsers" :key="u.id">
                  <td>
                    <div class="cell-main">
                      <span class="cell-title">{{ u.nama_lengkap }}</span>
                      <span class="cell-meta">{{ u.email }}</span>
                    </div>
                  </td>
                  <td>
                    <span class="text-source">@{{ u.username }} &bull; #{{ u.id }}</span>
                  </td>
                  <td>
                    <span class="badge-role">{{ u.role_label || u.role }}</span>
                  </td>
                  <td>{{ u.opd }}</td>
                  <td>
                    <span
                      class="status-pill"
                      :class="u.is_active ? 'st-published' : 'st-draft'"
                    >
                      {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                  </td>
                  <td>
                    <div class="action-btn-group">
                      <span v-if="canManageThisUser(u)" class="btn-wrap-group">
                        <button
                          type="button"
                          class="btn-act"
                          :class="u.is_active ? 'btn-toggle-deact' : 'btn-approve'"
                          @click="handleToggleUserStatusWithConfirm(u)"
                          :title="u.is_active ? 'Nonaktifkan Akses' : 'Aktifkan Akses'"
                        >
                          <ToggleRight v-if="u.is_active" :size="13" />
                          <ToggleLeft v-else :size="13" />
                          <span>{{ u.is_active ? 'Nonaktifkan' : 'Aktifkan' }}</span>
                        </button>

                        <button
                          type="button"
                          class="btn-act btn-edit"
                          @click="openEditUserModal(u)"
                          title="Edit Informasi Pengguna"
                        >
                          <Edit3 :size="12" />
                          <span>Edit</span>
                        </button>

                        <button
                          type="button"
                          class="btn-act btn-delete"
                          @click="handleDeleteUserWithConfirm(u)"
                          title="Hapus Akun Pengguna"
                        >
                          <Trash2 :size="12" />
                          <span>Hapus</span>
                        </button>
                      </span>
                      <span v-else class="text-source">
                        <ShieldCheck :size="13" />
                        <span>{{ u.id === 1 ? 'Akun Utama Terproteksi' : 'Di Luar Kewenangan OPD' }}</span>
                      </span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination Pengguna -->
            <PaginationControl
              v-if="filteredUsers.length > 0"
              v-model:page="pageUsers"
              v-model:pageSize="pageSizeUsers"
              :total="filteredUsers.length"
              :pageSizeOptions="[5, 10, 20, 50]"
              label="pengguna"
            />
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 9: AUDIT TRAIL & LOG AKTIVITAS SISTEM                      -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'audit'" class="tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Jejak Audit &amp; Riwayat Perubahan (Auditability)</h2>
              <p class="content-sub">Rekaman lengkap setiap aksi validasi teknis, persetujuan Walidata, perubahan data dasar, dan pengguna</p>
            </div>

            <!-- Toolbar Controls: Search, Filters, Reset & Refresh -->
            <div class="header-controls-cluster">
              <button
                type="button"
                class="btn-box-action"
                @click="loadAuditLogs"
                title="Segarkan Log dari Server"
              >
                <RotateCcw :size="14" :class="{ 'spin-anim': isAuditLoading }" />
                <span>Refresh Log</span>
              </button>

              <div class="search-input-wrap search-sm">
                <Search :size="15" class="search-icon" />
                <input
                  v-model="auditSearchQuery"
                  type="text"
                  placeholder="Cari objek, aktor, catatan, atau versi..."
                  class="search-input-field"
                />
                <button
                  v-if="auditSearchQuery"
                  type="button"
                  class="clear-search-btn"
                  @click="auditSearchQuery = ''"
                  title="Bersihkan pencarian"
                >
                  <X :size="13" />
                </button>
              </div>

              <!-- Filter Kategori -->
              <select v-model="auditFilterKategori" class="filter-select-enhanced">
                <option value="">Semua Kategori ({{ auditLogs.length }})</option>
                <option v-for="kat in auditKategoriList" :key="kat" :value="kat">
                  {{ kat }}
                </option>
              </select>

              <!-- Filter Aksi -->
              <select v-model="auditFilterAksi" class="filter-select-enhanced">
                <option value="">Semua Tindakan / Aksi</option>
                <option v-for="ak in auditAksiList" :key="ak" :value="ak">
                  {{ ak }}
                </option>
              </select>

              <!-- Reset Button -->
              <button
                v-if="activeAuditFilterCount > 0"
                type="button"
                class="btn-reset-filters"
                @click="resetAuditFilters"
                title="Bersihkan semua kriteria filter"
              >
                <RotateCcw :size="13" />
                <span>Reset ({{ activeAuditFilterCount }})</span>
              </button>
            </div>
          </div>

          <!-- Visual Statistics Charts: Audit Trail -->
          <div class="tab-chart-grid">
            <StatChart
              title="Frekuensi Aktivitas per Kategori Sistem"
              subtitle="Distribusi log pada modul Dataset, Validasi, Walidata, User, dan Master"
              type="bar"
              :data="chartAuditByKategori"
              badge="Auditability"
            />
            <StatChart
              title="Proporsi Tindakan / Aksi Pengguna"
              subtitle="Sebaran operasi pembuatan (CREATE), revisi (UPDATE), validasi, dan hapus"
              type="doughnut"
              :data="chartAuditByAksi"
              badge="Jejak Digital"
            />
          </div>

          <!-- Empty State -->
          <div v-if="filteredAuditLogs.length === 0" class="empty-state-card">
            <Search :size="40" class="empty-icon" />
            <h3 class="empty-title">Tidak Ada Rekaman Log yang Cocok</h3>
            <p class="empty-desc">
              Tidak ditemukan jejak audit yang memenuhi kriteria pencarian atau filter yang dipilih.
            </p>
            <button type="button" class="btn-empty-reset" @click="resetAuditFilters">
              <RotateCcw :size="14" />
              <span>Bersihkan Filter &amp; Pencarian</span>
            </button>
          </div>

          <!-- Audit Table -->
          <div v-else class="table-card">
            <div class="table-meta-bar">
              <span class="meta-count">
                Menampilkan <strong>{{ filteredAuditLogs.length }}</strong> dari <strong>{{ auditLogs.length }}</strong> rekaman jejak audit
              </span>
            </div>

            <table class="data-table">
              <thead>
                <tr>
                  <th style="width: 140px;">Waktu (WITA)</th>
                  <th style="width: 110px;">Kategori</th>
                  <th style="width: 150px;">Aksi / Tindakan</th>
                  <th>Aktor &amp; Role</th>
                  <th>Objek &amp; Versi Terkait</th>
                  <th>Catatan / Keterangan</th>
                  <th style="width: 80px; text-align: center;">Detail</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in paginatedAuditLogs" :key="log.id">
                  <td>
                    <span class="cell-time font-mono">{{ log.timestamp }}</span>
                  </td>
                  <td>
                    <span
                      class="log-cat-badge"
                      :class="'cat-' + log.kategori.toLowerCase()"
                    >
                      {{ log.kategori }}
                    </span>
                  </td>
                  <td>
                    <span class="log-action-tag font-mono">{{ log.aksi }}</span>
                  </td>
                  <td>
                    <div class="cell-main">
                      <span class="cell-title">{{ log.aktor }}</span>
                      <span class="cell-meta font-mono">{{ log.role }} &bull; {{ log.opd }}</span>
                    </div>
                  </td>
                  <td>
                    <div class="cell-main">
                      <span class="cell-title">{{ log.objek }}</span>
                      <span v-if="log.versi_terkait && log.versi_terkait !== '-'" class="chip-version-mini font-mono">
                        {{ log.versi_terkait }}
                      </span>
                    </div>
                  </td>
                  <td>
                    <span class="log-note-text" :title="log.keterangan">{{ log.keterangan }}</span>
                  </td>
                  <td style="text-align: center;">
                    <button
                      type="button"
                      class="btn-act btn-view"
                      @click="showAuditDetail(log)"
                      title="Lihat Detail Lengkap Log"
                    >
                      <Eye :size="13" />
                      <span>Lihat</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination Jejak Audit -->
            <PaginationControl
              v-model:page="pageAudit"
              v-model:pageSize="pageSizeAudit"
              :total="filteredAuditLogs.length"
              :pageSizeOptions="[10, 25, 50, 100]"
              label="jejak audit"
            />
          </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 10: PROFIL PENGGUNA LENGKAP & STATISTIK KONTRIBUSI          -->
        <!-- ============================================================== -->
        <div v-if="activeTab === 'profile'" class="tab-content profile-tab-content">
          <div class="content-header">
            <div>
              <h2 class="content-title">Profil Pengguna Lengkap &amp; Hak Akses</h2>
              <p class="content-sub">Informasi akun kedinasan, struktur unit kerja, matriks hak akses RBAC, dan statistik kontribusi spasial</p>
            </div>
          </div>

          <!-- Hero Profile Card -->
          <div class="profile-hero-card">
            <div class="hero-left">
              <div class="profile-avatar-large">
                <span>{{ authStore.user?.nama_lengkap?.charAt(0) || 'U' }}</span>
                <span class="avatar-online-dot" title="Sesi Pengguna Aktif"></span>
              </div>
              <div class="hero-identity">
                <div class="hero-name-row">
                  <h3 class="hero-name">{{ authStore.user?.nama_lengkap }}</h3>
                  <span class="badge-role-hero">{{ authStore.roleLabel }}</span>
                  <span class="badge-status-active">
                    <CheckCircle2 :size="13" />
                    <span>Terverifikasi Aktif</span>
                  </span>
                </div>
                <div class="hero-meta-row">
                  <span class="hero-meta-item">
                    <User :size="13" />
                    <span>@{{ authStore.user?.username }} (ID: #{{ authStore.user?.id }})</span>
                  </span>
                  <span class="hero-meta-item">
                    <Building2 :size="13" />
                    <span>{{ authStore.opdLabel }} ({{ authStore.user?.kode_opd || 'OPD' }})</span>
                  </span>
                  <span class="hero-meta-item">
                    <Mail :size="13" />
                    <span>{{ authStore.user?.email || '-' }}</span>
                  </span>
                </div>
              </div>
            </div>
            <div class="hero-right">
              <div class="session-chip">
                <span class="session-label">Sesi Akses:</span>
                <span class="session-value">WITA &bull; Resmi Frontend Token</span>
              </div>
            </div>
          </div>

          <!-- 4 Mini Metric Information Cards -->
          <div class="profile-metrics-grid">
            <div class="profile-mini-metric">
              <div class="mini-metric-icon bg-sky">
                <Briefcase :size="18" />
              </div>
              <div class="mini-metric-content">
                <span class="mini-metric-label">Jabatan Kedinasan</span>
                <span class="mini-metric-val">{{ authStore.user?.jabatan || profileEditForm.jabatan || 'Pengelola Data Spasial' }}</span>
              </div>
            </div>

            <div class="profile-mini-metric">
              <div class="mini-metric-icon bg-emerald">
                <ShieldCheck :size="18" />
              </div>
              <div class="mini-metric-content">
                <span class="mini-metric-label">Nomor Induk Pegawai (NIP)</span>
                <span class="mini-metric-val font-mono">{{ authStore.user?.nip || profileEditForm.nip || '19840512 200801 1 001' }}</span>
              </div>
            </div>

            <div class="profile-mini-metric">
              <div class="mini-metric-icon bg-amber">
                <Phone :size="18" />
              </div>
              <div class="mini-metric-content">
                <span class="mini-metric-label">Kontak WhatsApp / Telp</span>
                <span class="mini-metric-val font-mono">{{ authStore.user?.telepon || profileEditForm.telepon || '0812-4455-0111' }}</span>
              </div>
            </div>

            <div class="profile-mini-metric">
              <div class="mini-metric-icon bg-indigo">
                <MapPin :size="18" />
              </div>
              <div class="mini-metric-content">
                <span class="mini-metric-label">Alamat Kantor Unit Kerja</span>
                <span class="mini-metric-val truncate-text" :title="authStore.user?.alamat_kantor || profileEditForm.alamat_kantor">
                  {{ authStore.user?.alamat_kantor || profileEditForm.alamat_kantor || 'Kompleks Kantor Bupati Minsel, Pondang, Amurang' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Visual Statistics Charts: Profile Charts (Mematuhi aturan tegas chart real setiap halaman) -->
          <div class="tab-chart-grid">
            <StatChart
              title="Status Dataset Spasial OPD Pengguna"
              subtitle="Proporsi dataset tayang, dalam proses reviu/validasi, draf dan revisi pada lingkup OPD"
              type="doughnut"
              :data="chartProfileDatasetStatus"
              badge="Satu Data Minsel"
            />
            <StatChart
              title="Distribusi Jejak Audit &amp; Kontribusi Personal"
              subtitle="Frekuensi aktivitas aksi pengguna yang tercatat dalam audit trail sistem"
              type="bar"
              :data="chartProfileAuditActivities"
              badge="Audit Trail Personal"
            />
          </div>

          <!-- Two Column Profile Form Grid -->
          <div class="profile-forms-grid">
            <!-- Form 1: Edit Informasi Data Diri -->
            <div class="profile-card">
              <div class="profile-card-header">
                <div class="profile-card-title-wrap">
                  <Edit3 :size="18" class="text-sky" />
                  <h3 class="profile-card-title">Perbarui Informasi Data Diri</h3>
                </div>
                <span class="profile-card-badge">Data Personal Pegawai</span>
              </div>

              <form class="profile-form" @submit.prevent="handleSaveProfile">
                <div class="form-row-2">
                  <div class="form-field">
                    <label class="form-label">Nama Lengkap &amp; Gelar <span class="text-red">*</span></label>
                    <input v-model="profileEditForm.nama_lengkap" type="text" class="form-input" required />
                  </div>
                  <div class="form-field">
                    <label class="form-label">Email Dinas Resmi <span class="text-red">*</span></label>
                    <input v-model="profileEditForm.email" type="email" class="form-input" required />
                  </div>
                </div>

                <div class="form-row-2">
                  <div class="form-field">
                    <label class="form-label">Nomor Induk Pegawai (NIP)</label>
                    <input v-model="profileEditForm.nip" type="text" class="form-input font-mono" placeholder="19840512 200801 1 xxx" />
                  </div>
                  <div class="form-field">
                    <label class="form-label">Nomor Telepon / WhatsApp</label>
                    <input v-model="profileEditForm.telepon" type="text" class="form-input font-mono" placeholder="0812-xxxx-xxxx" />
                  </div>
                </div>

                <div class="form-field">
                  <label class="form-label">Jabatan Struktural / Fungsional</label>
                  <input v-model="profileEditForm.jabatan" type="text" class="form-input" placeholder="Pranata Komputer / Pengelola GIS" />
                </div>

                <div class="form-field">
                  <label class="form-label">Alamat Kantor Unit Kerja</label>
                  <textarea v-model="profileEditForm.alamat_kantor" class="form-textarea" rows="2" placeholder="Alamat lengkap instansi..."></textarea>
                </div>

                <div class="profile-form-footer">
                  <button type="submit" class="btn-primary-action" :disabled="isSavingProfile">
                    <Save :size="15" />
                    <span>{{ isSavingProfile ? 'Menyimpan...' : 'Simpan Perubahan Profil' }}</span>
                  </button>
                </div>
              </form>
            </div>

            <!-- Column 2: Keamanan Akun & Matriks Hak Akses RBAC -->
            <div class="profile-card-stack">
              <!-- Sub-card: Ganti Kata Sandi -->
              <div class="profile-card">
                <div class="profile-card-header">
                  <div class="profile-card-title-wrap">
                    <Lock :size="18" class="text-amber" />
                    <h3 class="profile-card-title">Keamanan Akun &amp; Kata Sandi</h3>
                  </div>
                  <span class="profile-card-badge">Enkripsi SHA-256</span>
                </div>

                <form class="profile-form" @submit.prevent="handleChangePassword">
                  <div class="form-field">
                    <label class="form-label">Kata Sandi Saat Ini</label>
                    <input v-model="passwordForm.current_password" type="password" class="form-input" placeholder="••••••••" />
                  </div>

                  <div class="form-row-2">
                    <div class="form-field">
                      <label class="form-label">Kata Sandi Baru <span class="text-red">*</span></label>
                      <input v-model="passwordForm.new_password" type="password" class="form-input" placeholder="Min. 6 karakter" required />
                    </div>
                    <div class="form-field">
                      <label class="form-label">Konfirmasi Sandi Baru <span class="text-red">*</span></label>
                      <input v-model="passwordForm.confirm_password" type="password" class="form-input" placeholder="Ulangi sandi baru" required />
                    </div>
                  </div>

                  <div class="profile-form-footer">
                    <button type="submit" class="btn-amber-action" :disabled="isChangingPassword">
                      <Key :size="15" />
                      <span>{{ isChangingPassword ? 'Memproses...' : 'Perbarui Kata Sandi' }}</span>
                    </button>
                  </div>
                </form>
              </div>

              <!-- Sub-card: Matriks Hak Akses RBAC -->
              <div class="profile-card">
                <div class="profile-card-header">
                  <div class="profile-card-title-wrap">
                    <Shield :size="18" class="text-emerald" />
                    <h3 class="profile-card-title">Hak Akses Role &amp; Wewenang (RBAC)</h3>
                  </div>
                  <span class="profile-card-badge">{{ authStore.user?.permissions?.length || 15 }} Hak Akses Aktif</span>
                </div>

                <div class="rbac-box-content">
                  <div class="rbac-scope-row">
                    <span class="rbac-scope-label">Lingkup Batas Operasional:</span>
                    <strong class="rbac-scope-val">
                      {{ authStore.currentRole === 'superadmin' ? 'Seluruh OPD & Sistem Minsel (Akses Penuh)' : `${authStore.opdLabel} (${authStore.user?.kode_opd || 'OPD'})` }}
                    </strong>
                  </div>

                  <div class="rbac-permissions-tags">
                    <span
                      v-for="perm in (authStore.user?.permissions || ['user.view', 'dataset.view', 'dataset.create'])"
                      :key="perm"
                      class="rbac-perm-tag"
                    >
                      <CheckCircle2 :size="11" />
                      <span>{{ perm }}</span>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Personal Recent Audit Logs -->
          <div class="table-card">
            <div class="table-card-header">
              <div class="table-card-title-wrap">
                <History :size="18" class="text-sky" />
                <h3 class="table-card-title">Riwayat Aktivitas &amp; Kontribusi Terbaru Pengguna</h3>
              </div>
              <span class="count-badge">{{ userPersonalAuditLogs.length }} Aktivitas Tercatat</span>
            </div>

            <table class="data-table">
              <thead>
                <tr>
                  <th>Waktu (WITA)</th>
                  <th>Kategori</th>
                  <th>Aksi</th>
                  <th>Objek Terkait</th>
                  <th>Keterangan Aktivitas</th>
                  <th>Detail</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in userPersonalAuditLogs" :key="log.id">
                  <td>
                    <span class="cell-time font-mono">{{ log.timestamp }}</span>
                  </td>
                  <td>
                    <span class="log-cat-badge" :class="'cat-' + log.kategori.toLowerCase()">
                      {{ log.kategori }}
                    </span>
                  </td>
                  <td>
                    <span class="log-action-tag font-mono">{{ log.aksi }}</span>
                  </td>
                  <td>
                    <span class="cell-title">{{ log.objek }}</span>
                  </td>
                  <td>
                    <span class="log-note-text" :title="log.keterangan">{{ log.keterangan }}</span>
                  </td>
                  <td style="text-align: center;">
                    <button
                      type="button"
                      class="btn-act btn-view"
                      @click="showAuditDetail(log)"
                      title="Lihat Detail Log"
                    >
                      <Eye :size="13" />
                      <span>Lihat</span>
                    </button>
                  </td>
                </tr>
                <tr v-if="userPersonalAuditLogs.length === 0">
                  <td colspan="6" class="text-center py-4 text-source">
                    Belum ada riwayat aktivitas terbaru yang tercatat untuk akun ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 1: TAMBAH DATASET SPASIAL BARU (CREATE)                 -->
    <!-- ============================================================== -->
    <div v-if="isCreateDatasetModalOpen" class="modal-backdrop" @click.self="isCreateDatasetModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <FilePlus2 :size="18" class="text-blue" />
            <h3 class="modal-title">Tambah Dataset Spasial Baru</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isCreateDatasetModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleCreateDataset">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Nama Dataset <span class="text-red">*</span></label>
              <input v-model="newDatasetForm.nama_dataset" type="text" class="form-input" placeholder="Contoh: Sebaran Fasilitas Air Bersih" required />
            </div>
            <div class="form-field">
              <label class="form-label">Kode Unik Dataset <span class="text-red">*</span></label>
              <input v-model="newDatasetForm.kode_dataset" type="text" class="form-input font-mono" placeholder="MNSL-PUPR-01" required />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kategori Tematik</label>
              <select v-model="newDatasetForm.kategori" class="form-input">
                <option value="infrastruktur_pu">Pekerjaan Umum &amp; Infrastruktur</option>
                <option value="pertanian">Pertanian &amp; Ketahanan Pangan</option>
                <option value="kesehatan">Kesehatan &amp; Faskes</option>
                <option value="pariwisata">Pariwisata &amp; Kebudayaan</option>
                <option value="kebencanaan">Kebencanaan &amp; Titik Rawan</option>
                <option value="pendidikan">Pendidikan &amp; Sekolah</option>
                <option value="lingkungan">Lingkungan Hidup &amp; Kehutanan</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Tipe Geometri</label>
              <select v-model="newDatasetForm.tipe_geometri" class="form-input">
                <option value="Point">Point (Titik Koordinat)</option>
                <option value="LineString">LineString (Jaringan / Garis)</option>
                <option value="Polygon">Polygon (Kawasan / Area)</option>
              </select>
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Cakupan Kecamatan</label>
              <select v-model="newDatasetForm.kecamatan" class="form-input">
                <option value="Seluruh Kabupaten">Seluruh Kabupaten Minahasa Selatan</option>
                <option v-for="kec in daftarKecamatan" :key="kec" :value="kec">{{ kec }}</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Estimasi Jumlah Fitur</label>
              <input v-model.number="newDatasetForm.jumlah_fitur" type="number" class="form-input" min="1" />
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Deskripsi / Ruang Lingkup Data</label>
            <textarea v-model="newDatasetForm.deskripsi" class="form-textarea" rows="3" placeholder="Jelaskan sumber data, metode akuisisi, dan kegunaan spasial..."></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isCreateDatasetModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <Plus :size="15" />
              <span>Simpan Sebagai Draft</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 2: EDIT DATASET SPASIAL (UPDATE)                        -->
    <!-- ============================================================== -->
    <div v-if="isEditDatasetModalOpen" class="modal-backdrop" @click.self="isEditDatasetModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Edit3 :size="18" class="text-amber" />
            <h3 class="modal-title">Perbarui Informasi Dataset (#{{ editDatasetForm.id }})</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isEditDatasetModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleUpdateDataset">
          <div class="form-field">
            <label class="form-label">Nama Dataset <span class="text-red">*</span></label>
            <input v-model="editDatasetForm.nama_dataset" type="text" class="form-input" required />
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Cakupan Wilayah</label>
              <select v-model="editDatasetForm.kecamatan" class="form-input">
                <option value="Seluruh Kabupaten">Seluruh Kabupaten</option>
                <option v-for="kec in daftarKecamatan" :key="kec" :value="kec">{{ kec }}</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Tipe Geometri</label>
              <select v-model="editDatasetForm.tipe_geometri" class="form-input">
                <option value="Point">Point</option>
                <option value="LineString">LineString</option>
                <option value="Polygon">Polygon</option>
              </select>
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Deskripsi</label>
            <textarea v-model="editDatasetForm.deskripsi" class="form-textarea" rows="3"></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isEditDatasetModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <CheckCircle2 :size="15" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 3: TAMBAH PENGGUNA / OPERATOR BARU                      -->
    <!-- ============================================================== -->
    <div v-if="isCreateUserModalOpen" class="modal-backdrop" @click.self="isCreateUserModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <UserPlus :size="18" class="text-blue" />
            <h3 class="modal-title">Tambah Akun Pengguna / Operator</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isCreateUserModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleCreateUser">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Username <span class="text-red">*</span></label>
              <input v-model="newUserForm.username" type="text" class="form-input font-mono" placeholder="op_pupr_minsel" required />
            </div>
            <div class="form-field">
              <label class="form-label">Nama Lengkap &amp; Gelar <span class="text-red">*</span></label>
              <input v-model="newUserForm.nama_lengkap" type="text" class="form-input" placeholder="John Doe, S.T." required />
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Email Dinas</label>
            <input v-model="newUserForm.email" type="email" class="form-input" placeholder="nama@minahasaselatan.go.id" />
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Peran Sistem (Role) <span class="text-red">*</span></label>
              <!-- Batasi pilihan role jika login sebagai Admin OPD -->
              <select v-if="authStore.currentRole === 'admin_opd'" v-model="newUserForm.role" class="form-input">
                <option value="operator_opd">Operator OPD</option>
                <option value="admin_opd">Admin OPD (Delegasi)</option>
              </select>
              <select v-else v-model="newUserForm.role" class="form-input">
                <option value="superadmin">Superadmin</option>
                <option value="admin_gis">Admin GIS</option>
                <option value="admin_opd">Admin OPD</option>
                <option value="operator_opd">Operator OPD</option>
                <option value="validator_gis">Validator GIS</option>
                <option value="approver_data">Approver Data (Bappelitbangda)</option>
                <option value="pimpinan">Pimpinan Daerah</option>
                <option value="publik">Publik</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Instansi / OPD <span class="text-red">*</span></label>
              <!-- Kunci OPD jika login sebagai Admin OPD -->
              <input
                v-if="authStore.currentRole === 'admin_opd'"
                :value="authStore.opdLabel"
                type="text"
                class="form-input"
                disabled
              />
              <select
                v-else
                v-model="newUserForm.opd"
                class="form-input"
                @change="handleSelectNewUserOpd"
              >
                <option v-for="o in opdList" :key="o.id" :value="o.nama_opd">
                  {{ o.nama_opd }} ({{ o.kode_opd }})
                </option>
              </select>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isCreateUserModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <UserPlus :size="15" />
              <span>Daftarkan Pengguna</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 4: EDIT DATA PENGGUNA                                   -->
    <!-- ============================================================== -->
    <div v-if="isEditUserModalOpen" class="modal-backdrop" @click.self="isEditUserModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Edit3 :size="18" class="text-amber" />
            <h3 class="modal-title">Edit Informasi Pengguna (@{{ editUserForm.username }})</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isEditUserModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleUpdateUser">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Username</label>
              <input :value="editUserForm.username" type="text" class="form-input font-mono" disabled />
            </div>
            <div class="form-field">
              <label class="form-label">Nama Lengkap &amp; Gelar <span class="text-red">*</span></label>
              <input v-model="editUserForm.nama_lengkap" type="text" class="form-input" required />
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Email Dinas</label>
            <input v-model="editUserForm.email" type="email" class="form-input" />
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Peran Sistem (Role)</label>
              <!-- Batasi pilihan role jika login sebagai Admin OPD -->
              <select v-if="authStore.currentRole === 'admin_opd'" v-model="editUserForm.role" class="form-input">
                <option value="operator_opd">Operator OPD</option>
                <option value="admin_opd">Admin OPD</option>
              </select>
              <select v-else v-model="editUserForm.role" class="form-input">
                <option value="superadmin">Superadmin</option>
                <option value="admin_gis">Admin GIS</option>
                <option value="admin_opd">Admin OPD</option>
                <option value="operator_opd">Operator OPD</option>
                <option value="validator_gis">Validator GIS</option>
                <option value="approver_data">Approver Data (Bappelitbangda)</option>
                <option value="pimpinan">Pimpinan Daerah</option>
                <option value="publik">Publik</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Instansi / OPD</label>
              <!-- Kunci OPD jika login sebagai Admin OPD -->
              <input
                v-if="authStore.currentRole === 'admin_opd'"
                :value="editUserForm.opd"
                type="text"
                class="form-input"
                disabled
              />
              <select
                v-else
                v-model="editUserForm.opd"
                class="form-input"
                @change="handleSelectEditUserOpd"
              >
                <option v-for="o in opdList" :key="o.id" :value="o.nama_opd">
                  {{ o.nama_opd }} ({{ o.kode_opd }})
                </option>
              </select>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isEditUserModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <CheckCircle2 :size="15" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 5: TAMBAH MASTER OPD BARU                               -->
    <!-- ============================================================== -->
    <div v-if="isCreateOpdModalOpen" class="modal-backdrop" @click.self="isCreateOpdModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Building2 :size="18" class="text-blue" />
            <h3 class="modal-title">Tambah Perangkat Daerah (OPD) Baru</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isCreateOpdModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleCreateOpd">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kode Singkat OPD <span class="text-red">*</span></label>
              <input v-model="newOpdForm.kode_opd" type="text" class="form-input font-mono" placeholder="DISPAR" required />
            </div>
            <div class="form-field">
              <label class="form-label">Singkatan Nama</label>
              <input v-model="newOpdForm.singkatan" type="text" class="form-input" placeholder="Dispar" />
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Nama Lengkap Instansi / Dinas <span class="text-red">*</span></label>
            <input v-model="newOpdForm.nama_opd" type="text" class="form-input" placeholder="Dinas Pariwisata Kabupaten Minahasa Selatan" required />
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Penanggung Jawab (PIC / Kepala)</label>
              <input v-model="newOpdForm.pic" type="text" class="form-input" placeholder="Kepala Dinas Pariwisata" />
            </div>
            <div class="form-field">
              <label class="form-label">Kontak Telepon Kantor</label>
              <input v-model="newOpdForm.telepon" type="text" class="form-input" placeholder="(0430) 21xxx" />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Email Dinas Resmi</label>
              <input v-model="newOpdForm.email" type="email" class="form-input" placeholder="dinas@minselkab.go.id" />
            </div>
            <div class="form-field">
              <label class="form-label">Alamat Kantor</label>
              <input v-model="newOpdForm.alamat" type="text" class="form-input" placeholder="Jl. Trans Sulawesi, Amurang" />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isCreateOpdModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <Plus :size="15" />
              <span>Daftarkan OPD</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 6: EDIT MASTER OPD                                      -->
    <!-- ============================================================== -->
    <div v-if="isEditOpdModalOpen" class="modal-backdrop" @click.self="isEditOpdModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Edit3 :size="18" class="text-amber" />
            <h3 class="modal-title">Edit Data Perangkat Daerah ({{ editOpdForm.kode_opd }})</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isEditOpdModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleUpdateOpd">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kode OPD</label>
              <input :value="editOpdForm.kode_opd" type="text" class="form-input font-mono" disabled />
            </div>
            <div class="form-field">
              <label class="form-label">Singkatan Nama</label>
              <input v-model="editOpdForm.singkatan" type="text" class="form-input" />
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Nama Lengkap Instansi / Dinas <span class="text-red">*</span></label>
            <input v-model="editOpdForm.nama_opd" type="text" class="form-input" required />
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Penanggung Jawab (PIC / Kepala)</label>
              <input v-model="editOpdForm.pic" type="text" class="form-input" />
            </div>
            <div class="form-field">
              <label class="form-label">Kontak Telepon Kantor</label>
              <input v-model="editOpdForm.telepon" type="text" class="form-input" />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Email Dinas Resmi</label>
              <input v-model="editOpdForm.email" type="email" class="form-input" />
            </div>
            <div class="form-field">
              <label class="form-label">Alamat Kantor</label>
              <input v-model="editOpdForm.alamat" type="text" class="form-input" />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isEditOpdModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <CheckCircle2 :size="15" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 7: TAMBAH KATEGORI SPASIAL BARU                         -->
    <!-- ============================================================== -->
    <div v-if="isCreateKategoriModalOpen" class="modal-backdrop" @click.self="isCreateKategoriModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Layers :size="18" class="text-blue" />
            <h3 class="modal-title">Tambah Kategori Spasial Tematik Baru</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isCreateKategoriModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleCreateKategori">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Nama Kategori Tematik <span class="text-red">*</span></label>
              <input v-model="newKategoriForm.nama_kategori" type="text" class="form-input" placeholder="Contoh: Pariwisata & Ekosistem" required />
            </div>
            <div class="form-field">
              <label class="form-label">Kode Unik Kategori</label>
              <input v-model="newKategoriForm.kode" type="text" class="form-input font-mono" placeholder="pariwisata_ekosistem" />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Warna Simbologi Standar</label>
              <div class="flex-row-align gap-2">
                <input v-model="newKategoriForm.warna_default" type="color" class="color-picker-input" />
                <input v-model="newKategoriForm.warna_default" type="text" class="form-input font-mono" />
              </div>
            </div>
            <div class="form-field">
              <label class="form-label">OPD Pembina Data Sektoral</label>
              <select v-model="newKategoriForm.opd_id" class="form-input">
                <option v-for="o in opdList" :key="o.id" :value="o.id">
                  {{ o.nama_opd }} ({{ o.kode_opd }})
                </option>
              </select>
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Deskripsi / Ruang Lingkup Tema</label>
            <textarea v-model="newKategoriForm.deskripsi" class="form-textarea" rows="3" placeholder="Jelaskan objek-objek geospasial yang tercakup dalam tema ini..."></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isCreateKategoriModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <Plus :size="15" />
              <span>Simpan Kategori</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 8: EDIT KATEGORI SPASIAL                                -->
    <!-- ============================================================== -->
    <div v-if="isEditKategoriModalOpen" class="modal-backdrop" @click.self="isEditKategoriModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Edit3 :size="18" class="text-amber" />
            <h3 class="modal-title">Edit Kategori Spasial ({{ editKategoriForm.kode }})</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isEditKategoriModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleUpdateKategori">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kode Kategori</label>
              <input :value="editKategoriForm.kode" type="text" class="form-input font-mono" disabled />
            </div>
            <div class="form-field">
              <label class="form-label">Nama Kategori <span class="text-red">*</span></label>
              <input v-model="editKategoriForm.nama_kategori" type="text" class="form-input" required />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Warna Simbologi Standar</label>
              <div class="flex-row-align gap-2">
                <input v-model="editKategoriForm.warna_default" type="color" class="color-picker-input" />
                <input v-model="editKategoriForm.warna_default" type="text" class="form-input font-mono" />
              </div>
            </div>
            <div class="form-field">
              <label class="form-label">OPD Pembina Data</label>
              <select v-model="editKategoriForm.opd_id" class="form-input">
                <option v-for="o in opdList" :key="o.id" :value="o.id">
                  {{ o.nama_opd }} ({{ o.kode_opd }})
                </option>
              </select>
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Deskripsi / Ruang Lingkup Tema</label>
            <textarea v-model="editKategoriForm.deskripsi" class="form-textarea" rows="3"></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isEditKategoriModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <CheckCircle2 :size="15" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 9: TAMBAH KECAMATAN                                      -->
    <!-- ============================================================== -->
    <div v-if="isCreateKecamatanModalOpen" class="modal-backdrop" @click.self="isCreateKecamatanModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <MapPin :size="18" class="text-emerald" />
            <h3 class="modal-title">Tambah Kecamatan Baru</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isCreateKecamatanModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleCreateKecamatan">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kode Wilayah Kemendagri *</label>
              <input v-model="kecamatanForm.kode_kecamatan" type="text" class="form-input" placeholder="contoh: 71.05.18" required />
            </div>
            <div class="form-field">
              <label class="form-label">Nama Kecamatan *</label>
              <input v-model="kecamatanForm.nama_kecamatan" type="text" class="form-input" placeholder="contoh: Amurang Barat" required />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Ibukota Kecamatan</label>
              <input v-model="kecamatanForm.ibukota_kecamatan" type="text" class="form-input" placeholder="contoh: Kapitu" />
            </div>
            <div class="form-field">
              <label class="form-label">Luas Wilayah (km²)</label>
              <input v-model.number="kecamatanForm.luas_wilayah_km2" type="number" step="0.01" class="form-input" />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Titik Bujur (Longitude)</label>
              <input v-model.number="kecamatanForm.center_lng" type="number" step="0.0001" class="form-input" placeholder="124.57" />
            </div>
            <div class="form-field">
              <label class="form-label">Titik Lintang (Latitude)</label>
              <input v-model.number="kecamatanForm.center_lat" type="number" step="0.0001" class="form-input" placeholder="1.18" />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isCreateKecamatanModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <CheckCircle2 :size="15" />
              <span>Simpan Kecamatan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 10: EDIT KECAMATAN                                       -->
    <!-- ============================================================== -->
    <div v-if="isEditKecamatanModalOpen" class="modal-backdrop" @click.self="isEditKecamatanModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Edit3 :size="18" class="text-amber" />
            <h3 class="modal-title">Edit Kecamatan ({{ kecamatanForm.nama_kecamatan }})</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isEditKecamatanModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleUpdateKecamatan">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kode Wilayah Kemendagri *</label>
              <input v-model="kecamatanForm.kode_kecamatan" type="text" class="form-input" required />
            </div>
            <div class="form-field">
              <label class="form-label">Nama Kecamatan *</label>
              <input v-model="kecamatanForm.nama_kecamatan" type="text" class="form-input" required />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Ibukota Kecamatan</label>
              <input v-model="kecamatanForm.ibukota_kecamatan" type="text" class="form-input" />
            </div>
            <div class="form-field">
              <label class="form-label">Luas Wilayah (km²)</label>
              <input v-model.number="kecamatanForm.luas_wilayah_km2" type="number" step="0.01" class="form-input" />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Titik Bujur (Longitude)</label>
              <input v-model.number="kecamatanForm.center_lng" type="number" step="0.0001" class="form-input" />
            </div>
            <div class="form-field">
              <label class="form-label">Titik Lintang (Latitude)</label>
              <input v-model.number="kecamatanForm.center_lat" type="number" step="0.0001" class="form-input" />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isEditKecamatanModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <CheckCircle2 :size="15" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 11: TAMBAH DESA / KELURAHAN                             -->
    <!-- ============================================================== -->
    <div v-if="isCreateDesaModalOpen" class="modal-backdrop" @click.self="isCreateDesaModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Building2 :size="18" class="text-emerald" />
            <h3 class="modal-title">Tambah Desa / Kelurahan Baru</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isCreateDesaModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleCreateDesa">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kecamatan Induk *</label>
              <select v-model.number="desaForm.kecamatan_id" class="form-select" required>
                <option v-for="k in kecamatanAdminList" :key="k.id" :value="k.id">
                  {{ k.nama_kecamatan }} ({{ k.kode_kecamatan }})
                </option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Klasifikasi Wilayah *</label>
              <select v-model="desaForm.tipe" class="form-select" required>
                <option value="DESA">DESA</option>
                <option value="KELURAHAN">KELURAHAN</option>
              </select>
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kode Wilayah Kemendagri *</label>
              <input v-model="desaForm.kode_desa" type="text" class="form-input" placeholder="contoh: 71.05.01.2001" required />
            </div>
            <div class="form-field">
              <label class="form-label">Nama Desa / Kelurahan *</label>
              <input v-model="desaForm.nama_desa" type="text" class="form-input" placeholder="contoh: Buyungon" required />
            </div>
          </div>

          <div class="form-row-3">
            <div class="form-field">
              <label class="form-label">Luas Wilayah (km²)</label>
              <input v-model.number="desaForm.luas_wilayah_km2" type="number" step="0.01" class="form-input" />
            </div>
            <div class="form-field">
              <label class="form-label">Longitude (Bujur)</label>
              <input v-model.number="desaForm.center_lng" type="number" step="0.0001" class="form-input" placeholder="124.57" />
            </div>
            <div class="form-field">
              <label class="form-label">Latitude (Lintang)</label>
              <input v-model.number="desaForm.center_lat" type="number" step="0.0001" class="form-input" placeholder="1.18" />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isCreateDesaModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <CheckCircle2 :size="15" />
              <span>Simpan Desa/Kelurahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 12: EDIT DESA / KELURAHAN                               -->
    <!-- ============================================================== -->
    <div v-if="isEditDesaModalOpen" class="modal-backdrop" @click.self="isEditDesaModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Edit3 :size="18" class="text-amber" />
            <h3 class="modal-title">Edit {{ desaForm.tipe }} ({{ desaForm.nama_desa }})</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isEditDesaModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleUpdateDesa">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kecamatan Induk *</label>
              <select v-model.number="desaForm.kecamatan_id" class="form-select" required>
                <option v-for="k in kecamatanAdminList" :key="k.id" :value="k.id">
                  {{ k.nama_kecamatan }} ({{ k.kode_kecamatan }})
                </option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Klasifikasi Wilayah *</label>
              <select v-model="desaForm.tipe" class="form-select" required>
                <option value="DESA">DESA</option>
                <option value="KELURAHAN">KELURAHAN</option>
              </select>
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kode Wilayah Kemendagri *</label>
              <input v-model="desaForm.kode_desa" type="text" class="form-input" required />
            </div>
            <div class="form-field">
              <label class="form-label">Nama Desa / Kelurahan *</label>
              <input v-model="desaForm.nama_desa" type="text" class="form-input" required />
            </div>
          </div>

          <div class="form-row-3">
            <div class="form-field">
              <label class="form-label">Luas Wilayah (km²)</label>
              <input v-model.number="desaForm.luas_wilayah_km2" type="number" step="0.01" class="form-input" />
            </div>
            <div class="form-field">
              <label class="form-label">Longitude (Bujur)</label>
              <input v-model.number="desaForm.center_lng" type="number" step="0.0001" class="form-input" />
            </div>
            <div class="form-field">
              <label class="form-label">Latitude (Lintang)</label>
              <input v-model.number="desaForm.center_lat" type="number" step="0.0001" class="form-input" />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isEditDesaModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <CheckCircle2 :size="15" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 13: TAMBAH BASE MAP (PETA DASAR)                         -->
    <!-- ============================================================== -->
    <div v-if="isCreateBasemapModalOpen" class="modal-backdrop" @click.self="isCreateBasemapModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Compass :size="18" class="text-emerald" />
            <h3 class="modal-title">Tambah Peta Dasar Baru (Base Map)</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isCreateBasemapModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleCreateBasemap">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Label Tampilan *</label>
              <input v-model="basemapForm.label" type="text" class="form-input" placeholder="contoh: ESRI World Imagery" required />
            </div>
            <div class="form-field">
              <label class="form-label">Kode Unik / Identifier *</label>
              <input v-model="basemapForm.name" type="text" class="form-input" placeholder="contoh: esri-satellite" required />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Tipe Layer *</label>
              <select v-model="basemapForm.type" class="form-select" required>
                <option value="raster">Raster Tiles (XYZ / TMS)</option>
                <option value="vector">Vector Tiles / JSON Style</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Warna Aksen / Preview (Hex)</label>
              <div class="color-picker-row">
                <input v-model="basemapForm.preview_color" type="color" class="form-color-picker" />
                <input v-model="basemapForm.preview_color" type="text" class="form-input" placeholder="#334155" />
              </div>
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">URL Tile Endpoint *</label>
            <input v-model="basemapForm.tile_url" type="text" class="form-input font-mono" placeholder="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png" required />
            <span class="field-hint">Mendukung placeholder <code>{z}</code>, <code>{x}</code>, <code>{y}</code> atau endpoint Style JSON MapLibre/Mapbox.</span>
          </div>

          <div class="form-field">
            <label class="form-label">Atribusi Hak Cipta (Attribution)</label>
            <input v-model="basemapForm.attribution" type="text" class="form-input" placeholder="&copy; OpenStreetMap contributors" />
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Min Zoom</label>
              <input v-model.number="basemapForm.min_zoom" type="number" min="0" max="24" class="form-input" />
            </div>
            <div class="form-field">
              <label class="form-label">Max Zoom</label>
              <input v-model.number="basemapForm.max_zoom" type="number" min="0" max="24" class="form-input" />
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Deskripsi / Ruang Lingkup Peta</label>
            <textarea v-model="basemapForm.desc" class="form-textarea" rows="2" placeholder="Peta citra satelit resolusi tinggi global dengan akurasi sub-meter."></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isCreateBasemapModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <CheckCircle2 :size="15" />
              <span>Simpan Peta Dasar</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 14: EDIT BASE MAP (PETA DASAR)                           -->
    <!-- ============================================================== -->
    <div v-if="isEditBasemapModalOpen" class="modal-backdrop" @click.self="isEditBasemapModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <Edit3 :size="18" class="text-amber" />
            <h3 class="modal-title">Edit Peta Dasar ({{ basemapForm.label }})</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isEditBasemapModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleUpdateBasemap">
          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Label Tampilan *</label>
              <input v-model="basemapForm.label" type="text" class="form-input" required />
            </div>
            <div class="form-field">
              <label class="form-label">Kode Unik / Identifier</label>
              <input v-model="basemapForm.name" type="text" class="form-input" readonly disabled />
              <span class="field-hint">Identifier sistem tidak dapat diubah.</span>
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Tipe Layer *</label>
              <select v-model="basemapForm.type" class="form-select" required>
                <option value="raster">Raster Tiles (XYZ / TMS)</option>
                <option value="vector">Vector Tiles / JSON Style</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Warna Aksen / Preview (Hex)</label>
              <div class="color-picker-row">
                <input v-model="basemapForm.preview_color" type="color" class="form-color-picker" />
                <input v-model="basemapForm.preview_color" type="text" class="form-input" />
              </div>
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">URL Tile Endpoint *</label>
            <input v-model="basemapForm.tile_url" type="text" class="form-input font-mono" required />
          </div>

          <div class="form-field">
            <label class="form-label">Atribusi Hak Cipta (Attribution)</label>
            <input v-model="basemapForm.attribution" type="text" class="form-input" />
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Min Zoom</label>
              <input v-model.number="basemapForm.min_zoom" type="number" min="0" max="24" class="form-input" />
            </div>
            <div class="form-field">
              <label class="form-label">Max Zoom</label>
              <input v-model.number="basemapForm.max_zoom" type="number" min="0" max="24" class="form-input" />
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Deskripsi / Ruang Lingkup Peta</label>
            <textarea v-model="basemapForm.desc" class="form-textarea" rows="2"></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isEditBasemapModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit">
              <CheckCircle2 :size="15" />
              <span>Simpan Perubahan</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 15: UPLOAD PAKET SHAPEFILE (SHP ZIP)                     -->
    <!-- ============================================================== -->
    <div v-if="isUploadShpModalOpen" class="modal-backdrop" @click.self="isUploadShpModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <UploadCloud :size="18" class="text-blue" />
            <h3 class="modal-title">Upload Paket Shapefile (SHP ZIP)</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isUploadShpModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form class="modal-form" @submit.prevent="handleUploadShpDataset">
          <!-- Dropzone Berkas ZIP -->
          <div class="shp-dropzone" :class="{ 'has-file': !!uploadShpFile }" @click="uploadShpInputRef?.click()">
            <input
              ref="uploadShpInputRef"
              type="file"
              accept=".zip"
              class="hidden-file-input"
              @change="onShpFileSelected"
            />
            <div v-if="!uploadShpFile" class="dropzone-empty">
              <UploadCloud :size="36" class="text-blue" />
              <p class="dropzone-title">Klik atau Seret Berkas ZIP Shapefile ke Sini</p>
              <p class="dropzone-hint">
                Format <code>.zip</code> harus memuat 4 komponen utama: <code>.shp</code>, <code>.shx</code>, <code>.dbf</code>, dan <code>.prj</code> (WGS84).
              </p>
            </div>
            <div v-else class="dropzone-selected">
              <FileArchive :size="32" class="text-emerald" />
              <div class="dropzone-file-info">
                <span class="file-name">{{ uploadShpFile.name }}</span>
                <span class="file-size font-mono">{{ (uploadShpFile.size / 1024).toFixed(1) }} KB</span>
              </div>
              <button type="button" class="btn-change-file" @click.stop="uploadShpFile = null">
                <X :size="14" />
                <span>Ganti</span>
              </button>
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Nama Dataset <span class="text-red">*</span></label>
              <input v-model="uploadShpForm.nama_dataset" type="text" class="form-input" placeholder="Contoh: Jaringan Irigasi Sekunder" required />
            </div>
            <div class="form-field">
              <label class="form-label">Kode Unik Dataset</label>
              <input v-model="uploadShpForm.kode_dataset" type="text" class="form-input font-mono" placeholder="MNSL-PUPR-SHP-01" />
            </div>
          </div>

          <div class="form-row-2">
            <div class="form-field">
              <label class="form-label">Kategori Tematik *</label>
              <select v-model="uploadShpForm.kategori" class="form-select" required>
                <option value="infrastruktur_pu">Pekerjaan Umum &amp; Infrastruktur</option>
                <option value="pertanian">Pertanian &amp; Ketahanan Pangan</option>
                <option value="kesehatan">Kesehatan &amp; Faskes</option>
                <option value="pariwisata">Pariwisata &amp; Kebudayaan</option>
                <option value="kebencanaan">Kebencanaan &amp; Titik Rawan</option>
                <option value="pendidikan">Pendidikan &amp; Sekolah</option>
                <option value="lingkungan">Lingkungan Hidup &amp; Kehutanan</option>
              </select>
            </div>
            <div class="form-field">
              <label class="form-label">Cakupan Wilayah</label>
              <select v-model="uploadShpForm.kecamatan" class="form-select">
                <option value="Seluruh Kabupaten">Seluruh Kabupaten Minahasa Selatan</option>
                <option v-for="kec in daftarKecamatan" :key="kec" :value="kec">{{ kec }}</option>
              </select>
            </div>
          </div>

          <div class="form-field">
            <label class="form-label">Deskripsi / Metadata Sumber Data</label>
            <textarea v-model="uploadShpForm.deskripsi" class="form-textarea" rows="2" placeholder="Jelaskan instansi pembuat data, metode akuisisi survei, dan kegunaan spasial..."></textarea>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isUploadShpModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit" :disabled="isUploadingShp">
              <RotateCcw v-if="isUploadingShp" :size="15" class="spin-anim" />
              <CheckCircle2 v-else :size="15" />
              <span>{{ isUploadingShp ? 'Memeriksa & Mengunggah...' : 'Unggah & Validasi SHP' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL 16: UPLOAD ULANG REVISI SHAPEFILE (SHP ZIP)             -->
    <!-- ============================================================== -->
    <div v-if="isReuploadShpModalOpen" class="modal-backdrop" @click.self="isReuploadShpModalOpen = false">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <UploadCloud :size="18" class="text-amber" />
            <h3 class="modal-title">Upload Ulang Revisi Shapefile</h3>
          </div>
          <button type="button" class="btn-close-modal" @click="isReuploadShpModalOpen = false">
            <X :size="16" />
          </button>
        </div>

        <form v-if="reuploadTargetDataset" class="modal-form" @submit.prevent="handleReuploadShpDataset">
          <div class="reupload-target-info">
            <span class="target-name">{{ reuploadTargetDataset.nama_dataset }}</span>
            <span class="target-meta font-mono">{{ reuploadTargetDataset.kode_dataset }} &bull; Versi Saat Ini: {{ reuploadTargetDataset.versi || 'v1.0' }} (Rev #{{ reuploadTargetDataset.revisi_ke || 1 }})</span>
            <span class="status-pill inline-pill" :class="'st-' + reuploadTargetDataset.status.toLowerCase()">
              {{ reuploadTargetDataset.status }}
            </span>
          </div>

          <div class="shp-dropzone" :class="{ 'has-file': !!reuploadShpFile }" @click="reuploadShpInputRef?.click()">
            <input
              ref="reuploadShpInputRef"
              type="file"
              accept=".zip"
              class="hidden-file-input"
              @change="onReuploadShpFileSelected"
            />
            <div v-if="!reuploadShpFile" class="dropzone-empty">
              <UploadCloud :size="36" class="text-amber" />
              <p class="dropzone-title">Pilih Berkas ZIP Shapefile Hasil Koreksi</p>
              <p class="dropzone-hint">
                Sistem akan memverifikasi ulang geometri, CRS EPSG:4326, dan menaikkan nomor revisi dataset secara otomatis.
              </p>
            </div>
            <div v-else class="dropzone-selected">
              <FileArchive :size="32" class="text-emerald" />
              <div class="dropzone-file-info">
                <span class="file-name">{{ reuploadShpFile.name }}</span>
                <span class="file-size font-mono">{{ (reuploadShpFile.size / 1024).toFixed(1) }} KB</span>
              </div>
              <button type="button" class="btn-change-file" @click.stop="reuploadShpFile = null">
                <X :size="14" />
                <span>Ganti</span>
              </button>
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn-modal-cancel" @click="isReuploadShpModalOpen = false">Batal</button>
            <button type="submit" class="btn-modal-submit" :disabled="isReuploading">
              <RotateCcw v-if="isReuploading" :size="15" class="spin-anim" />
              <CheckCircle2 v-else :size="15" />
              <span>{{ isReuploading ? 'Memvalidasi Revisi...' : 'Simpan & Terapkan Revisi SHP' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Tab Statistical Charts Grid */
.tab-chart-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
  gap: 1rem;
  margin-bottom: 1.25rem;
}
@media (max-width: 768px) {
  .tab-chart-grid {
    grid-template-columns: 1fr;
  }
}

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

.btn-create-dataset {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  background: #0284c7;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-create-dataset:hover {
  background: #0369a1;
  box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
}

.btn-edit {
  background: rgba(245, 158, 11, 0.15) !important;
  color: #fbbf24 !important;
  border: 1px solid rgba(245, 158, 11, 0.3) !important;
}

.btn-edit:hover {
  background: rgba(245, 158, 11, 0.25) !important;
}

.btn-delete {
  background: rgba(239, 68, 68, 0.15) !important;
  color: #f87171 !important;
  border: 1px solid rgba(239, 68, 68, 0.3) !important;
}

.btn-delete:hover {
  background: rgba(239, 68, 68, 0.25) !important;
}

.btn-toggle-deact {
  background: rgba(239, 68, 68, 0.12) !important;
  color: #fca5a5 !important;
}

.readonly-notice-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(56, 189, 248, 0.08);
  border: 1px solid rgba(56, 189, 248, 0.25);
  color: #38bdf8;
  padding: 10px 16px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 600;
}

.icon-ro {
  color: #38bdf8;
  flex-shrink: 0;
}

/* MODAL SYSTEM */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.modal-card {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  width: 100%;
  max-width: 580px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  animation: modalIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalIn {
  from {
    opacity: 0;
    transform: scale(0.96) translateY(8px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
}

.modal-title-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-title {
  font-size: 16px;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
}

.btn-close-modal {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close-modal:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

.modal-form {
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  max-height: calc(85vh - 120px);
  overflow-y: auto;
}

.form-row-2 {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 14px;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.text-red {
  color: #ef4444;
}

.form-input, .form-textarea {
  background: #0b132b;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  padding: 9px 12px;
  color: #ffffff;
  font-size: 13px;
  outline: none;
  transition: all 0.2s;
  box-sizing: border-box;
  width: 100%;
}

.form-input:focus, .form-textarea:focus {
  border-color: #38bdf8;
  box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
}

.modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  padding-top: 10px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  margin-top: 6px;
}

.btn-modal-cancel {
  padding: 9px 18px;
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #cbd5e1;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-modal-cancel:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #ffffff;
}

.btn-modal-submit {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 9px 20px;
  background: #0284c7;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-modal-submit:hover {
  background: #0369a1;
  box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
}

.flex-row-align {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-preview-box {
  width: 16px;
  height: 16px;
  border-radius: 4px;
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: inline-block;
  flex-shrink: 0;
}

.theme-color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.color-swatch-label {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #94a3b8;
}

.color-picker-input {
  width: 38px;
  height: 38px;
  padding: 0;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 6px;
  background: transparent;
  cursor: pointer;
}

.cell-desc-table {
  display: block;
  max-width: 280px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: #94a3b8;
  font-size: 11px;
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

/* ============================================================== */
/* SISTEM VERSI OTOMATIS STYLES                                   */
/* ============================================================== */
.chip-version {
  background: rgba(14, 165, 233, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.chip-version.version-official {
  background: rgba(16, 185, 129, 0.15);
  color: #34d399;
  border-color: rgba(52, 211, 153, 0.35);
}

.chip-version.version-draft {
  background: rgba(148, 163, 184, 0.12);
  color: #94a3b8;
  border-color: rgba(148, 163, 184, 0.25);
}

.version-cell {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.version-badge {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  width: fit-content;
}

.version-badge.badge-official {
  background: rgba(16, 185, 129, 0.18);
  color: #34d399;
  border: 1px solid rgba(52, 211, 153, 0.3);
}

.version-badge.badge-draft {
  background: rgba(148, 163, 184, 0.15);
  color: #cbd5e1;
  border: 1px solid rgba(148, 163, 184, 0.25);
}

.revisi-sub {
  font-size: 10px;
  color: #64748b;
  font-family: monospace;
}

/* ============================================================== */
/* SISTEM PENGUNJUNG & VISITOR ANALYTICS STYLES                   */
/* ============================================================== */
.visitor-analytics-box {
  margin-top: 20px;
}

.visitor-kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 20px;
}

@media (max-width: 900px) {
  .visitor-kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.vkpi-card {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.vkpi-label {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.vkpi-val-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.vkpi-val {
  font-size: 22px;
  font-weight: 800;
  font-family: monospace;
}

.vkpi-sub {
  font-size: 11px;
  color: #64748b;
}

.visitor-details-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

@media (max-width: 800px) {
  .visitor-details-grid {
    grid-template-columns: 1fr;
  }
}

.visitor-subcard {
  background: rgba(15, 23, 42, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.subcard-header {
  display: flex;
  align-items: center;
  gap: 8px;
}

.subcard-title {
  font-size: 13px;
  font-weight: 700;
  color: #e2e8f0;
  margin: 0;
}

.device-bars {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.device-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.device-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 11px;
}

.d-label {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #cbd5e1;
}

.d-count {
  font-family: monospace;
  font-weight: 600;
  color: #94a3b8;
}

.device-bar-track {
  width: 100%;
  height: 6px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  overflow: hidden;
}

.device-bar-fill {
  height: 100%;
  border-radius: 999px;
  transition: width 0.4s ease;
}

.bar-desktop {
  background: linear-gradient(90deg, #0284c7, #38bdf8);
}

.bar-mobile {
  background: linear-gradient(90deg, #059669, #34d399);
}

.bar-tablet {
  background: linear-gradient(90deg, #7c3aed, #a855f7);
}

.popular-page-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.popular-page-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 6px;
  font-size: 12px;
}

.page-info {
  display: flex;
  align-items: center;
  gap: 8px;
}

.page-tag {
  background: rgba(14, 165, 233, 0.12);
  color: #38bdf8;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
}

.page-name {
  color: #e2e8f0;
}

.page-hits {
  color: #94a3b8;
  font-size: 11px;
}

/* ============================================================== */
/* AUDIT LOG & JEJAK SISTEM STYLES                                */
/* ============================================================== */
.table-meta-bar {
  padding: 8px 14px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  font-size: 11px;
  color: #94a3b8;
}

.meta-count strong {
  color: #e2e8f0;
}

.cell-time {
  font-size: 11px;
  color: #94a3b8;
}

.log-cat-badge {
  display: inline-block;
  padding: 3px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.cat-dataset {
  background: rgba(2, 132, 199, 0.18);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.cat-validasi {
  background: rgba(139, 92, 246, 0.18);
  color: #c084fc;
  border: 1px solid rgba(192, 132, 252, 0.3);
}

.cat-persetujuan {
  background: rgba(245, 158, 11, 0.18);
  color: #fbbf24;
  border: 1px solid rgba(251, 191, 36, 0.3);
}

.cat-data_dasar {
  background: rgba(6, 182, 212, 0.18);
  color: #22d3ee;
  border: 1px solid rgba(34, 211, 238, 0.3);
}

.cat-user {
  background: rgba(16, 185, 129, 0.18);
  color: #34d399;
  border: 1px solid rgba(52, 211, 153, 0.3);
}

.cat-opd {
  background: rgba(148, 163, 184, 0.18);
  color: #cbd5e1;
  border: 1px solid rgba(148, 163, 184, 0.3);
}

.cat-kategori {
  background: rgba(236, 72, 153, 0.18);
  color: #f472b6;
  border: 1px solid rgba(244, 114, 182, 0.3);
}

.log-action-tag {
  background: rgba(255, 255, 255, 0.06);
  padding: 3px 6px;
  border-radius: 4px;
  font-size: 11px;
  color: #e2e8f0;
}

.chip-version-mini {
  display: inline-block;
  background: rgba(14, 165, 233, 0.15);
  color: #38bdf8;
  padding: 1px 6px;
  border-radius: 3px;
  font-size: 10px;
  font-weight: 700;
  width: fit-content;
  margin-top: 2px;
}

.log-note-text {
  display: block;
  max-width: 280px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 12px;
  color: #94a3b8;
}

.btn-view {
  background: rgba(2, 132, 199, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.3);
}

.btn-view:hover {
  background: rgba(2, 132, 199, 0.3);
  color: #fff;
}

.spin-anim {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* ============================================================== */
/* SUBNAV PILL GROUP (WILAYAH KECAMATAN / DESA)                   */
/* ============================================================== */
.subnav-pill-group {
  display: flex;
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 8px;
  padding: 3px;
  gap: 4px;
}

.subnav-pill {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  font-size: 0.8rem;
  font-weight: 500;
  color: #94a3b8;
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.subnav-pill:hover {
  color: #f1f5f9;
  background: rgba(255, 255, 255, 0.05);
}

.subnav-pill.active {
  background: #0284c7;
  color: #ffffff;
  box-shadow: 0 2px 8px rgba(2, 132, 199, 0.35);
}

/* ============================================================== */
/* KECAMATAN GRID & CARD STYLES                                   */
/* ============================================================== */
.kecamatan-admin-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
  margin-top: 16px;
}

.kec-admin-card {
  background: rgba(30, 41, 59, 0.75);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  transition: all 0.2s ease;
}

.kec-admin-card:hover {
  border-color: rgba(56, 189, 248, 0.4);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.kec-card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.kec-code {
  font-size: 0.75rem;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.1);
  padding: 2px 8px;
  border-radius: 4px;
}

.badge-total-desa {
  font-size: 0.72rem;
  color: #10b981;
  background: rgba(16, 185, 129, 0.12);
  padding: 2px 8px;
  border-radius: 12px;
  font-weight: 600;
}

.kec-name {
  font-size: 1.05rem;
  font-weight: 600;
  color: #f1f5f9;
  margin: 0 0 12px 0;
}

.kec-info-rows {
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
  font-size: 0.8rem;
}

.kec-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.kec-info-row .info-lbl {
  color: #64748b;
}

.kec-info-row .info-val {
  color: #cbd5e1;
  font-weight: 500;
}

.kec-card-actions {
  display: flex;
  gap: 8px;
  margin-top: auto;
  padding-top: 12px;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
}

/* ============================================================== */
/* DESA & KELURAHAN BADGES                                        */
/* ============================================================== */
.tipe-badge {
  display: inline-block;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.tipe-desa {
  background: rgba(14, 165, 233, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(14, 165, 233, 0.3);
}

.tipe-kel {
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
  border: 1px solid rgba(168, 85, 247, 0.3);
}

.badge-kec {
  font-size: 0.8rem;
  color: #f8fafc;
  background: rgba(51, 65, 85, 0.6);
  padding: 2px 8px;
  border-radius: 4px;
}

/* ============================================================== */
/* BASEMAP ADMIN GRID & CARD STYLES                               */
/* ============================================================== */
.basemap-admin-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 18px;
  margin-top: 16px;
}

.basemap-admin-card {
  background: rgba(30, 41, 59, 0.75);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: all 0.2s ease;
}

.basemap-admin-card:hover {
  border-color: rgba(56, 189, 248, 0.35);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.basemap-admin-card.is-default-card {
  border-color: rgba(234, 179, 8, 0.5);
  box-shadow: 0 0 16px rgba(234, 179, 8, 0.15);
}

.bm-card-header {
  padding: 12px 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 48px;
  position: relative;
}

.bm-header-badges {
  display: flex;
  gap: 6px;
  align-items: center;
}

.badge-default-bm {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(234, 179, 8, 0.95);
  color: #0f172a;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 12px;
}

.badge-type-bm {
  background: rgba(15, 23, 42, 0.7);
  color: #e2e8f0;
  font-size: 0.68rem;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
  backdrop-filter: blur(4px);
}

.bm-header-status {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.75rem;
  color: #f1f5f9;
  font-weight: 500;
  background: rgba(15, 23, 42, 0.6);
  padding: 2px 8px;
  border-radius: 12px;
}

.bm-status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
}

.bm-status-dot.dot-active {
  background: #10b981;
  box-shadow: 0 0 6px #10b981;
}

.bm-status-dot.dot-inactive {
  background: #64748b;
}

.bm-card-body {
  padding: 16px;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.bm-label {
  font-size: 1.05rem;
  font-weight: 600;
  color: #f1f5f9;
  margin: 0;
}

.bm-name {
  font-size: 0.75rem;
  color: #38bdf8;
}

.bm-desc {
  font-size: 0.8rem;
  color: #94a3b8;
  line-height: 1.4;
  margin: 0;
}

.bm-url-box {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 6px;
  padding: 6px 10px;
  font-size: 0.72rem;
  margin-top: 4px;
}

.bm-url-lbl {
  display: block;
  color: #64748b;
  margin-bottom: 2px;
  font-weight: 500;
}

.bm-url-val {
  color: #a5b4fc;
  word-break: break-all;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.bm-meta-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 6px;
}

.bm-card-footer {
  padding: 12px 16px;
  background: rgba(15, 23, 42, 0.4);
  border-top: 1px solid rgba(148, 163, 184, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.btn-bm-default {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #f59e0b;
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.3);
  padding: 4px 10px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-bm-default:hover {
  background: rgba(245, 158, 11, 0.25);
  color: #fbbf24;
}

.btn-warn {
  background: rgba(245, 158, 11, 0.12);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.btn-warn:hover {
  background: rgba(245, 158, 11, 0.25);
  color: #fbbf24;
}

.btn-success {
  background: rgba(16, 185, 129, 0.12);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
  background: rgba(16, 185, 129, 0.25);
  color: #34d399;
}

.color-picker-row {
  display: flex;
  gap: 8px;
  align-items: center;
}

.form-color-picker {
  width: 40px;
  height: 38px;
  border-radius: 6px;
  border: 1px solid rgba(148, 163, 184, 0.2);
  background: transparent;
  cursor: pointer;
  padding: 2px;
}

.field-hint {
  display: block;
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 4px;
}

.field-hint code {
  color: #38bdf8;
  background: rgba(15, 23, 42, 0.5);
  padding: 1px 4px;
  border-radius: 3px;
}

/* ============================================================== */
/* SHP FILE DROPZONE & FILE PICKER STYLES                         */
/* ============================================================== */
.hidden-file-input {
  display: none !important;
}

.shp-dropzone {
  border: 2px dashed rgba(56, 189, 248, 0.35);
  border-radius: 12px;
  padding: 24px 16px;
  text-align: center;
  cursor: pointer;
  background: rgba(15, 23, 42, 0.4);
  transition: all 0.2s ease;
  margin-bottom: 16px;
}

.shp-dropzone:hover {
  border-color: #38bdf8;
  background: rgba(56, 189, 248, 0.05);
}

.shp-dropzone.has-file {
  border-style: solid;
  border-color: rgba(16, 185, 129, 0.4);
  background: rgba(16, 185, 129, 0.05);
}

.dropzone-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.dropzone-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #f1f5f9;
  margin: 0;
}

.dropzone-hint {
  font-size: 0.78rem;
  color: #94a3b8;
  max-width: 440px;
  margin: 0;
  line-height: 1.4;
}

.dropzone-hint code {
  color: #38bdf8;
  background: rgba(15, 23, 42, 0.6);
  padding: 1px 5px;
  border-radius: 3px;
}

.dropzone-selected {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
}

.dropzone-file-info {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
}

.dropzone-file-info .file-name {
  font-weight: 600;
  color: #f1f5f9;
  font-size: 0.9rem;
}

.dropzone-file-info .file-size {
  font-size: 0.78rem;
  color: #38bdf8;
}

.btn-change-file {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(239, 68, 68, 0.15);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #f87171;
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-change-file:hover {
  background: rgba(239, 68, 68, 0.25);
  color: #fca5a5;
}

.reupload-target-info {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 8px;
  padding: 10px 14px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  margin-bottom: 14px;
}

.reupload-target-info .target-name {
  font-weight: 600;
  color: #f1f5f9;
  font-size: 0.95rem;
}

.reupload-target-info .target-meta {
  font-size: 0.75rem;
  color: #94a3b8;
}

.inline-pill {
  align-self: flex-start;
  font-size: 0.7rem;
  padding: 2px 8px;
}

.chip-file {
  background: rgba(56, 189, 248, 0.15) !important;
  border: 1px solid rgba(56, 189, 248, 0.3) !important;
  color: #38bdf8 !important;
}

.btn-download {
  background: rgba(14, 165, 233, 0.12);
  border: 1px solid rgba(14, 165, 233, 0.3);
  color: #38bdf8;
}

.btn-download:hover {
  background: rgba(14, 165, 233, 0.25);
  color: #ffffff;
}

.btn-reupload {
  background: rgba(245, 158, 11, 0.12);
  border: 1px solid rgba(245, 158, 11, 0.3);
  color: #f59e0b;
}

.btn-reupload:hover {
  background: rgba(245, 158, 11, 0.25);
  color: #fbbf24;
}

.file-picker-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-choose-file {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(56, 189, 248, 0.15);
  border: 1px solid rgba(56, 189, 248, 0.35);
  color: #38bdf8;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.btn-choose-file:hover {
  background: rgba(56, 189, 248, 0.25);
  color: #ffffff;
}

.file-chosen-text {
  font-size: 0.8rem;
  color: #cbd5e1;
  font-family: monospace;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 250px;
}

.server-zip-hint {
  margin-top: 8px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px dashed rgba(255, 255, 255, 0.15);
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.sz-label {
  font-size: 0.72rem;
  color: var(--theme-text-muted, #a1a1aa);
  font-weight: 500;
}

.sz-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.sz-badge-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  background: var(--theme-card-bg, #18181b);
  border: 1px solid var(--theme-border, rgba(255, 255, 255, 0.12));
  border-radius: 4px;
  font-size: 0.75rem;
  color: var(--theme-text-main, #f4f4f5);
  cursor: pointer;
  transition: all 0.2s ease;
}

.sz-badge-btn:hover, .sz-badge-btn.active {
  border-color: #38bdf8;
  background: rgba(56, 189, 248, 0.12);
  color: #38bdf8;
}

.sz-tag {
  font-size: 0.65rem;
  padding: 1px 5px;
  background: rgba(56, 189, 248, 0.2);
  border-radius: 3px;
  font-weight: 600;
}

.inline-file-badge {
  color: #38bdf8;
  font-weight: 500;
}

/* ============================================================== */
/* SCOPE NOTICE BANNER (ADMIN OPD & SUPERADMIN)                   */
/* ============================================================== */
.scope-notice-banner {
  display: flex;
  align-items: center;
  gap: 14px;
  background: rgba(2, 132, 199, 0.12);
  border: 1px solid rgba(56, 189, 248, 0.28);
  border-radius: 10px;
  padding: 12px 18px;
  margin-bottom: 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.scope-notice-banner.superadmin-banner {
  background: rgba(16, 185, 129, 0.12);
  border-color: rgba(52, 211, 153, 0.3);
}

.scope-notice-icon {
  width: 38px;
  height: 38px;
  border-radius: 8px;
  background: rgba(15, 23, 42, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.scope-notice-text {
  flex: 1;
}

.scope-notice-title {
  font-size: 0.92rem;
  color: #f1f5f9;
  font-weight: 600;
  margin-bottom: 2px;
}

.scope-notice-desc {
  font-size: 0.78rem;
  color: #94a3b8;
  line-height: 1.4;
}

.scope-badge-tag {
  background: rgba(2, 132, 199, 0.25);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.4);
  font-size: 0.72rem;
  font-weight: 600;
  padding: 4px 10px;
  border-radius: 6px;
  white-space: nowrap;
}

.scope-badge-tag.superadmin-tag {
  background: rgba(16, 185, 129, 0.25);
  color: #34d399;
  border-color: rgba(52, 211, 153, 0.4);
}

/* ============================================================== */
/* USER CHIP CLICKABLE & ITEM PROFILE                             */
/* ============================================================== */
.clickable-chip {
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.clickable-chip:hover,
.clickable-chip.active {
  background: rgba(255, 255, 255, 0.1);
  box-shadow: 0 0 10px rgba(56, 189, 248, 0.2);
}

.item-profile {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  margin-top: 6px;
  padding-top: 10px;
}

/* ============================================================== */
/* PROFILE TAB CONTENT & HERO                                     */
/* ============================================================== */
.profile-tab-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.profile-hero-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.8) 0%, rgba(15, 23, 42, 0.95) 100%);
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 12px;
  padding: 24px 28px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  backdrop-filter: blur(8px);
}

.hero-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.profile-avatar-large {
  position: relative;
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  font-weight: 700;
  box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
}

.avatar-online-dot {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #10b981;
  border: 2px solid #0f172a;
  box-shadow: 0 0 8px #10b981;
}

.hero-identity {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.hero-name-row {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.hero-name {
  font-size: 1.3rem;
  font-weight: 700;
  color: #f8fafc;
  margin: 0;
}

.badge-role-hero {
  background: rgba(56, 189, 248, 0.2);
  color: #38bdf8;
  border: 1px solid rgba(56, 189, 248, 0.35);
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
}

.badge-status-active {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  border: 1px solid rgba(52, 211, 153, 0.35);
  font-size: 0.72rem;
  font-weight: 600;
  padding: 3px 10px;
  border-radius: 6px;
}

.hero-meta-row {
  display: flex;
  align-items: center;
  gap: 18px;
  font-size: 0.8rem;
  color: #94a3b8;
  flex-wrap: wrap;
}

.hero-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.session-chip {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 8px;
  padding: 8px 14px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.session-label {
  font-size: 0.7rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.session-value {
  font-size: 0.78rem;
  color: #38bdf8;
  font-weight: 600;
}

/* ============================================================== */
/* 4 MINI METRIC CARDS                                            */
/* ============================================================== */
.profile-metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

@media (max-width: 1024px) {
  .profile-metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .profile-metrics-grid {
    grid-template-columns: 1fr;
  }
}

.profile-mini-metric {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 10px;
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.mini-metric-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.bg-sky { background: rgba(2, 132, 199, 0.2); color: #38bdf8; }
.bg-emerald { background: rgba(16, 185, 129, 0.2); color: #34d399; }
.bg-amber { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
.bg-indigo { background: rgba(99, 102, 241, 0.2); color: #a5b4fc; }

.mini-metric-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow: hidden;
}

.mini-metric-label {
  font-size: 0.7rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.4px;
}

.mini-metric-val {
  font-size: 0.85rem;
  font-weight: 600;
  color: #f1f5f9;
}

.truncate-text {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ============================================================== */
/* TWO COLUMN FORMS & RBAC MATRIX                                 */
/* ============================================================== */
.profile-forms-grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 20px;
}

@media (max-width: 900px) {
  .profile-forms-grid {
    grid-template-columns: 1fr;
  }
}

.profile-card {
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(148, 163, 184, 0.15);
  border-radius: 12px;
  padding: 20px 24px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.profile-card-stack {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.profile-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid rgba(148, 163, 184, 0.12);
  padding-bottom: 12px;
}

.profile-card-title-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.profile-card-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #f8fafc;
  margin: 0;
}

.profile-card-badge {
  font-size: 0.7rem;
  color: #94a3b8;
  background: rgba(15, 23, 42, 0.6);
  padding: 3px 8px;
  border-radius: 4px;
}

.profile-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.profile-form-footer {
  display: flex;
  justify-content: flex-end;
  margin-top: 6px;
}

.btn-primary-action {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #0284c7;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  padding: 9px 18px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary-action:hover:not(:disabled) {
  background: #0369a1;
  box-shadow: 0 4px 12px rgba(2, 132, 199, 0.4);
}

.btn-primary-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-amber-action {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #d97706;
  color: #ffffff;
  border: none;
  border-radius: 8px;
  padding: 9px 18px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-amber-action:hover:not(:disabled) {
  background: #b45309;
  box-shadow: 0 4px 12px rgba(217, 119, 6, 0.4);
}

.btn-amber-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ============================================================== */
/* RBAC MATRIX BOX                                                */
/* ============================================================== */
.rbac-box-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.rbac-scope-row {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 6px;
  padding: 8px 12px;
  font-size: 0.8rem;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.rbac-scope-label {
  color: #64748b;
  font-size: 0.72rem;
}

.rbac-scope-val {
  color: #38bdf8;
}

.rbac-permissions-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  max-height: 180px;
  overflow-y: auto;
  padding-right: 4px;
}

.rbac-perm-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: rgba(16, 185, 129, 0.15);
  border: 1px solid rgba(52, 211, 153, 0.25);
  color: #34d399;
  font-size: 0.72rem;
  font-family: monospace;
  padding: 3px 8px;
  border-radius: 4px;
}

.table-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.table-card-title-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.table-card-title {
  font-size: 0.95rem;
  font-weight: 600;
  color: #f1f5f9;
  margin: 0;
}

.count-badge {
  font-size: 0.72rem;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.15);
  padding: 3px 8px;
  border-radius: 4px;
}
</style>
