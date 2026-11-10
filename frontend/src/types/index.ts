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
  kecamatan?: string;
  crs: string;
  submitted_by: string;
  updated_at: string;
  deskripsi?: string;
  catatan_validasi?: string;
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
