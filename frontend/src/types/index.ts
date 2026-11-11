export interface GeoJsonGeometry {
  type: string;
  coordinates: any;
}

export interface GeoJsonFeature<P = Record<string, any>> {
  type: 'Feature';
  id?: number | string;
  geometry: GeoJsonGeometry;
  properties: P;
}

export interface GeoJsonFeatureCollection<P = Record<string, any>> {
  type: 'FeatureCollection';
  features: GeoJsonFeature<P>[];
  total?: number;
  [key: string]: any;
}

export interface KabupatenProperties {
  id: number;
  kode: string;
  nama: string;
  ibukota: string;
  luas_km2: number;
  deskripsi?: string;
  center?: { lng: number; lat: number };
  jumlah_kecamatan?: number;
}

export interface KecamatanProperties {
  id: number;
  kabupaten_id: number;
  kabupaten?: string;
  kode: string;
  nama: string;
  ibukota?: string;
  luas_km2?: number;
  center?: { lng: number; lat: number };
}

export interface DesaProperties {
  id: number;
  kabupaten_id: number;
  kabupaten?: string;
  kecamatan_id: number;
  kecamatan?: string;
  kode: string;
  nama: string;
  tipe: 'DESA' | 'KELURAHAN';
  luas_km2?: number;
  center?: { lng: number; lat: number };
}

export interface ObjekSpasialProperties {
  id: number;
  nama: string;
  tipe_geometri: string;
  kategori: string;
  nama_kategori: string;
  kabupaten_id: number;
  kecamatan_id?: number | null;
  kecamatan?: string | null;
  desa_id?: number | null;
  desa?: string | null;
  opd_id?: number | null;
  opd?: string | null;
  deskripsi?: string | null;
  foto?: string | null;
  atribut?: Record<string, any>;
}

export interface KategoriLayer {
  id: number;
  kode: string;
  nama_kategori: string;
  deskripsi?: string;
  icon?: string;
  warna_default?: string;
  opd_id?: number;
  nama_opd?: string;
}

export interface HierarchyNode {
  kabupaten: {
    id: number;
    kode: string;
    nama: string;
    ibukota: string;
    total_kecamatan: number;
    total_desa: number;
    kecamatan: {
      id: number;
      kode: string;
      nama: string;
      ibukota?: string;
      jumlah_desa: number;
      desa: {
        id: number;
        kode: string;
        nama: string;
        tipe: 'DESA' | 'KELURAHAN';
      }[];
    }[];
  };
}

// ==========================================
// FASE 2: ROLE & KEWENANGAN PENGGUNA
// ==========================================

export type RoleName = 
  | 'superadmin' 
  | 'admin_gis' 
  | 'admin_opd' 
  | 'operator_opd' 
  | 'validator_gis' 
  | 'approver_data' 
  | 'pimpinan' 
  | 'publik';

export interface UserRoleInfo {
  id: number;
  name: RoleName;
  label: string;
  deskripsi: string;
  opd: string;
  kode_opd: string;
  permissions: string[];
}

export interface UserProfile {
  id: number;
  username: string;
  nama_lengkap: string;
  email: string;
  role: RoleName;
  role_label: string;
  opd: string;
  kode_opd: string;
  permissions: string[];
  token: string;
  logged_at?: string;
  nip?: string;
  jabatan?: string;
  telepon?: string;
  alamat_kantor?: string;
  total_datasets?: number;
  dataset_tayang?: number;
  dataset_review?: number;
}

export interface UserItem {
  id: number;
  username: string;
  nama_lengkap: string;
  email: string;
  role: RoleName;
  role_label: string;
  opd: string;
  kode_opd: string;
  is_active: boolean;
  created_at: string;
  nip?: string;
  jabatan?: string;
  telepon?: string;
  alamat_kantor?: string;
}

// ==========================================
// FASE 2: 12 TAHAPAN WORKFLOW PERSETUJUAN TERPUSAT
// ==========================================

export type WorkflowStatusCode = 
  | 'DRAFT'
  | 'SUBMITTED'
  | 'IN_REVIEW_OPD'
  | 'APPROVED_OPD'
  | 'SUBMITTED_GIS'
  | 'IN_VALIDATION_GIS'
  | 'REJECTED_GIS'
  | 'APPROVED_GIS'
  | 'SUBMITTED_APPROVAL'
  | 'IN_APPROVAL'
  | 'APPROVED'
  | 'PUBLISHED';

export interface WorkflowState {
  code: WorkflowStatusCode;
  label: string;
  step: number;
  actor: RoleName;
  color: string;
  deskripsi: string;
}

export interface DatasetItem {
  id: number;
  kode_dataset: string;
  nama_dataset: string;
  kategori: string;
  nama_kategori: string;
  opd: string;
  kode_opd: string;
  tipe_geometri: 'Point' | 'LineString' | 'Polygon';
  jumlah_fitur: number;
  status: WorkflowStatusCode;
  versi?: string;
  revisi_ke?: number;
  kecamatan?: string;
  crs: string;
  submitted_by: string;
  updated_at: string;
  deskripsi?: string;
  catatan_validasi?: string;
  has_shp_file?: boolean;
  file_name?: string;
  file_path?: string;
  file_size?: number;
  file_size_formatted?: string;
  shp_components?: string[];
  bbox?: { x_min: number; y_min: number; x_max: number; y_max: number };
}

// ==========================================
// MODUL SUPERADMIN: DATA DASAR GEOSPASIAL (SHP)
// ==========================================

export interface DataDasarVersion {
  id: number;
  tipe_data: 'KABUPATEN' | 'KECAMATAN' | 'DESA' | 'JARINGAN_JALAN' | 'SUNGAI';
  label_tipe: string;
  versi: string;
  nama_file_zip: string;
  sumber_resmi: string;
  tahun_referensi: number;
  crs_terdeteksi: string;
  jumlah_fitur: number;
  status: 'DRAFT' | 'AKTIF' | 'DIGANTIKAN' | 'DIARSIPKAN';
  uploaded_by: string;
  activated_at?: string | null;
  catatan?: string;
}

export interface ShpValidationReport {
  has_shp: boolean;
  has_shx: boolean;
  has_dbf: boolean;
  has_prj: boolean;
  crs: string;
  crs_valid: boolean;
  topology: string;
  features: number;
}

// ==========================================
// MASTER DATA: OPD & KATEGORI SPASIAL
// ==========================================

export interface OpdItem {
  id: number;
  kode_opd: string;
  nama_opd: string;
  singkatan?: string;
  email: string;
  telepon: string;
  alamat: string;
  pic: string;
  is_active: boolean;
  total_datasets?: number;
  total_kategori?: number;
  created_at?: string;
}

export interface KategoriSpasialItem {
  id: number;
  kode: string;
  nama_kategori: string;
  deskripsi: string;
  icon: string;
  warna_default: string;
  opd_id: number;
  nama_opd: string;
  is_active: boolean;
  total_datasets?: number;
}

// ==========================================
// AUDIT LOG & JEJAK AKTIVITAS SISTEM
// ==========================================

export interface AuditLogItem {
  id: number;
  kategori: string;
  aksi: string;
  aktor: string;
  role: string;
  opd: string;
  objek: string;
  keterangan: string;
  versi_terkait?: string;
  ip_address?: string;
  timestamp: string;
}

// ==========================================
// ANALITIK & STATISTIK PENGUNJUNG GEOPORTAL
// ==========================================

export interface VisitorSummary {
  total_kunjungan: number;
  kunjungan_hari_ini: number;
  kunjungan_bulan_ini: number;
  pengunjung_unik: number;
  perangkat: {
    desktop: number;
    mobile: number;
    tablet: number;
  };
  tren_mingguan: Array<{
    tanggal: string;
    hits: number;
  }>;
  halaman_populer: Array<{
    page: string;
    hits: number;
  }>;
  terakhir_diperbarui: string;
}

// ==========================================
// CRUD WILAYAH: KECAMATAN, KELURAHAN & DESA
// ==========================================

export interface KecamatanAdminItem {
  id: number;
  kabupaten_id: number;
  kode_kecamatan: string;
  nama_kecamatan: string;
  ibukota_kecamatan: string;
  luas_wilayah_km2: number;
  center_lng: number;
  center_lat: number;
  total_desa: number;
}

export interface DesaAdminItem {
  id: number;
  kabupaten_id: number;
  kecamatan_id: number;
  nama_kecamatan: string;
  kode_desa: string;
  nama_desa: string;
  tipe: 'DESA' | 'KELURAHAN';
  luas_wilayah_km2: number;
  center_lng: number;
  center_lat: number;
}

// ==========================================
// CRUD BASE MAP (PETA DASAR) SUPERADMIN
// ==========================================

export interface BaseMapItem {
  id: number;
  name: string;
  label: string;
  desc: string;
  type: 'raster' | 'vector';
  tile_url: string;
  attribution: string;
  max_zoom: number;
  min_zoom: number;
  preview_color: string;
  is_active: boolean;
  is_default: boolean;
  is_system?: boolean;
  created_at?: string;
}

// ==========================================
// RINGKASAN EKSEKUTIF REAL DATABASE (OVERVIEW)
// ==========================================

export interface OverviewStats {
  kpi: {
    total_kecamatan: number;
    total_kecamatan_mapped: number;
    cakupan_persen: number;
    total_desa: number;
    total_desa_murni: number;
    total_kelurahan: number;
    total_kategori: number;
    total_pilar: number;
    total_workflow_stages: number;
  };
  geometri: {
    labels: string[];
    counts: number[];
    polygon: number;
    line: number;
    point: number;
    total_objek: number;
    features: {
      polygon: number;
      line: number;
      point: number;
      total: number;
    };
  };
  pipeline: {
    labels: string[];
    counts: number[];
    draft: number;
    validasi: number;
    approval: number;
    published: number;
    total: number;
  };
  visitors: {
    labels: string[];
    hits: number[];
  };
}
