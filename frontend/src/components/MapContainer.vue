<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { useWilayahStore } from '../stores/wilayahStore';
import { useLayerStore } from '../stores/layerStore';
import { sigApi } from '../services/api';
import type { ObjekSpasialProperties } from '../types';

const mapContainer = ref<HTMLDivElement | null>(null);
let map: maplibregl.Map | null = null;
let popup: maplibregl.Popup | null = null;

const wilayahStore = useWilayahStore();
const layerStore = useLayerStore();

// Basemap URL Definitions
const basemapStyles: Record<string, string | maplibregl.StyleSpecification> = {
  'osm': {
    version: 8,
    sources: {
      'osm-raster': {
        type: 'raster',
        tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
        tileSize: 256,
        attribution: '&copy; OpenStreetMap contributors'
      }
    },
    layers: [{ id: 'osm-layer', type: 'raster', source: 'osm-raster', minzoom: 0, maxzoom: 19 }]
  },
  'carto-positron': 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
  'carto-dark': 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json',
  'esri-satellite': {
    version: 8,
    sources: {
      'esri-sat': {
        type: 'raster',
        tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}'],
        tileSize: 256,
        attribution: '&copy; Esri World Imagery'
      }
    },
    layers: [{ id: 'esri-layer', type: 'raster', source: 'esri-sat', minzoom: 0, maxzoom: 19 }]
  }
};

const initMap = () => {
  if (!mapContainer.value) return;

  const style = basemapStyles[layerStore.activeBasemap];

  map = new maplibregl.Map({
    container: mapContainer.value,
    style: style,
    center: [124.5800, 1.1800], // Amurang, Minahasa Selatan
    zoom: 10,
    pitch: 0,
    bearing: 0
  });

  map.addControl(new maplibregl.NavigationControl({ visualizePitch: true }), 'top-right');
  map.addControl(new maplibregl.ScaleControl({ unit: 'metric' }), 'bottom-right');

  popup = new maplibregl.Popup({
    closeButton: true,
    closeOnClick: true,
    maxWidth: '320px'
  });

  map.on('load', async () => {
    await loadSpatialData();
  });
};

// Muat data spasial dari CodeIgniter 4 REST API
const loadSpatialData = async () => {
  if (!map || !map.isStyleLoaded()) return;

  try {
    // 1. Data Batas Kecamatan
    const kecData = await sigApi.getKecamatan();
    if (map.getSource('kecamatan-source')) {
      (map.getSource('kecamatan-source') as maplibregl.GeoJSONSource).setData(kecData as any);
    } else {
      map.addSource('kecamatan-source', {
        type: 'geojson',
        data: kecData as any
      });

      // Layer Fill Kecamatan
      map.addLayer({
        id: 'kecamatan-fill',
        type: 'fill',
        source: 'kecamatan-source',
        paint: {
          'fill-color': '#3b82f6',
          'fill-opacity': [
            'case',
            ['boolean', ['feature-state', 'hover'], false],
            0.4,
            0.15
          ]
        }
      });

      // Layer Garis Batas Kecamatan
      map.addLayer({
        id: 'kecamatan-line',
        type: 'line',
        source: 'kecamatan-source',
        paint: {
          'line-color': '#1d4ed8',
          'line-width': 2,
          'line-dasharray': [2, 1]
        }
      });

      // Event Click Poligon Kecamatan
      map.on('click', 'kecamatan-fill', (e) => {
        if (!e.features || !e.features[0]) return;
        const props = e.features[0].properties;
        wilayahStore.setKecamatan(props.id);
      });

      // Hover cursor
      map.on('mouseenter', 'kecamatan-fill', () => { if (map) map.getCanvas().style.cursor = 'pointer'; });
      map.on('mouseleave', 'kecamatan-fill', () => { if (map) map.getCanvas().style.cursor = ''; });
    }

    // 2. Data Objek Spasial Tematik (Points)
    await reloadThematicMarkers();

  } catch (err) {
    console.warn('Pemberitahuan: Data API backend sedang dihubungkan:', err);
  }
};

// Reload marker tematik sesuai filter hierarki aktif
const reloadThematicMarkers = async () => {
  if (!map || !map.isStyleLoaded()) return;

  try {
    const filters = {
      kecamatan_id: wilayahStore.selectedKecamatanId || undefined,
      desa_id: wilayahStore.selectedDesaId || undefined,
    };

    // Ambil layer kesehatan & pemerintahan sebagai sampel awal
    const layerData = await sigApi.getLayer('kesehatan', filters);

    if (map.getSource('thematic-source')) {
      (map.getSource('thematic-source') as maplibregl.GeoJSONSource).setData(layerData as any);
    } else {
      map.addSource('thematic-source', {
        type: 'geojson',
        data: layerData as any
      });

      map.addLayer({
        id: 'thematic-points',
        type: 'circle',
        source: 'thematic-source',
        paint: {
          'circle-radius': 8,
          'circle-color': '#ef4444',
          'circle-stroke-width': 2,
          'circle-stroke-color': '#ffffff'
        }
      });

      map.on('click', 'thematic-points', (e) => {
        if (!e.features || !e.features[0] || !map || !popup) return;
        const coords = (e.features[0].geometry as any).coordinates.slice();
        const props = e.features[0].properties as ObjekSpasialProperties;

        const content = `
          <div style="font-family: inherit; font-size: 13px; padding: 4px;">
            <div style="font-weight: 700; color: #0f172a; margin-bottom: 4px; font-size: 14px;">
              ${props.nama}
            </div>
            <div style="display: inline-block; background: #eff6ff; color: #2563eb; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-bottom: 6px;">
              ${props.nama_kategori || props.kategori}
            </div>
            <div style="font-size: 12px; color: #475569; margin-bottom: 4px;">
              <strong>Wilayah:</strong> Kec. ${props.kecamatan || '-'} ${props.desa ? `(Desa ${props.desa})` : ''}
            </div>
            ${props.deskripsi ? `<div style="font-size: 12px; color: #64748b; margin-top: 6px;">${props.deskripsi}</div>` : ''}
            ${props.foto ? `<img src="${props.foto}" style="width: 100%; border-radius: 6px; margin-top: 8px; max-height: 120px; object-fit: cover;" />` : ''}
          </div>
        `;

        popup.setLngLat(coords).setHTML(content).addTo(map);
      });

      map.on('mouseenter', 'thematic-points', () => { if (map) map.getCanvas().style.cursor = 'pointer'; });
      map.on('mouseleave', 'thematic-points', () => { if (map) map.getCanvas().style.cursor = ''; });
    }
  } catch (err) {
    console.warn('Gagal memuat thematic markers:', err);
  }
};

// Watcher pergantian basemap
watch(() => layerStore.activeBasemap, (newBasemap) => {
  if (!map) return;
  const style = basemapStyles[newBasemap];
  map.setStyle(style);
  map.once('style.load', () => {
    loadSpatialData();
  });
});

// Watcher pergantian filter kecamatan / desa -> Otomatis FlyTo ke wilayah terpilih!
watch(() => [wilayahStore.selectedKecamatanId, wilayahStore.selectedDesaId], async () => {
  if (!map) return;

  const kec = wilayahStore.selectedKecamatan;
  if (kec && kec.center && kec.center.lng && kec.center.lat) {
    map.flyTo({
      center: [kec.center.lng, kec.center.lat],
      zoom: 12,
      essential: true
    });
  } else if (!wilayahStore.selectedKecamatanId) {
    // Reset ke Kabupaten
    map.flyTo({
      center: [124.5800, 1.1800],
      zoom: 10,
      essential: true
    });
  }

  await reloadThematicMarkers();
});

onMounted(() => {
  initMap();
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});
</script>

<template>
  <div class="map-wrapper">
    <div ref="mapContainer" class="map-container"></div>
  </div>
</template>

<style scoped>
.map-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
}

.map-container {
  width: 100%;
  height: 100%;
}
</style>

