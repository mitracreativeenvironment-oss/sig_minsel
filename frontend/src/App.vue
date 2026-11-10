<script setup lang="ts">
import { ref, onMounted } from 'vue';
import TopNavbar from './components/TopNavbar.vue';
import MapContainer from './components/MapContainer.vue';
import CascadingWilayahFilter from './components/CascadingWilayahFilter.vue';
import LayerControlPanel from './components/LayerControlPanel.vue';
import StatSummaryBar from './components/StatSummaryBar.vue';
import LandingPage from './views/LandingPage.vue';
import DashboardView from './views/DashboardView.vue';
import AuthModal from './components/AuthModal.vue';
import { useWilayahStore } from './stores/wilayahStore';
import { useLayerStore } from './stores/layerStore';
import { useAuthStore } from './stores/authStore';

type ActiveView = 'landing' | 'geoportal' | 'dashboard';
const currentView = ref<ActiveView>('landing');
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
  authStore.initFromStorage();
  await wilayahStore.init();
  await layerStore.loadCategories();
  await authStore.loadRoles();
});
</script>

<template>
  <div class="gis-app">
    <!-- VIEW 1: LANDING PAGE 1 HALAMAN FULL DENGAN THREE.JS SCROLLYTELLING -->
    <LandingPage
      v-if="currentView === 'landing'"
      @navigate="handleNavigate"
      @open-login="showAuthModal = true"
    />

    <!-- VIEW 2: GEOPORTAL PETA INTERAKTIF MAPLIBRE -->
    <div v-else-if="currentView === 'geoportal'" class="geoportal-layout">
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

        <!-- Floating Stat Bar Bawah -->
        <div class="floating-stat-bar">
          <StatSummaryBar />
        </div>
      </main>
    </div>

    <!-- VIEW 3: DASHBOARD TATA KELOLA SPASIAL 8 ROLE -->
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

.floating-stat-bar {
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
</style>
