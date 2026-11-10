<script setup lang="ts">
import { onMounted } from 'vue';
import TopNavbar from './components/TopNavbar.vue';
import MapContainer from './components/MapContainer.vue';
import CascadingWilayahFilter from './components/CascadingWilayahFilter.vue';
import LayerControlPanel from './components/LayerControlPanel.vue';
import StatSummaryBar from './components/StatSummaryBar.vue';
import { useWilayahStore } from './stores/wilayahStore';
import { useLayerStore } from './stores/layerStore';

const wilayahStore = useWilayahStore();
const layerStore = useLayerStore();

onMounted(async () => {
  await wilayahStore.init();
  await layerStore.loadCategories();
});
</script>

<template>
  <div class="gis-app">
    <TopNavbar />

    <main class="gis-body">
      <!-- Peta Layar Penuh -->
      <MapContainer />

      <!-- Floating Sidebar Kiri: Filter Hierarki & Layer Control -->
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
  background-color: #f8fafc;
  overflow: hidden;
}

.gis-app {
  display: flex;
  flex-direction: column;
  width: 100vw;
  height: 100vh;
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

@media (max-width: 768px) {
  .floating-sidebar {
    width: calc(100vw - 32px);
    max-height: 50vh;
  }
}
</style>

