<script setup lang="ts">
import { computed } from 'vue';
import { useWilayahStore } from '../stores/wilayahStore';
import { MapPin, Building2, Home, RotateCcw } from 'lucide-vue-next';

const wilayahStore = useWilayahStore();

const onKecamatanChange = (e: Event) => {
  const target = e.target as HTMLSelectElement;
  const val = target.value ? parseInt(target.value, 10) : null;
  wilayahStore.setKecamatan(val);
};

const onDesaChange = (e: Event) => {
  const target = e.target as HTMLSelectElement;
  const val = target.value ? parseInt(target.value, 10) : null;
  wilayahStore.setDesa(val);
};

const isFiltered = computed(() => {
  return wilayahStore.selectedKecamatanId !== null || wilayahStore.selectedDesaId !== null;
});
</script>

<template>
  <div class="hierarki-filter-card">
    <div class="filter-header">
      <div class="title-wrap">
        <MapPin class="icon-title" :size="18" />
        <span class="title-text">Hierarki Wilayah Administrasi</span>
      </div>
      <button
        v-if="isFiltered"
        @click="wilayahStore.resetToKabupaten"
        class="btn-reset"
        title="Reset ke cakupan seluruh Kabupaten"
      >
        <RotateCcw :size="14" />
        <span>Reset</span>
      </button>
    </div>

    <div class="filter-body">
      <!-- 1. Level Kabupaten (Wilayah Induk) -->
      <div class="filter-group">
        <label class="filter-label">
          <Building2 :size="14" class="icon-label" />
          <span>Level 1: Kabupaten (Wilayah Induk)</span>
        </label>
        <div class="kabupaten-badge">
          <span class="badge-code">{{ wilayahStore.kabupatenKode }}</span>
          <span class="badge-name">{{ wilayahStore.kabupatenNama }}</span>
          <span class="badge-ibukota">(Ibukota: {{ wilayahStore.kabupatenIbukota }})</span>
        </div>
      </div>

      <!-- 2. Level Kecamatan -->
      <div class="filter-group">
        <label class="filter-label" for="select-kecamatan">
          <MapPin :size="14" class="icon-label" />
          <span>Level 2: Kecamatan (17 Wilayah)</span>
        </label>
        <select
          id="select-kecamatan"
          class="filter-select"
          :value="wilayahStore.selectedKecamatanId ?? ''"
          @change="onKecamatanChange"
        >
          <option value="">-- Semua Kecamatan (17 Kecamatan) --</option>
          <option
            v-for="kec in wilayahStore.kecamatanList"
            :key="kec.id"
            :value="kec.id"
          >
            {{ kec.kode }} - Kecamatan {{ kec.nama }}
          </option>
        </select>
      </div>

      <!-- 3. Level Desa / Kelurahan -->
      <div class="filter-group">
        <label class="filter-label" for="select-desa">
          <Home :size="14" class="icon-label" />
          <span>Level 3: Desa / Kelurahan</span>
        </label>
        <select
          id="select-desa"
          class="filter-select"
          :disabled="!wilayahStore.selectedKecamatanId"
          :value="wilayahStore.selectedDesaId ?? ''"
          @change="onDesaChange"
        >
          <option value="">
            {{ wilayahStore.selectedKecamatanId ? '-- Semua Desa / Kelurahan --' : '-- Pilih Kecamatan Terlebih Dahulu --' }}
          </option>
          <option
            v-for="desa in wilayahStore.filteredDesaList"
            :key="desa.id"
            :value="desa.id"
          >
            {{ desa.kode }} - {{ desa.tipe }} {{ desa.nama }}
          </option>
        </select>
      </div>
    </div>

    <!-- Active Scope Breadcrumb -->
    <div class="filter-footer">
      <span class="scope-indicator">Cakupan Aktif:</span>
      <span class="scope-value">{{ wilayahStore.currentScopeLabel }}</span>
    </div>
  </div>
</template>

<style scoped>
.hierarki-filter-card {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
  border: 1px solid #e2e8f0;
  overflow: hidden;
  font-family: inherit;
}

.filter-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.title-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #0f172a;
  font-weight: 600;
  font-size: 14px;
}

.icon-title {
  color: #2563eb;
}

.btn-reset {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  padding: 4px 8px;
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-reset:hover {
  background: #e2e8f0;
  color: #0f172a;
}

.filter-body {
  padding: 14px 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}

.icon-label {
  color: #64748b;
}

.kabupaten-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
}

.badge-code {
  background: #2563eb;
  color: #ffffff;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 700;
  font-size: 11px;
}

.badge-name {
  font-weight: 600;
  color: #1e3a8a;
}

.badge-ibukota {
  color: #64748b;
  font-size: 12px;
}

.filter-select {
  width: 100%;
  padding: 8px 12px;
  font-size: 13px;
  color: #0f172a;
  background-color: #ffffff;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.filter-select:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

.filter-select:disabled {
  background-color: #f8fafc;
  color: #94a3b8;
  cursor: not-allowed;
}

.filter-footer {
  padding: 10px 16px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 6px;
}

.scope-indicator {
  color: #64748b;
}

.scope-value {
  color: #2563eb;
  font-weight: 600;
}
</style>

