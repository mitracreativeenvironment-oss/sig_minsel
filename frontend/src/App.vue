<script setup lang="ts">
import { ref, onMounted } from 'vue';
import TopNavbar from './components/TopNavbar.vue';
import MapContainer from './components/MapContainer.vue';
import CascadingWilayahFilter from './components/CascadingWilayahFilter.vue';
import LayerControlPanel from './components/LayerControlPanel.vue';
import UserLocationBar from './components/UserLocationBar.vue';
import DashboardView from './views/DashboardView.vue';
import AuthModal from './components/AuthModal.vue';
import { useWilayahStore } from './stores/wilayahStore';
import { useLayerStore } from './stores/layerStore';
import { useAuthStore } from './stores/authStore';
import { sigApi } from './services/api';

type ActiveView = 'geoportal' | 'dashboard';
const currentView = ref<ActiveView>('geoportal');
const showAuthModal = ref(false);

const wilayahStore = useWilayahStore();
const layerStore = useLayerStore();
const authStore = useAuthStore();

function handleNavigate(view: ActiveView) {
  if (view === 'dashboard' && !authStore.isAuthenticated) {
    showAuthModal.value = true;
    return;
  }
  currentView.value = view;
}

function handleLoginSuccess() {
  showAuthModal.value = false;
  currentView.value = 'dashboard';
}

onMounted(async () => {
  sigApi.trackVisitor({ page: 'geoportal_map' }).catch(() => {});
  authStore.initFromStorage();
  await wilayahStore.init();
  await layerStore.loadCategories();
  await authStore.loadRoles();
});
</script>

<template>
  <div class="gis-app">
    <!-- VIEW 1: GEOPORTAL PETA INTERAKTIF MAPLIBRE (UTAMA) -->
    <div v-if="currentView === 'geoportal'" class="geoportal-layout">
      <TopNavbar
        @navigate="handleNavigate"
        @open-login="showAuthModal = true"
      />

      <main class="gis-body">
        <!-- Peta Layar Penuh -->
        <MapContainer />

        <!-- Floating Sidebar Kiri: Filter Hierarki 17 Kecamatan & Layer Control -->
        <aside class="floating-sidebar">
          <CascadingWilayahFilter />
          <LayerControlPanel />
        </aside>

        <!-- Floating Bar Bawah: Toggle Lokasi Saya (On/Off) -->
        <div class="floating-location-bar">
          <UserLocationBar />
        </div>
      </main>
    </div>

    <!-- VIEW 2: DASHBOARD TATA KELOLA SPASIAL 8 ROLE -->
    <DashboardView
      v-else-if="currentView === 'dashboard'"
      @navigate="handleNavigate"
    />

    <!-- MODAL AUTENTIKASI 8 ROLE -->
    <AuthModal
      v-if="showAuthModal"
      @close="showAuthModal = false"
      @success="handleLoginSuccess"
    />
  </div>
</template>

<style>
/* Global Reset */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  color: #0f172a;
  background-color: #0b132b;
  overflow: hidden;
}

.gis-app {
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  position: relative;
}

.geoportal-layout {
  display: flex;
  flex-direction: column;
  width: 100%;
  height: 100%;
}

.gis-body {
  position: relative;
  flex: 1;
  width: 100%;
  height: calc(100vh - 64px);
  overflow: hidden;
}

.floating-sidebar {
  position: absolute;
  top: 16px;
  left: 16px;
  width: 360px;
  max-height: calc(100vh - 96px);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 12px;
  z-index: 10;
  padding-right: 4px;
}

/* Custom Scrollbar for Sidebar */
.floating-sidebar::-webkit-scrollbar {
  width: 6px;
}
.floating-sidebar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.floating-location-bar {
  position: absolute;
  bottom: 24px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
}

@media (max-width: 640px) {
  .floating-sidebar {
    width: calc(100vw - 32px);
    max-height: 40vh;
  }
}

/* SweetAlert2 GeoMinsel Theme Customization */
.geominsel-toast-popup {
  background: #ffffff !important;
  color: #0f172a !important;
  border-radius: 12px !important;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.18), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
  border-left: 4px solid #0284c7 !important;
  font-family: inherit !important;
  padding: 12px 16px !important;
}

.geominsel-confirm-popup {
  border-radius: 16px !important;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.15) !important;
  font-family: inherit !important;
  padding: 20px !important;
  border: 1px solid #e2e8f0 !important;
}

.geominsel-confirm-title {
  font-size: 17px !important;
  font-weight: 700 !important;
  color: #0f172a !important;
}
</style>
