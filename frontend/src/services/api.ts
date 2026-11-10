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
  VisitorSummary
} from '../types';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080/spasials';
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

  async uploadDataDasar(payload: {
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
    return request('/admin/data-dasar/upload', {
      method: 'POST',
      body: JSON.stringify(payload)
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
  // CRUD DATASET SEKTORAL
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

  async getUsers(): Promise<{ data: UserItem[]; message: string }> {
    return request('/admin/users');
  },

  async createUser(payload: {
    username: string;
    nama_lengkap: string;
    email?: string;
    role: string;
    opd: string;
    kode_opd: string;
  }): Promise<{ data: UserItem; message: string }> {
    return request('/admin/users/create', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async toggleUserStatus(id: number): Promise<{ data: UserItem; message: string }> {
    return request('/admin/users/toggle', {
      method: 'POST',
      body: JSON.stringify({ id })
    });
  },

  async updateUser(payload: {
    id: number;
    nama_lengkap?: string;
    email?: string;
    role?: string;
    opd?: string;
    kode_opd?: string;
  }): Promise<{ data: UserItem; message: string }> {
    return request('/admin/users/update', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  },

  async deleteUser(id: number): Promise<{ data: { id: number }; message: string }> {
    return request('/admin/users/delete', {
      method: 'POST',
      body: JSON.stringify({ id })
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

  async getVisitorSummary(): Promise<{ data: VisitorSummary; message: string }> {
    return request('/analytics/summary');
  },

  async trackVisitor(payload: { page?: string; device?: string; meta?: any }): Promise<{ data: any; message: string }> {
    return request('/analytics/track', {
      method: 'POST',
      body: JSON.stringify(payload)
    });
  }
};
