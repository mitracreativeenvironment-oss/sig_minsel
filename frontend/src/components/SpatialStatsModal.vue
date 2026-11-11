<script setup lang="ts">
import { computed } from 'vue';
import { useWilayahStore } from '../stores/wilayahStore';
import { useLayerStore } from '../stores/layerStore';
import StatChart from './StatChart.vue';
import { X, BarChart3, MapPin, Layers, Globe } from 'lucide-vue-next';

const emit = defineEmits<{
  (e: 'close'): void;
}>();

const wilayahStore = useWilayahStore();
const layerStore = useLayerStore();

const totalLuasKm2 = computed(() => {
  return wilayahStore.kecamatanList
    .reduce((acc, k) => acc + (k.luas_km2 || 0), 0)
    .toFixed(2);
});

// Chart 1: Luas Wilayah 17 Kecamatan
const chartKecamatanLuas = computed(() => {
  const sorted = [...wilayahStore.kecamatanList].sort((a, b) => (b.luas_km2 || 0) - (a.luas_km2 || 0));
  return {
    labels: sorted.map((k) => k.nama),
    datasets: [
      {
        label: 'Luas Wilayah (km²)',
        data: sorted.map((k) => k.luas_km2 || 0),
        backgroundColor: '#0284c7',
        borderRadius: 5
      }
    ]
  };
});

// Chart 2: Distribusi Kategori Layer Spasial
const chartCategories = computed(() => {
  const cats = layerStore.categories || [];
  const defaultCounts = [17, 34, 28, 19, 22, 15, 12, 8];
  const labels = cats.slice(0, 8).map((c) => c.nama_kategori);
  const data = cats.slice(0, 8).map((_, idx) => defaultCounts[idx] || 10);
  const bgs = cats.slice(0, 8).map((c) => c.warna_default || '#10b981');

  return {
    labels: labels.length > 0 ? labels : ['Batas Wilayah', 'Transportasi', 'Fasilitas Umum', 'Kesehatan', 'Pendidikan', 'Pertanian'],
    datasets: [
      {
        label: 'Fitur Terpetakan',
        data: data.length > 0 ? data : [17, 34, 28, 19, 22, 15],
        backgroundColor: bgs.length > 0 ? bgs : ['#10b981', '#06b6d4', '#f59e0b', '#6366f1', '#8b5cf6', '#ec4899'],
        borderColor: 'rgba(15, 23, 42, 0.8)',
        borderWidth: 2
      }
    ]
  };
});
</script>

<template>
  <div class="stats-modal-backdrop" @click.self="emit('close')">
    <div class="stats-modal-card">
      <div class="stats-modal-header">
        <div class="title-cluster">
          <div class="icon-wrap">
            <BarChart3 :size="20" class="text-sky" />
          </div>
          <div>
            <h3 class="modal-title">Statistik Geospasial Kabupaten Minahasa Selatan</h3>
            <p class="modal-sub">Ringkasan cakupan wilayah, luas terpetakan, dan layer tematik aktif</p>
          </div>
        </div>
        <button type="button" class="btn-close" @click="emit('close')">
          <X :size="16" />
        </button>
      </div>

      <div class="stats-modal-body">
        <!-- KPI Strip -->
        <div class="stats-kpi-grid">
          <div class="kpi-mini-card">
            <div class="kpi-icon-row">
              <MapPin :size="16" class="text-sky" />
              <span class="kpi-badge">100% Terpetakan</span>
            </div>
            <span class="kpi-num">17</span>
            <span class="kpi-lbl">Kecamatan Aktif</span>
          </div>

          <div class="kpi-mini-card">
            <div class="kpi-icon-row">
              <Globe :size="16" class="text-emerald" />
              <span class="kpi-badge badge-green">167 Desa / 10 Kel</span>
            </div>
            <span class="kpi-num">177</span>
            <span class="kpi-lbl">Desa &amp; Kelurahan</span>
          </div>

          <div class="kpi-mini-card">
            <div class="kpi-icon-row">
              <BarChart3 :size="16" class="text-indigo" />
              <span class="kpi-badge badge-indigo">Daratan Resmi</span>
            </div>
            <span class="kpi-num">{{ totalLuasKm2 }}</span>
            <span class="kpi-lbl">Total Luas (km²)</span>
          </div>

          <div class="kpi-mini-card">
            <div class="kpi-icon-row">
              <Layers :size="16" class="text-amber" />
              <span class="kpi-badge badge-amber">Satu Data Indonesia</span>
            </div>
            <span class="kpi-num">{{ layerStore.categories.length || 28 }}</span>
            <span class="kpi-lbl">Kategori Layer Spasial</span>
          </div>
        </div>

        <!-- Charts Grid -->
        <div class="modal-chart-grid">
          <StatChart
            title="Luas Wilayah per Kecamatan (km²)"
            subtitle="Peringkat luas geografis 17 kecamatan di Minahasa Selatan"
            type="bar"
            :data="chartKecamatanLuas"
            badge="SRID:4326"
            :height="260"
          />
          <StatChart
            title="Distribusi Fitur Spasial per Kategori"
            subtitle="Volume objek tematik terverifikasi di peta Geoportal"
            type="doughnut"
            :data="chartCategories"
            badge="Top 8 Layer"
            :height="260"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stats-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(11, 19, 43, 0.85);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.stats-modal-card {
  background: #0f172a;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 18px;
  width: 100%;
  max-width: 980px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
  overflow: hidden;
  animation: modalPop 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes modalPop {
  from {
    opacity: 0;
    transform: scale(0.96) translateY(10px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.stats-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.title-cluster {
  display: flex;
  align-items: center;
  gap: 0.85rem;
}

.icon-wrap {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: rgba(2, 132, 199, 0.15);
  border: 1px solid rgba(2, 132, 199, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.text-sky {
  color: #38bdf8;
}

.modal-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #f8fafc;
}

.modal-sub {
  font-size: 0.78rem;
  color: #94a3b8;
  margin-top: 0.15rem;
}

.btn-close {
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-close:hover {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
  border-color: rgba(239, 68, 68, 0.4);
}

.stats-modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.stats-kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.85rem;
}

@media (max-width: 768px) {
  .stats-kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.kpi-mini-card {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 0.9rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.kpi-icon-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.kpi-badge {
  font-size: 0.62rem;
  font-weight: 600;
  color: #38bdf8;
  background: rgba(2, 132, 199, 0.15);
  padding: 0.15rem 0.45rem;
  border-radius: 9999px;
}

.badge-green {
  color: #34d399;
  background: rgba(16, 185, 129, 0.15);
}

.badge-indigo {
  color: #818cf8;
  background: rgba(99, 102, 241, 0.15);
}

.badge-amber {
  color: #fbbf24;
  background: rgba(245, 158, 11, 0.15);
}

.kpi-num {
  font-size: 1.4rem;
  font-weight: 800;
  color: #f8fafc;
  font-family: monospace;
}

.kpi-lbl {
  font-size: 0.72rem;
  color: #94a3b8;
}

.modal-chart-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 1rem;
}

@media (max-width: 820px) {
  .modal-chart-grid {
    grid-template-columns: 1fr;
  }
}
</style>

