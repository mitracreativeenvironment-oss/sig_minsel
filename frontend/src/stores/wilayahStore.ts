import { defineStore } from 'pinia';
import { sigApi } from '../services/api';
import type { HierarchyNode, KecamatanProperties, DesaProperties } from '../types';

export const useWilayahStore = defineStore('wilayah', {
  state: () => ({
    kabupatenNama: 'Minahasa Selatan',
    kabupatenKode: '71.05',
    kabupatenIbukota: 'Amurang',
    selectedKecamatanId: null as number | null,
    selectedDesaId: null as number | null,
    hierarchy: null as HierarchyNode | null,
    kecamatanList: [
      { id: 1, kabupaten_id: 1, kode: '71.05.01', nama: 'Modoinding', ibukota: 'Linelean', luas_km2: 53.61, center: { lng: 124.4500, lat: 0.8550 } },
      { id: 2, kabupaten_id: 1, kode: '71.05.02', nama: 'Tompaso Baru', ibukota: 'Tompaso Baru Dua', luas_km2: 126.11, center: { lng: 124.4700, lat: 0.9550 } },
      { id: 3, kabupaten_id: 1, kode: '71.05.03', nama: 'Ranoyapo', ibukota: 'Pontak', luas_km2: 93.71, center: { lng: 124.5300, lat: 1.0000 } },
      { id: 4, kabupaten_id: 1, kode: '71.05.04', nama: 'Motoling', ibukota: 'Motoling', luas_km2: 26.68, center: { lng: 124.5350, lat: 1.0500 } },
      { id: 5, kabupaten_id: 1, kode: '71.05.05', nama: 'Sinonsayang', ibukota: 'Ongkaw Dua', luas_km2: 133.40, center: { lng: 124.3750, lat: 1.0750 } },
      { id: 6, kabupaten_id: 1, kode: '71.05.06', nama: 'Tenga', ibukota: 'Pakuweru', luas_km2: 114.77, center: { lng: 124.4650, lat: 1.1400 } },
      { id: 7, kabupaten_id: 1, kode: '71.05.07', nama: 'Amurang', ibukota: 'Buyungon', luas_km2: 10.66, center: { lng: 124.5772, lat: 1.1824 } },
      { id: 8, kabupaten_id: 1, kode: '71.05.08', nama: 'Tumpaan', ibukota: 'Tumpaan Satu', luas_km2: 73.16, center: { lng: 124.6400, lat: 1.2400 } },
      { id: 9, kabupaten_id: 1, kode: '71.05.09', nama: 'Tareran', ibukota: 'Rumoong Atas', luas_km2: 58.74, center: { lng: 124.6950, lat: 1.2650 } },
      { id: 10, kabupaten_id: 1, kode: '71.05.10', nama: 'Kumelembuai', ibukota: 'Kumelembuai', luas_km2: 45.47, center: { lng: 124.5800, lat: 1.1050 } },
      { id: 11, kabupaten_id: 1, kode: '71.05.11', nama: 'Maesaan', ibukota: 'Tumani', luas_km2: 47.78, center: { lng: 124.4700, lat: 0.9000 } },
      { id: 12, kabupaten_id: 1, kode: '71.05.12', nama: 'Amurang Barat', ibukota: 'Kawangkoan Bawah', luas_km2: 88.58, center: { lng: 124.5200, lat: 1.1700 } },
      { id: 13, kabupaten_id: 1, kode: '71.05.13', nama: 'Amurang Timur', ibukota: 'Malenos Baru', luas_km2: 178.26, center: { lng: 124.6250, lat: 1.1750 } },
      { id: 14, kabupaten_id: 1, kode: '71.05.14', nama: 'Tatapaan', ibukota: 'Paslaten', luas_km2: 65.48, center: { lng: 124.6400, lat: 1.2900 } },
      { id: 15, kabupaten_id: 1, kode: '71.05.15', nama: 'Suluun Tareran', ibukota: 'Suluun Empat', luas_km2: 32.22, center: { lng: 124.6950, lat: 1.2200 } },
      { id: 16, kabupaten_id: 1, kode: '71.05.16', nama: 'Motoling Barat', ibukota: 'Raanan Baru', luas_km2: 113.67, center: { lng: 124.4800, lat: 1.0500 } },
      { id: 17, kabupaten_id: 1, kode: '71.05.17', nama: 'Motoling Timur', ibukota: 'Wanga', luas_km2: 55.45, center: { lng: 124.5700, lat: 1.0500 } }
    ] as KecamatanProperties[],
    desaList: [
      { id: 1, kabupaten_id: 1, kecamatan_id: 7, kode: '71.05.07.1001', nama: 'Buyungon', tipe: 'KELURAHAN', luas_km2: 1.85 },
      { id: 2, kabupaten_id: 1, kecamatan_id: 7, kode: '71.05.07.1002', nama: 'Pondang', tipe: 'KELURAHAN', luas_km2: 2.10 },
      { id: 3, kabupaten_id: 1, kecamatan_id: 7, kode: '71.05.07.1003', nama: 'Ranomeida', tipe: 'KELURAHAN', luas_km2: 1.65 },
      { id: 4, kabupaten_id: 1, kecamatan_id: 8, kode: '71.05.08.2001', nama: 'Tumpaan Satu', tipe: 'DESA', luas_km2: 5.20 },
      { id: 5, kabupaten_id: 1, kecamatan_id: 8, kode: '71.05.08.2002', nama: 'Popontolen', tipe: 'DESA', luas_km2: 6.40 },
      { id: 6, kabupaten_id: 1, kecamatan_id: 1, kode: '71.05.01.2001', nama: 'Linelean', tipe: 'DESA', luas_km2: 7.80 }
    ] as DesaProperties[],
    rekap: null as any,
    isLoading: false,
    error: null as string | null,
  }),

  getters: {
    selectedKecamatan(state) {
      if (!state.selectedKecamatanId) return null;
      return state.kecamatanList.find(k => k.id === state.selectedKecamatanId) || null;
    },

    selectedDesa(state) {
      if (!state.selectedDesaId) return null;
      return state.desaList.find(d => d.id === state.selectedDesaId) || null;
    },

    currentScopeLabel(state): string {
      if (state.selectedDesaId) {
        const desa = state.desaList.find(d => d.id === state.selectedDesaId);
        const kec = state.kecamatanList.find(k => k.id === state.selectedKecamatanId);
        return `${desa ? desa.nama : 'Desa'} - Kec. ${kec ? kec.nama : ''}`;
      }
      if (state.selectedKecamatanId) {
        const kec = state.kecamatanList.find(k => k.id === state.selectedKecamatanId);
        return `Kecamatan ${kec ? kec.nama : ''}`;
      }
      return 'Seluruh Kabupaten Minahasa Selatan';
    },

    filteredDesaList(state) {
      if (!state.selectedKecamatanId) return [];
      return state.desaList.filter(d => d.kecamatan_id === state.selectedKecamatanId);
    }
  },

  actions: {
    async init() {
      this.isLoading = true;
      this.error = null;
      try {
        // 1. Muat data hierarki
        const res = await sigApi.getHierarchy();
        if (res.data) {
          this.hierarchy = res.data;
          this.kabupatenNama = res.data.kabupaten.nama;
          this.kabupatenKode = res.data.kabupaten.kode;
          this.kabupatenIbukota = res.data.kabupaten.ibukota;
        }

        // 2. Muat data kecamatan
        const kecRes = await sigApi.getKecamatan();
        if (kecRes.features) {
          this.kecamatanList = kecRes.features.map(f => f.properties);
        }

        // 3. Muat data desa awal
        const desaRes = await sigApi.getDesa();
        if (desaRes.features) {
          this.desaList = desaRes.features.map(f => f.properties);
        }

        // 4. Muat rekapitulasi awal
        const rekapRes = await sigApi.getRekap();
        if (rekapRes.data) {
          this.rekap = rekapRes.data;
        }
      } catch (err: any) {
        this.error = err.message || 'Gagal memuat data wilayah';
        console.warn('Gagal memuat API wilayah, menggunakan fallback data lokal:', err);
      } finally {
        this.isLoading = false;
      }
    },

    setKecamatan(kecamatanId: number | null) {
      this.selectedKecamatanId = kecamatanId;
      this.selectedDesaId = null; // Reset pemilihan desa saat kecamatan berganti
      this.refreshRekap();
    },

    setDesa(desaId: number | null) {
      this.selectedDesaId = desaId;
    },

    resetToKabupaten() {
      this.selectedKecamatanId = null;
      this.selectedDesaId = null;
      this.refreshRekap();
    },

    async refreshRekap() {
      try {
        const rekapRes = await sigApi.getRekap(this.selectedKecamatanId || undefined);
        if (rekapRes.data) {
          this.rekap = rekapRes.data;
        }
      } catch (err) {
        console.warn('Gagal memuat rekap:', err);
      }
    }
  }
});

