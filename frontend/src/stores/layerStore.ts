import { defineStore } from 'pinia';
import { sigApi } from '../services/api';
import type { KategoriLayer, BaseMapItem } from '../types';

export const useLayerStore = defineStore('layer', {
  state: () => ({
    activeBasemap: 'carto-positron' as string,
    basemaps: [] as BaseMapItem[],
    categories: [] as KategoriLayer[],
    activeLayers: ['admin_wilayah', 'pemerintahan', 'kesehatan', 'pariwisata'] as string[],
    isFocusMinselActive: true, // Tampilan Default: Blur + Grayscale Luar Minsel Aktif
    isLoading: false,
  }),

  actions: {
    async loadBasemaps() {
      try {
        const res = await sigApi.getPublicBasemaps();
        if (res.data?.basemaps?.length) {
          this.basemaps = res.data.basemaps;
          if (res.data.default?.name) {
            this.activeBasemap = res.data.default.name;
          }
        }
      } catch (err) {
        console.warn('Gagal memuat basemap dari API:', err);
      }
    },

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

    setBasemap(name: string) {
      this.activeBasemap = name;
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
