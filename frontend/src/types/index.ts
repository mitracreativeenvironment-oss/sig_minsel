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

