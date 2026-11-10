<script setup lang="ts">
import { ref, onMounted, watch, onBeforeUnmount } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { useWilayahStore } from '../stores/wilayahStore';
import { useLayerStore } from '../stores/layerStore';
import { useLocationStore } from '../stores/locationStore';
import { sigApi } from '../services/api';
import type { ObjekSpasialProperties } from '../types';

const mapContainer = ref<HTMLDivElement | null>(null);
let map: maplibregl.Map | null = null;
let popup: maplibregl.Popup | null = null;
let userLocationMarker: maplibregl.Marker | null = null;

const wilayahStore = useWilayahStore();
const layerStore = useLayerStore();
const locationStore = useLocationStore();

// 8 Peta Dasar Bebas Akses (Free, Lengkap, dan Stabil)
const basemapStyles: Record<string, string | maplibregl.StyleSpecification> = {
  // 1. Carto Positron (Peta Terang Bersih - Ideal untuk Poligon Wilayah)
  'carto-positron': 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',

  // 2. Carto Dark Matter (Peta Gelap Kontras Tinggi)
  'carto-dark': 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json',

  // 3. Carto Voyager (Peta Lengkap Jalan, Bangunan & Landmark)
  'carto-voyager': 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json',

  // 4. OpenStreetMap Standard (Peta Komunitas Global Lengkap)
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

  // 5. Esri World Topographic (Kontur & Relief Elevasi Topografi Minsel)
  'esri-topo': {
    version: 8,
    sources: {
      'esri-topo-source': {
        type: 'raster',
        tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}'],
        tileSize: 256,
        attribution: '&copy; Esri World Topographic'
      }
    },
    layers: [{ id: 'esri-topo-layer', type: 'raster', source: 'esri-topo-source', minzoom: 0, maxzoom: 19 }]
  },

  // 6. Esri World Street Map (Jaringan Jalan Nasional Trans Sulawesi)
  'esri-street': {
    version: 8,
    sources: {
      'esri-street-source': {
        type: 'raster',
        tiles: ['https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}'],
        tileSize: 256,
        attribution: '&copy; Esri World Street Map'
      }
    },
    layers: [{ id: 'esri-street-layer', type: 'raster', source: 'esri-street-source', minzoom: 0, maxzoom: 19 }]
  },

  // 7. Esri World Imagery (Citra Satelit Foto Udara Resolusi Tinggi)
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
  },

  // 8. OpenTopoMap (Kontur Elevasi Terbuka)
  'opentopo': {
    version: 8,
    sources: {
      'opentopo-source': {
        type: 'raster',
        tiles: ['https://tile.opentopomap.org/{z}/{x}/{y}.png'],
        tileSize: 256,
        attribution: '&copy; OpenTopoMap contributors'
      }
    },
    layers: [{ id: 'opentopo-layer', type: 'raster', source: 'opentopo-source', minzoom: 0, maxzoom: 17 }]
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
    syncUserLocationMarker();
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

// ========================================================
// MANAJEMEN MARKER LOKASI SAYA (ON / OFF)
// ========================================================
function syncUserLocationMarker() {
  if (!map) return;

  if (locationStore.isLocationActive && locationStore.currentCoords) {
    const { lng, lat, accuracy } = locationStore.currentCoords;

    if (!userLocationMarker) {
      // Buat elemen DOM kustom untuk marker lokasi berdenyut (pulsing GPS radar)
      const el = document.createElement('div');
      el.className = 'custom-user-gps-marker';
      el.innerHTML = `
        <div class="gps-pulse-ring"></div>
        <div class="gps-dot"></div>
      `;

      const locPopup = new maplibregl.Popup({ offset: 16, closeButton: false }).setHTML(`
        <div style="font-family: inherit; font-size: 12px; font-weight: 600; color: #0f172a; padding: 2px;">
          📍 Lokasi Anda Saat Ini<br>
          <span style="font-size: 10px; color: #64748b; font-weight: normal;">
            Akurasi: ±${accuracy}m
          </span>
        </div>
      `);

      userLocationMarker = new maplibregl.Marker({ element: el })
        .setLngLat([lng, lat])
        .setPopup(locPopup)
        .addTo(map);
    } else {
      userLocationMarker.setLngLat([lng, lat]);
    }
  } else {
    // Jika OFF: Hapus marker dari layar
    if (userLocationMarker) {
      userLocationMarker.remove();
      userLocationMarker = null;
    }
  }
}

// Watcher status dan koordinat lokasi pengguna
watch(
  () => [locationStore.isLocationActive, locationStore.currentCoords],
  () => {
    syncUserLocationMarker();
  },
  { deep: true }
);

// Watcher permintaan pusatkan ke lokasi saya
watch(
  () => locationStore.centerRequestTrigger,
  () => {
    if (!map || !locationStore.currentCoords) return;
    map.flyTo({
      center: [locationStore.currentCoords.lng, locationStore.currentCoords.lat],
      zoom: 14,
      essential: true,
      speed: 1.2
    });
  }
);

// Watcher pergantian basemap
watch(() => layerStore.activeBasemap, (newBasemap) => {
  if (!map) return;
  const style = basemapStyles[newBasemap];
  map.setStyle(style);
  map.once('style.load', () => {
    loadSpatialData();
    syncUserLocationMarker();
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
  if (userLocationMarker) {
    userLocationMarker.remove();
    userLocationMarker = null;
  }
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

<!-- Global Style untuk Custom GPS Marker Element di MapLibre -->
<style>
.custom-user-gps-marker {
  position: relative;
  width: 24px;
  height: 24px;
  cursor: pointer;
}

.gps-dot {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #0284c7;
  border: 2.5px solid #ffffff;
  box-shadow: 0 0 10px rgba(2, 132, 199, 0.7);
  z-index: 2;
}

.gps-pulse-ring {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(56, 189, 248, 0.35);
  border: 1px solid rgba(56, 189, 248, 0.8);
  animation: gpsPulse 2s infinite ease-out;
  z-index: 1;
}

@keyframes gpsPulse {
  0% {
    transform: translate(-50%, -50%) scale(0.6);
    opacity: 1;
  }
  100% {
    transform: translate(-50%, -50%) scale(2.2);
    opacity: 0;
  }
}
</style>
