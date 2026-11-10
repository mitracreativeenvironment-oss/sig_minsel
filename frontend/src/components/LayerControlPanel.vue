<script setup lang="ts">
import { useLayerStore, type BasemapType } from '../stores/layerStore';
import { Layers, Map as MapIcon, Hospital, Landmark, Trees, ShieldAlert, Check } from 'lucide-vue-next';

const layerStore = useLayerStore();

const basemapOptions: { id: BasemapType; label: string; desc: string }[] = [
  { id: 'carto-positron', label: 'Positron (Light)', desc: 'Peta terang minimalis' },
  { id: 'osm', label: 'OpenStreetMap', desc: 'Peta standar OSM' },
  { id: 'carto-dark', label: 'Dark Matter', desc: 'Peta kontras gelap' },
  { id: 'esri-satellite', label: 'Satelit Citra', desc: 'Citra satelit resolusi tinggi' },
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
    <!-- Basemap Selector -->
    <div class="control-section">
      <div class="section-title">
        <MapIcon :size="16" class="icon-section" />
        <span>Peta Dasar (Basemap)</span>
      </div>
      <div class="basemap-grid">
        <button
          v-for="b in basemapOptions"
          :key="b.id"
          :class="['basemap-btn', { active: layerStore.activeBasemap === b.id }]"
          @click="layerStore.setBasemap(b.id)"
        >
          <span class="btn-label">{{ b.label }}</span>
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
          <span class="color-dot" :style="{ backgroundColor: layer.color }"></span>
          <span class="thematic-label">{{ layer.label }}</span>
        </label>
      </div>
    </div>
  </div>
</template>

<style scoped>
.layer-control-card {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
  overflow: hidden;
  font-family: inherit;
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding: 16px;
}

.control-section {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
}

.icon-section {
  color: #2563eb;
}

.basemap-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.basemap-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 10px;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 11px;
  font-weight: 500;
  color: #334155;
  cursor: pointer;
  transition: all 0.2s;
}

.basemap-btn:hover {
  background: #f1f5f9;
  border-color: #94a3b8;
}

.basemap-btn.active {
  background: #eff6ff;
  border-color: #2563eb;
  color: #1d4ed8;
  font-weight: 600;
}

.icon-check {
  color: #2563eb;
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
  padding: 8px 10px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #f1f5f9;
  cursor: pointer;
  transition: background 0.2s;
  user-select: none;
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

.color-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.thematic-label {
  font-size: 12px;
  font-weight: 500;
  color: #1e293b;
}
</style>

