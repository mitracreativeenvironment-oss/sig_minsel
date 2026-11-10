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
    kecamatanList: [] as KecamatanProperties[],
    desaList: [] as DesaProperties[],
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

