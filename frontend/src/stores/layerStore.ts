import { defineStore } from 'pinia';
import { sigApi } from '../services/api';
import type { KategoriLayer } from '../types';

export type BasemapType =
  | 'carto-positron'
  | 'carto-dark'
  | 'carto-voyager'
  | 'osm'
  | 'esri-topo'
  | 'esri-street'
  | 'esri-satellite'
  | 'opentopo';

export const useLayerStore = defineStore('layer', {
  state: () => ({
    activeBasemap: 'carto-positron' as BasemapType,
    categories: [] as KategoriLayer[],
    activeLayers: ['admin_wilayah', 'pemerintahan', 'kesehatan', 'pariwisata'] as string[],
    isFocusMinselActive: true, // Tampilan Default: Blur + Grayscale Luar Minsel Aktif
    isLoading: false,
  }),

  actions: {
    async loadCategories() {
      this.isLoading = true;
      try {
        const res = await sigApi.getKategoriLayer();
        if (res.data) {
          this.categories = res.data;
        }
      } catch (err) {
        console.warn('Gagal memuat kategori layer dari API:', err);
      } finally {
        this.isLoading = false;
      }
    },

    setBasemap(type: BasemapType) {
      this.activeBasemap = type;
    },

    toggleLayer(kode: string) {
      const idx = this.activeLayers.indexOf(kode);
      if (idx > -1) {
        this.activeLayers.splice(idx, 1);
      } else {
        this.activeLayers.push(kode);
      }
    },

    isLayerActive(kode: string): boolean {
      return this.activeLayers.includes(kode);
    },

    toggleFocusMinsel() {
      this.isFocusMinselActive = !this.isFocusMinselActive;
    }
  }
});
