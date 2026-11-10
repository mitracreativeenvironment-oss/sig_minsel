import type {
  GeoJsonFeatureCollection,
  KabupatenProperties,
  KecamatanProperties,
  DesaProperties,
  ObjekSpasialProperties,
  KategoriLayer,
  HierarchyNode
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
};

