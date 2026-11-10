<script setup lang="ts">
import { computed } from 'vue';
import { useWilayahStore } from '../stores/wilayahStore';
import { Building2, Home, Database } from 'lucide-vue-next';

const wilayahStore = useWilayahStore();

const totalKec = computed(() => {
  return wilayahStore.kecamatanList.length || 17;
});

const totalDesa = computed(() => {
  if (wilayahStore.selectedKecamatanId) {
    return wilayahStore.filteredDesaList.length;
  }
  return wilayahStore.desaList.length || 177;
});

const scopeLabel = computed(() => {
  if (wilayahStore.selectedKecamatan) {
    return `Kec. ${wilayahStore.selectedKecamatan.nama}`;
  }
  return 'Kab. Minahasa Selatan';
});
</script>

<template>
  <div class="stat-summary-bar">
    <div class="stat-item">
      <Building2 :size="15" class="stat-icon text-blue" />
      <span class="stat-label">Kecamatan:</span>
      <span class="stat-value">{{ totalKec }}</span>
    </div>

    <div class="stat-divider"></div>

    <div class="stat-item">
      <Home :size="15" class="stat-icon text-green" />
      <span class="stat-label">Desa / Kelurahan ({{ scopeLabel }}):</span>
      <span class="stat-value">{{ totalDesa }}</span>
    </div>

    <div class="stat-divider"></div>

    <div class="stat-item">
      <Database :size="15" class="stat-icon text-purple" />
      <span class="stat-label">Sistem Spasial:</span>
      <span class="stat-value">MySQL Spatial WGS84</span>
    </div>
  </div>
</template>

<style scoped>
.stat-summary-bar {
  display: flex;
  align-items: center;
  gap: 16px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  padding: 8px 16px;
  border-radius: 30px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(226, 232, 240, 0.8);
  font-size: 12px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 6px;
}

.stat-icon {
  flex-shrink: 0;
}

.text-blue { color: #2563eb; }
.text-green { color: #16a34a; }
.text-purple { color: #7c3aed; }

.stat-label {
  color: #64748b;
  font-weight: 500;
}

.stat-value {
  color: #0f172a;
  font-weight: 700;
}

.stat-divider {
  width: 1px;
  height: 14px;
  background: #cbd5e1;
}

@media (max-width: 900px) {
  .stat-summary-bar {
    display: none;
  }
}
</style>

