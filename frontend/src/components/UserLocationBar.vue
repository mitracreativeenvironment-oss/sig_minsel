<script setup lang="ts">
import { computed } from 'vue';
import { useLocationStore } from '../stores/locationStore';
import { Crosshair, AlertCircle, Compass, Loader2 } from 'lucide-vue-next';

const locationStore = useLocationStore();

const formattedCoords = computed(() => {
  if (!locationStore.currentCoords) return null;
  const { lat, lng, accuracy } = locationStore.currentCoords;
  const latStr = `${Math.abs(lat).toFixed(5)}° ${lat >= 0 ? 'N' : 'S'}`;
  const lngStr = `${Math.abs(lng).toFixed(5)}° ${lng >= 0 ? 'E' : 'W'}`;
  return { latStr, lngStr, accuracy };
});
</script>

<template>
  <div class="user-location-bar" :class="{ 'is-active': locationStore.isLocationActive }">
    <!-- Icon Indicator -->
    <div class="loc-icon-wrap" :class="{ 'loc-pulsing': locationStore.isLocationActive && !locationStore.isLocating }">
      <Loader2 v-if="locationStore.isLocating" :size="18" class="spin-icon" />
      <Crosshair v-else :size="18" />
    </div>

    <!-- Main Location Information -->
    <div class="loc-content">
      <div class="loc-header-row">
        <span class="loc-label">Lokasi Saya</span>
        <span class="loc-status-pill" :class="locationStore.isLocationActive ? 'status-on' : 'status-off'">
          {{ locationStore.isLocationActive ? (locationStore.isLocating ? 'Mencari Sinyal...' : 'Tampil di Layar') : 'Nonaktif' }}
        </span>
      </div>

      <!-- Detail Koordinat jika Aktif -->
      <div v-if="locationStore.isLocationActive && formattedCoords" class="loc-coords-row">
        <span class="coord-val">{{ formattedCoords.latStr }}</span>
        <span class="coord-sep">&bull;</span>
        <span class="coord-val">{{ formattedCoords.lngStr }}</span>
        <span class="coord-sep">&bull;</span>
        <span class="coord-acc">Akurasi &plusmn;{{ formattedCoords.accuracy }}m</span>
      </div>
      <div v-else-if="locationStore.locationError" class="loc-error-row">
        <AlertCircle :size="13" />
        <span>{{ locationStore.locationError }}</span>
      </div>
      <div v-else class="loc-hint">
        <span>Aktifkan untuk memantau posisi GPS Anda di atas peta</span>
      </div>
    </div>

    <!-- Action Group: Pusatkan & Switch On/Off -->
    <div class="loc-actions">
      <button
        v-if="locationStore.isLocationActive && locationStore.currentCoords"
        class="btn-recenter"
        title="Pusatkan peta ke lokasi saya"
        @click="locationStore.triggerCenter()"
      >
        <Compass :size="15" />
        <span>Pusatkan</span>
      </button>

      <!-- Toggle Switch Button -->
      <label class="toggle-switch" title="Nyalakan / Matikan Tampilan Lokasi Saya">
        <input
          type="checkbox"
          :checked="locationStore.isLocationActive"
          @change="locationStore.toggleLocation()"
        />
        <span class="slider">
          <span class="slider-text">{{ locationStore.isLocationActive ? 'ON' : 'OFF' }}</span>
        </span>
      </label>
    </div>
  </div>
</template>

<style scoped>
.user-location-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  background: rgba(15, 23, 42, 0.92);
  backdrop-filter: blur(12px);
  padding: 10px 18px;
  border-radius: 40px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #f8fafc;
  font-size: 12px;
  transition: all 0.25s ease;
}

.user-location-bar.is-active {
  background: rgba(11, 20, 42, 0.95);
  border-color: rgba(56, 189, 248, 0.45);
  box-shadow: 0 8px 32px rgba(2, 132, 199, 0.3);
}

.loc-icon-wrap {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: all 0.2s;
}

.user-location-bar.is-active .loc-icon-wrap {
  background: rgba(2, 132, 199, 0.25);
  color: #38bdf8;
}

.loc-pulsing {
  position: relative;
}

.loc-pulsing::after {
  content: '';
  position: absolute;
  top: -3px;
  left: -3px;
  right: -3px;
  bottom: -3px;
  border-radius: 50%;
  border: 2px solid #38bdf8;
  animation: ripple 2s infinite ease-out;
}

@keyframes ripple {
  0% { transform: scale(0.9); opacity: 0.9; }
  100% { transform: scale(1.4); opacity: 0; }
}

.spin-icon {
  animation: spin 1.2s linear infinite;
  color: #38bdf8;
}

@keyframes spin {
  100% { transform: rotate(360deg); }
}

.loc-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.loc-header-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.loc-label {
  font-weight: 700;
  font-size: 13px;
  color: #ffffff;
}

.loc-status-pill {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 9999px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-on {
  background: rgba(16, 185, 129, 0.2);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.4);
}

.status-off {
  background: rgba(148, 163, 184, 0.15);
  color: #94a3b8;
}

.loc-coords-row {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #cbd5e1;
  font-family: 'JetBrains Mono', monospace;
}

.coord-val {
  color: #38bdf8;
  font-weight: 600;
}

.coord-sep {
  color: #475569;
}

.coord-acc {
  color: #94a3b8;
  font-size: 10px;
}

.loc-error-row {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #f87171;
}

.loc-hint {
  font-size: 11px;
  color: #64748b;
}

.loc-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-left: 8px;
}

.btn-recenter {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(56, 189, 248, 0.15);
  border: 1px solid rgba(56, 189, 248, 0.35);
  color: #38bdf8;
  font-size: 11px;
  font-weight: 600;
  padding: 6px 12px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-recenter:hover {
  background: rgba(56, 189, 248, 0.3);
  color: #ffffff;
}

/* iOS Style Animated Toggle Switch */
.toggle-switch {
  position: relative;
  display: inline-block;
  width: 58px;
  height: 28px;
  cursor: pointer;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #334155;
  border-radius: 28px;
  transition: 0.3s;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0 8px;
}

.slider-text {
  font-size: 10px;
  font-weight: 800;
  color: #94a3b8;
  user-select: none;
  font-family: 'JetBrains Mono', monospace;
}

.slider:before {
  position: absolute;
  content: "";
  height: 22px;
  width: 22px;
  left: 3px;
  bottom: 3px;
  background-color: #ffffff;
  border-radius: 50%;
  transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

input:checked + .slider {
  background: linear-gradient(135deg, #0284c7 0%, #16a34a 100%);
  justify-content: flex-start;
}

input:checked + .slider .slider-text {
  color: #ffffff;
  padding-left: 2px;
}

input:checked + .slider:before {
  transform: translateX(30px);
}

@media (max-width: 640px) {
  .user-location-bar {
    max-width: calc(100vw - 32px);
    border-radius: 20px;
    padding: 8px 12px;
    gap: 8px;
  }
  .loc-coords-row {
    flex-wrap: wrap;
    font-size: 10px;
  }
  .btn-recenter span {
    display: none;
  }
}
</style>
