<script setup lang="ts">
import { useLayerStore, type BasemapType } from '../stores/layerStore';
import { Layers, Map as MapIcon, Hospital, Landmark, Trees, ShieldAlert, Check, Eye } from 'lucide-vue-next';

const layerStore = useLayerStore();

interface BasemapOption {
  id: BasemapType;
  label: string;
  category: string;
  badge?: string;
}

const basemapOptions: BasemapOption[] = [
  { id: 'carto-positron', label: 'Positron (Light)', category: 'Minimalis', badge: 'Rekomendasi' },
  { id: 'carto-voyager', label: 'Voyager (Lengkap)', category: 'Jalan & POI' },
  { id: 'esri-street', label: 'Esri Street Poros', category: 'Jalan Nasional' },
  { id: 'osm', label: 'OpenStreetMap', category: 'Komunitas OSM' },
  { id: 'esri-topo', label: 'Esri Topografis', category: 'Relief & Kontur' },
  { id: 'opentopo', label: 'OpenTopoMap', category: 'Garis Elevasi' },
  { id: 'esri-satellite', label: 'Citra Satelit', category: 'Foto Udara' },
  { id: 'carto-dark', label: 'Dark Matter', category: 'Mode Gelap' }
];

const thematicLayers = [
  { kode: 'admin_wilayah', label: 'Batas Kecamatan & Desa', icon: Landmark, color: '#2563eb' },
  { kode: 'pemerintahan', label: 'Kantor Pemerintahan', icon: Landmark, color: '#0284c7' },
  { kode: 'kesehatan', label: 'Fasilitas Kesehatan', icon: Hospital, color: '#dc2626' },
  { kode: 'pariwisata', label: 'Pariwisata & Budaya', icon: Trees, color: '#16a34a' },
  { kode: 'kebencanaan', label: 'Titik Rawan Bencana', icon: ShieldAlert, color: '#ea580c' },
];
</script>

<template>
  <div class="layer-control-card">
    <!-- Fokus Wilayah Minahasa Selatan (Blur + Grayscale Luar Minsel) -->
    <div class="control-section focus-section">
      <div class="focus-row">
        <div class="focus-info">
          <div class="focus-title">
            <Eye :size="15" class="icon-focus" />
            <span>Fokus Teritorial Minsel</span>
          </div>
          <span class="focus-sub">Blur &amp; Desaturasi wilayah luar Minsel</span>
        </div>
        <label class="toggle-switch-sm" title="Aktifkan/Nonaktifkan Fokus Minsel">
          <input
            type="checkbox"
            :checked="layerStore.isFocusMinselActive"
            @change="layerStore.toggleFocusMinsel()"
          />
          <span class="slider-sm"></span>
        </label>
      </div>
    </div>

    <!-- Basemap Selector (8 Pilihan Lengkap & Free) -->
    <div class="control-section">
      <div class="section-title">
        <MapIcon :size="16" class="icon-section" />
        <span>Peta Dasar Bebas Akses ({{ basemapOptions.length }} Basemap)</span>
      </div>
      <div class="basemap-grid">
        <button
          v-for="b in basemapOptions"
          :key="b.id"
          :class="['basemap-btn', { active: layerStore.activeBasemap === b.id }]"
          @click="layerStore.setBasemap(b.id)"
        >
          <div class="bm-info">
            <span class="btn-label">{{ b.label }}</span>
            <span class="btn-cat">{{ b.category }}</span>
          </div>
          <span v-if="b.badge" class="bm-badge">{{ b.badge }}</span>
          <Check v-if="layerStore.activeBasemap === b.id" :size="14" class="icon-check" />
        </button>
      </div>
    </div>

    <!-- Thematic Layers -->
    <div class="control-section">
      <div class="section-title">
        <Layers :size="16" class="icon-section" />
        <span>Layer Data Tematik</span>
      </div>
      <div class="thematic-list">
        <label
          v-for="layer in thematicLayers"
          :key="layer.kode"
          class="thematic-item"
        >
          <input
            type="checkbox"
            class="thematic-checkbox"
            :checked="layerStore.isLayerActive(layer.kode)"
            @change="layerStore.toggleLayer(layer.kode)"
          />
          <span class="color-badge" :style="{ backgroundColor: layer.color }"></span>
          <span class="layer-name">{{ layer.label }}</span>
        </label>
      </div>
    </div>
  </div>
</template>

<style scoped>
.layer-control-card {
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.control-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

/* Fokus Minsel Section Styling */
.focus-section {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 10px 12px;
}

.focus-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.focus-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.focus-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 700;
  color: #0f172a;
}

.icon-focus {
  color: #0284c7;
}

.focus-sub {
  font-size: 10px;
  color: #64748b;
}

/* Toggle Switch Small */
.toggle-switch-sm {
  position: relative;
  display: inline-block;
  width: 40px;
  height: 22px;
  cursor: pointer;
  flex-shrink: 0;
}

.toggle-switch-sm input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider-sm {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  border-radius: 22px;
  transition: 0.25s;
}

.slider-sm:before {
  position: absolute;
  content: "";
  height: 16px;
  width: 16px;
  left: 3px;
  bottom: 3px;
  background-color: #ffffff;
  border-radius: 50%;
  transition: 0.25s;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

input:checked + .slider-sm {
  background: #0284c7;
}

input:checked + .slider-sm:before {
  transform: translateX(18px);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #1e293b;
}

.icon-section {
  color: #2563eb;
}

.basemap-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  max-height: 220px;
  overflow-y: auto;
  padding-right: 2px;
}

.basemap-grid::-webkit-scrollbar {
  width: 4px;
}
.basemap-grid::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 2px;
}

.basemap-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  cursor: pointer;
  text-align: left;
  transition: all 0.2s ease;
  position: relative;
}

.basemap-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.basemap-btn.active {
  background: #eff6ff;
  border-color: #3b82f6;
  color: #1d4ed8;
  font-weight: 600;
}

.bm-info {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.btn-label {
  font-size: 11px;
  font-weight: 600;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.basemap-btn.active .btn-label {
  color: #1d4ed8;
}

.btn-cat {
  font-size: 9px;
  color: #64748b;
}

.bm-badge {
  font-size: 8px;
  background: #dbeafe;
  color: #1e40af;
  padding: 1px 4px;
  border-radius: 4px;
  font-weight: 700;
}

.icon-check {
  color: #2563eb;
  flex-shrink: 0;
  margin-left: 4px;
}

.thematic-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.thematic-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 12px;
  color: #334155;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 6px;
  transition: background 0.15s ease;
}

.thematic-item:hover {
  background: #f1f5f9;
}

.thematic-checkbox {
  width: 16px;
  height: 16px;
  accent-color: #2563eb;
  cursor: pointer;
}

.color-badge {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.layer-name {
  flex: 1;
  font-weight: 500;
}
</style>
