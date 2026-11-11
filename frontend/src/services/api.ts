import type {
  GeoJsonFeatureCollection,
  KabupatenProperties,
  KecamatanProperties,
  DesaProperties,
  ObjekSpasialProperties,
  KategoriLayer,
  HierarchyNode,
  UserRoleInfo,
  UserProfile,
  WorkflowState,
  DatasetItem,
  DataDasarVersion,
  ShpValidationReport,
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

const rawBase = import.meta.env.VITE_API_BASE_URL;
// Gunakan relative path '/spasials' agar selalu melalui Vite proxy (same-origin, bebas CORS)
// saat diakses melalui localhost, 127.0.0.1, atau IP lokal di lingkungan browser.
const API_BASE_URL = (!rawBase || rawBase.includes('localhost:8080') || rawBase.includes('127.0.0.1:8080'))
  ? '/spasials'
  : rawBase;
const API_TOKEN = import.meta.env.VITE_API_TOKEN || 'sig_minsel_sec_2026_m1n53l_k4b';

/**
 * Fetch wrapper dengan injeksi otomatis header X-API-TOKEN resmi
 */
async function request<T>(endpoint: string, options: RequestInit = {}): Promise<T> {
  const url = `${API_BASE_URL}${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`;

  const headers = new Headers(options.headers || {});
  headers.set('X-API-TOKEN', API_TOKEN);
  headers.set('Accept', 'application/json, application/geo+json');

  if (options.body && typeof options.body === 'string') {
    headers.set('Content-Type', 'application/json');
  }

  const config: RequestInit = {
    ...options,
    headers,
  };

  try {
    const response = await fetch(url, config);

    if (!response.ok) {
      let errorMessage = `HTTP Error ${response.status}: ${response.statusText}`;
      try {
        const errJson = await response.json();
        if (errJson.message) errorMessage = errJson.message;
      } catch {}
      throw new Error(errorMessage);
    }

    return response.json();
  } catch (err: any) {
    if (err.name === 'TypeError' && err.message === 'Failed to fetch') {
      // Coba fallback langsung ke port 8080 jika proxy Vite belum siap
      if (API_BASE_URL === '/spasials' && typeof window !== 'undefined') {
        try {
          const directUrl = `http://${window.location.hostname}:8080/spasials${endpoint.startsWith('/') ? endpoint : `/${endpoint}`}`;
          const directRes = await fetch(directUrl, config);
          if (directRes.ok) {
            return directRes.json();
          }
        } catch {}
      }
      throw new Error('Gagal menghubungi backend API (koneksi ditolak). Pastikan server backend aktif di port 8080.');
    }
    throw err;
  }
}

export const sigApi = {
  /**
   * Health check
   */
  async ping() {
    return request<{ status: number; message: string }>('/ping');
  },

  /**
   * Data Wilayah Induk Kabupaten Minahasa Selatan
   */
  async getKabupaten(): Promise<{ data: { properties: KabupatenProperties; geometry: any } }> {
    return request('/wilayah/kabupaten');
  },

  /**
   * Batas Seluruh 17 Kecamatan
   */
  async getKecamatan(): Promise<GeoJsonFeatureCollection<KecamatanProperties>> {
    return request('/wilayah/kecamatan');
  },

  /**
   * Batas Desa / Kelurahan (dapat difilter per kecamatan_id)
   */
  async getDesa(kecamatanId?: number): Promise<GeoJsonFeatureCollection<DesaProperties>> {
    const query = kecamatanId ? `?kecamatan_id=${kecamatanId}` : '';
    return request(`/wilayah/desa${query}`);
  },

  /**
   * Struktur Pohon Hierarki Lengkap (Kabupaten -> Kecamatan -> Desa)
   */
  async getHierarchy(): Promise<{ data: HierarchyNode }> {
    return request('/wilayah/hierarchy');
  },

  /**
   * Rekapitulasi Statistik Data Spasial
   */
  async getRekap(kecamatanId?: number) {
    const query = kecamatanId ? `?kecamatan_id=${kecamatanId}` : '';
    return request<{ data: any }>(`/wilayah/rekap${query}`);
  },

  /**
   * Daftar Kategori Layer Tematik
   */
  async getKategoriLayer(): Promise<{ data: KategoriLayer[] }> {
    return request('/layer/kategori');
  },

  /**
   * Fitur Spasial Layer Tematik dengan Filter Hierarki (kecamatan_id / desa_id / bbox)
   */
  async getLayer(
    kategoriKode: string,
    filters: { kecamatan_id?: number; desa_id?: number; bbox?: string } = {}
  ): Promise<GeoJsonFeatureCollection<ObjekSpasialProperties>> {
    const params = new URLSearchParams();
    if (filters.kecamatan_id) params.set('kecamatan_id', filters.kecamatan_id.toString());
    if (filters.desa_id) params.set('desa_id', filters.desa_id.toString());
    if (filters.bbox) params.set('bbox', filters.bbox);

    const queryStr = params.toString() ? `?${params.toString()}` : '';
    return request(`/layer/${kategoriKode}${queryStr}`);
  },

  // ==========================================
  // AUTENTIKASI & 8 ROLE
  // ==========================================

  async getRoles(): Promise<{ data: UserRoleInfo[] }> {
    return request('/auth/roles');
  },

  async login(payload: { username?: string; password?: string; role?: string }): Promise<{ data: UserProfile; message: string }> {
    return request('/auth/login', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async getProfile(params: { role?: string; id?: number } = {}): Promise<{ data: UserProfile; message: string }> {
    const searchParams = new URLSearchParams();
    if (params.role) searchParams.set('role', params.role);
    if (params.id) searchParams.set('id', params.id.toString());
    const query = searchParams.toString() ? `?${searchParams.toString()}` : '';
    return request(`/auth/profile${query}`);
  },

  async updateProfile(payload: {
    id: number;
    nama_lengkap?: string;
    email?: string;
    nip?: string;
    jabatan?: string;
    telepon?: string;
    alamat_kantor?: string;
    password_baru?: string;
  }): Promise<{ data: UserProfile; message: string }> {
    return request('/auth/profile', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  // ==========================================
  // WORKFLOW PERSETUJUAN TERPUSAT (12 STATUS)
  // ==========================================

  async getWorkflowStates(): Promise<{ data: WorkflowState[] }> {
    return request('/workflow/states');
  },

  async getWorkflowDatasets(filters: { status?: string; opd?: string } = {}): Promise<{
    data: {
      total: number;
      datasets: DatasetItem[];
      workflow_summary: {
        total_datasets: number;
        in_draft: number;
        in_validation: number;
        in_approval: number;
        published: number;
      };
    };
  }> {
    const params = new URLSearchParams();
    if (filters.status) params.set('status', filters.status);
    if (filters.opd) params.set('opd', filters.opd);
    const query = params.toString() ? `?${params.toString()}` : '';
    return request(`/workflow/datasets${query}`);
  },

  async transitionWorkflow(payload: {
    dataset_id: number;
    status: string;
    catatan?: string;
    actor_role?: string;
  }): Promise<{ data: any; message: string }> {
    return request('/workflow/transition', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  // ==========================================
  // MODUL SUPERADMIN: DATA DASAR GEOSPASIAL (SHP)
  // ==========================================

  async getDataDasar(): Promise<{
    data: {
      versions: DataDasarVersion[];
      active_versions: Record<string, string>;
      crs_wajib: string;
    };
  }> {
    return request('/admin/data-dasar');
  },

  async uploadDataDasar(payload: FormData | {
    tipe_data: string;
    sumber_resmi?: string;
    tahun_referensi?: number;
    nama_file?: string;
  }): Promise<{
    data: {
      version_record: DataDasarVersion;
      validation_report: ShpValidationReport;
    };
    message: string;
  }> {
    const isFormData = payload instanceof FormData;
    return request('/admin/data-dasar/upload', {
      method: 'POST',
      body: isFormData ? payload : JSON.stringify(payload)
    });
  },

  async activateDataDasar(id: number): Promise<{ data: DataDasarVersion; message: string }> {
    return request('/admin/data-dasar/activate', {
      method: 'POST',
      body: JSON.stringify({ id })
    });
  },

  async deleteDataDasar(id: number): Promise<{ data: { id: number }; message: string }> {
    return request('/admin/data-dasar/delete', {
      method: 'POST',
      body: JSON.stringify({ id })
    });
  },

  // ==========================================
  // CRUD DATASET SEKTORAL & UPLOAD SHP
  // ==========================================

  async createDataset(payload: {
    kode_dataset: string;
    nama_dataset: string;
    kategori: string;
    nama_kategori: string;
    opd: string;
    kode_opd: string;
    tipe_geometri: string;
    kecamatan: string;
    deskripsi?: string;
    jumlah_fitur?: number;
    submitted_by?: string;
  }): Promise<{ data: DatasetItem; message: string }> {
    return request('/workflow/datasets/create', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async uploadShpDataset(formData: FormData): Promise<{
    data: {
      dataset: DatasetItem;
      validation_report: any;
    };
    message: string;
  }> {
    return request('/workflow/datasets/upload-shp', {
      method: 'POST',
      body: formData
    });
  },

  async reuploadShpDataset(formData: FormData): Promise<{
    data: {
      dataset: DatasetItem;
      validation_report: any;
    };
    message: string;
  }> {
    return request('/workflow/datasets/reupload-shp', {
      method: 'POST',
      body: formData
    });
  },

  async downloadShpDataset(datasetId: number, fileName?: string): Promise<void> {
    const url = `${API_BASE_URL}/workflow/datasets/download/${datasetId}`;
    const res = await fetch(url, {
      headers: {
        'X-API-TOKEN': API_TOKEN
      }
    });
    if (!res.ok) {
      let msg = 'Gagal mengunduh berkas Shapefile';
      try {
        const err = await res.json();
        if (err.message) msg = err.message;
      } catch {}
      throw new Error(msg);
    }
    const blob = await res.blob();
    const blobUrl = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = blobUrl;
    a.download = fileName || `dataset_${datasetId}_shp.zip`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(blobUrl);
  },

  async updateDataset(payload: {
    id: number;
    nama_dataset?: string;
    deskripsi?: string;
    kecamatan?: string;
    kategori?: string;
    nama_kategori?: string;
    tipe_geometri?: string;
    actor_role?: string;
  }): Promise<{ data: DatasetItem; message: string }> {
    return request('/workflow/datasets/update', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async deleteDataset(payload: {
    id: number;
    actor_role?: string;
  }): Promise<{ data: { id: number; nama_dataset: string }; message: string }> {
    return request('/workflow/datasets/delete', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  // ==========================================
  // CRUD MANAJEMEN PENGGUNA & OPD
  // ==========================================

  async getUsers(params: { caller_role?: string; caller_opd?: string; kode_opd?: string; opd?: string } = {}): Promise<{ data: UserItem[]; message: string }> {
    const searchParams = new URLSearchParams();
    if (params.caller_role) searchParams.set('caller_role', params.caller_role);
    if (params.caller_opd) searchParams.set('caller_opd', params.caller_opd);
    if (params.kode_opd) searchParams.set('kode_opd', params.kode_opd);
    if (params.opd) searchParams.set('opd', params.opd);
    const query = searchParams.toString() ? `?${searchParams.toString()}` : '';
    return request(`/admin/users${query}`);
  },

  async createUser(payload: {
    username: string;
    nama_lengkap: string;
    email?: string;
    role: string;
    opd: string;
    kode_opd: string;
    caller_role?: string;
    caller_opd?: string;
    caller_kode_opd?: string;
  }): Promise<{ data: UserItem; message: string }> {
    return request('/admin/users/create', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async toggleUserStatus(payload: number | { id: number; caller_role?: string; caller_kode_opd?: string }): Promise<{ data: UserItem; message: string }> {
    const body = typeof payload === 'number' ? { id: payload } : payload;
    return request('/admin/users/toggle', {
      method: 'POST',
      body: JSON.stringify(body)
    });
  },

  async updateUser(payload: {
    id: number;
    nama_lengkap?: string;
    email?: string;
    role?: string;
    opd?: string;
    kode_opd?: string;
    nip?: string;
    jabatan?: string;
    telepon?: string;
    caller_role?: string;
    caller_opd?: string;
    caller_kode_opd?: string;
  }): Promise<{ data: UserItem; message: string }> {
    return request('/admin/users/update', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async deleteUser(payload: number | { id: number; caller_role?: string; caller_kode_opd?: string }): Promise<{ data: { id: number }; message: string }> {
    const body = typeof payload === 'number' ? { id: payload } : payload;
    return request('/admin/users/delete', {
      method: 'POST',
      body: JSON.stringify(body)
    });
  },

  // ==========================================
  // MASTER DATA: OPD (PERANGKAT DAERAH)
  // ==========================================

  async getOpds(): Promise<{ data: OpdItem[]; message: string }> {
    return request('/admin/opd');
  },

  async createOpd(payload: {
    kode_opd: string;
    nama_opd: string;
    singkatan?: string;
    email?: string;
    telepon?: string;
    alamat?: string;
    pic?: string;
  }): Promise<{ data: OpdItem; message: string }> {
    return request('/admin/opd/create', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async updateOpd(payload: {
    id: number;
    nama_opd?: string;
    singkatan?: string;
    email?: string;
    telepon?: string;
    alamat?: string;
    pic?: string;
    is_active?: boolean;
  }): Promise<{ data: OpdItem; message: string }> {
    return request('/admin/opd/update', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async deleteOpd(id: number): Promise<{ data: { id: number }; message: string }> {
    return request('/admin/opd/delete', {
      method: 'POST',
      body: JSON.stringify({ id })
    });
  },

  // ==========================================
  // MASTER DATA: KATEGORI SPASIAL TEMATIK
  // ==========================================

  async getKategoriAdmin(): Promise<{ data: KategoriSpasialItem[]; message: string }> {
    return request('/admin/kategori');
  },

  async createKategori(payload: {
    kode?: string;
    nama_kategori: string;
    deskripsi?: string;
    icon?: string;
    warna_default?: string;
    opd_id?: number;
    nama_opd?: string;
  }): Promise<{ data: KategoriSpasialItem; message: string }> {
    return request('/admin/kategori/create', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async updateKategori(payload: {
    id: number;
    nama_kategori?: string;
    deskripsi?: string;
    icon?: string;
    warna_default?: string;
    opd_id?: number;
    nama_opd?: string;
    is_active?: boolean;
  }): Promise<{ data: KategoriSpasialItem; message: string }> {
    return request('/admin/kategori/update', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async deleteKategori(id: number): Promise<{ data: { id: number }; message: string }> {
    return request('/admin/kategori/delete', {
      method: 'POST',
      body: JSON.stringify({ id })
    });
  },

  // ==========================================
  // AUDIT LOG & JEJAK AKTIVITAS SISTEM
  // ==========================================

  async getAuditLogs(params?: { kategori?: string; aksi?: string; search?: string }): Promise<{
    data: {
      logs: AuditLogItem[];
      total: number;
      filtered: number;
      kategori_list: string[];
      aksi_list: string[];
    };
    message: string;
  }> {
    const q = new URLSearchParams();
    if (params?.kategori) q.set('kategori', params.kategori);
    if (params?.aksi) q.set('aksi', params.aksi);
    if (params?.search) q.set('search', params.search);
    const qs = q.toString() ? `?${q.toString()}` : '';
    return request(`/admin/logs${qs}`);
  },

  async createAuditLog(payload: Partial<AuditLogItem>): Promise<{ data: AuditLogItem; message: string }> {
    return request('/admin/logs/create', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  // ==========================================
  // ANALITIK & STATISTIK PENGUNJUNG GEOPORTAL
  // ==========================================

  async getOverviewStats(): Promise<{ data: OverviewStats; message: string }> {
    return request('/analytics/overview');
  },

  async getVisitorSummary(): Promise<{ data: VisitorSummary; message: string }> {
    return request('/analytics/summary');
  },

  async trackVisitor(payload: { page?: string; device?: string; meta?: any }): Promise<{ data: any; message: string }> {
    return request('/analytics/track', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  // ==========================================
  // CRUD WILAYAH: KECAMATAN
  // ==========================================

  async getKecamatanAdmin(): Promise<{ data: KecamatanAdminItem[]; message: string }> {
    return request('/admin/wilayah/kecamatan');
  },

  async createKecamatan(payload: {
    nama_kecamatan: string;
    kode_kecamatan: string;
    ibukota_kecamatan?: string;
    luas_wilayah_km2?: number;
    center_lng?: number;
    center_lat?: number;
  }): Promise<{ data: KecamatanAdminItem; message: string }> {
    return request('/admin/wilayah/kecamatan/create', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async updateKecamatan(payload: {
    id: number;
    nama_kecamatan?: string;
    kode_kecamatan?: string;
    ibukota_kecamatan?: string;
    luas_wilayah_km2?: number;
    center_lng?: number;
    center_lat?: number;
  }): Promise<{ data: any; message: string }> {
    return request('/admin/wilayah/kecamatan/update', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async deleteKecamatan(id: number): Promise<{ data: { id: number }; message: string }> {
    return request('/admin/wilayah/kecamatan/delete', {
      method: 'POST',
      body: JSON.stringify({ id })
    });
  },

  // ==========================================
  // CRUD WILAYAH: KELURAHAN & DESA
  // ==========================================

  async getDesaAdmin(params?: {
    kecamatan_id?: number;
    tipe?: 'DESA' | 'KELURAHAN' | '';
    search?: string;
  }): Promise<{ data: DesaAdminItem[]; message: string }> {
    const q = new URLSearchParams();
    if (params?.kecamatan_id) q.set('kecamatan_id', params.kecamatan_id.toString());
    if (params?.tipe) q.set('tipe', params.tipe);
    if (params?.search) q.set('search', params.search);
    const qs = q.toString() ? `?${q.toString()}` : '';
    return request(`/admin/wilayah/desa${qs}`);
  },

  async createDesa(payload: {
    nama_desa: string;
    kode_desa?: string;
    tipe?: 'DESA' | 'KELURAHAN';
    kecamatan_id: number;
    luas_wilayah_km2?: number;
    center_lng?: number;
    center_lat?: number;
  }): Promise<{ data: DesaAdminItem; message: string }> {
    return request('/admin/wilayah/desa/create', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async updateDesa(payload: {
    id: number;
    nama_desa?: string;
    kode_desa?: string;
    tipe?: 'DESA' | 'KELURAHAN';
    kecamatan_id?: number;
    luas_wilayah_km2?: number;
    center_lng?: number;
    center_lat?: number;
  }): Promise<{ data: any; message: string }> {
    return request('/admin/wilayah/desa/update', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async deleteDesa(id: number): Promise<{ data: { id: number }; message: string }> {
    return request('/admin/wilayah/desa/delete', {
      method: 'POST',
      body: JSON.stringify({ id })
    });
  },

  // ==========================================
  // CRUD BASE MAP (PETA DASAR) SUPERADMIN
  // ==========================================

  async getPublicBasemaps(): Promise<{
    data: {
      basemaps: BaseMapItem[];
      default: BaseMapItem | null;
    };
    message: string;
  }> {
    return request('/basemaps');
  },

  async getAdminBasemaps(): Promise<{ data: BaseMapItem[]; message: string }> {
    return request('/admin/basemaps');
  },

  async createBasemap(payload: {
    name?: string;
    label: string;
    desc?: string;
    type?: 'raster' | 'vector';
    tile_url: string;
    attribution?: string;
    max_zoom?: number;
    min_zoom?: number;
    preview_color?: string;
  }): Promise<{ data: BaseMapItem; message: string }> {
    return request('/admin/basemaps/create', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async updateBasemap(payload: {
    id: number;
    label?: string;
    desc?: string;
    type?: 'raster' | 'vector';
    tile_url?: string;
    attribution?: string;
    max_zoom?: number;
    min_zoom?: number;
    preview_color?: string;
  }): Promise<{ data: BaseMapItem; message: string }> {
    return request('/admin/basemaps/update', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async toggleBasemap(id: number): Promise<{ data: BaseMapItem; message: string }> {
    return request('/admin/basemaps/toggle', {
      method: 'POST',
      body: JSON.stringify({ id })
    });
  },

  async setDefaultBasemap(id: number): Promise<{ data: BaseMapItem; message: string }> {
    return request('/admin/basemaps/set-default', {
      method: 'POST',
      body: JSON.stringify({ id })
    });
  },

  async deleteBasemap(id: number): Promise<{ data: { id: number }; message: string }> {
    return request('/admin/basemaps/delete', {
      method: 'POST',
      body: JSON.stringify({ id })
    });
  }
};
