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

// ==============================================================================
// 1. DATA GEOMETRI BATAS KABUPATEN MINAHASA SELATAN & INVERTED WORLD MASK
// ==============================================================================
// Poligon batas teritorial resmi mengelilingi 17 kecamatan di Kab. Minahasa Selatan (WGS84 EPSG:4326)
const minselBoundaryCoords: [number, number][] = [
  // Pesisir Timur Laut / Tatapaan (Batas Kab. Minahasa)
  [124.6850, 1.3400],
  [124.7200, 1.3250],
  [124.7450, 1.2950], // Tareran (Batas Kota Tomohon)
  [124.7480, 1.2500],
  [124.7350, 1.2100], // Suluun Tareran (Batas Kab. Minahasa Tenggara)
  [124.7100, 1.1700],
  [124.6850, 1.1350], // Kumelembuai
  [124.6550, 1.1000], // Motoling Timur
  [124.6200, 1.0650], // Ranoyapo
  [124.5950, 1.0200],
  [124.5750, 0.9650], // Tompaso Baru
  [124.5300, 0.9150],
  [124.5050, 0.8650], // Maesaan
  [124.4900, 0.8100], // Modoinding Tenggara (Batas Kab. Bolaang Mongondow Timur)
  [124.4450, 0.8050], // Modoinding Selatan (Danau Moat / Perbatasan Bolmong)
  [124.4100, 0.8120], // Modoinding Barat
  [124.4150, 0.8600], // Maesaan Barat
  [124.4050, 0.9100], // Tompaso Baru Barat
  [124.3650, 0.9550], // Sinonsayang Selatan
  [124.3100, 0.9950], // Muara Pesisir Barat Daya Sinonsayang (Batas Bolmong / Laut Sulawesi)
  // Garis Pantai Pesisir Laut Sulawesi (Barat Daya ke Timur Laut)
  [124.2980, 1.0450], // Pesisir Sinonsayang
  [124.3150, 1.1100],
  [124.3550, 1.1450], // Pesisir Tenga
  [124.4100, 1.1600],
  [124.4650, 1.1750], // Amurang Barat
  [124.5200, 1.1850], // Teluk Amurang Barat
  [124.5750, 1.1950], // Teluk Amurang Pusat (Pantai Alar / Buyungon)
  [124.6050, 1.2350], // Amurang Timur
  [124.6250, 1.2750], // Pesisir Tumpaan
  [124.6350, 1.3200], // Pesisir Tatapaan
  [124.6850, 1.3400]  // Menutup kembali ke titik awal
];

// Inverted GeoJSON Polygon: Dunia sebagai ring luar, Minahasa Selatan sebagai inner hole (lubang transparan)
const worldMaskGeoJson = {
  type: 'Feature',
  properties: { id: 'minsel-outer-mask' },
  geometry: {
    type: 'Polygon',
    coordinates: [
      [
        [-180, -85],
        [180, -85],
        [180, 85],
        [-180, 85],
        [-180, -85]
      ],
      minselBoundaryCoords
    ]
  }
};

// LineString untuk batas administratif Kabupaten Minahasa Selatan
const minselBoundaryGeoJson = {
  type: 'Feature',
  properties: { id: 'minsel-county-border' },
  geometry: {
    type: 'LineString',
    coordinates: minselBoundaryCoords
  }
};

// Data Fallback 17 Kecamatan jika database backend belum terhubung
const fallbackKecamatanGeoJson = {
  type: 'FeatureCollection',
  features: [
    { type: 'Feature', id: 1, properties: { id: 1, kode: '71.05.01', nama: 'Modoinding', luas_km2: 53.61 }, geometry: { type: 'Polygon', coordinates: [[[124.41, 0.81], [124.49, 0.81], [124.49, 0.90], [124.41, 0.90], [124.41, 0.81]]] } },
    { type: 'Feature', id: 2, properties: { id: 2, kode: '71.05.02', nama: 'Tompaso Baru', luas_km2: 126.11 }, geometry: { type: 'Polygon', coordinates: [[[124.42, 0.90], [124.52, 0.90], [124.52, 1.01], [124.42, 1.01], [124.42, 0.90]]] } },
    { type: 'Feature', id: 3, properties: { id: 3, kode: '71.05.03', nama: 'Ranoyapo', luas_km2: 93.71 }, geometry: { type: 'Polygon', coordinates: [[[124.48, 0.95], [124.58, 0.95], [124.58, 1.05], [124.48, 1.05], [124.48, 0.95]]] } },
    { type: 'Feature', id: 4, properties: { id: 4, kode: '71.05.04', nama: 'Motoling', luas_km2: 26.68 }, geometry: { type: 'Polygon', coordinates: [[[124.50, 1.02], [124.57, 1.02], [124.57, 1.08], [124.50, 1.08], [124.50, 1.02]]] } },
    { type: 'Feature', id: 5, properties: { id: 5, kode: '71.05.05', nama: 'Sinonsayang', luas_km2: 133.40 }, geometry: { type: 'Polygon', coordinates: [[[124.30, 1.00], [124.45, 1.00], [124.45, 1.15], [124.30, 1.15], [124.30, 1.00]]] } },
    { type: 'Feature', id: 6, properties: { id: 6, kode: '71.05.06', nama: 'Tenga', luas_km2: 114.77 }, geometry: { type: 'Polygon', coordinates: [[[124.40, 1.08], [124.53, 1.08], [124.53, 1.20], [124.40, 1.20], [124.40, 1.08]]] } },
    { type: 'Feature', id: 7, properties: { id: 7, kode: '71.05.07', nama: 'Amurang', luas_km2: 10.66 }, geometry: { type: 'Polygon', coordinates: [[[124.55, 1.16], [124.60, 1.16], [124.60, 1.22], [124.55, 1.22], [124.55, 1.16]]] } },
    { type: 'Feature', id: 8, properties: { id: 8, kode: '71.05.08', nama: 'Tumpaan', luas_km2: 73.16 }, geometry: { type: 'Polygon', coordinates: [[[124.60, 1.20], [124.68, 1.20], [124.68, 1.28], [124.60, 1.28], [124.60, 1.20]]] } },
    { type: 'Feature', id: 9, properties: { id: 9, kode: '71.05.09', nama: 'Tareran', luas_km2: 58.74 }, geometry: { type: 'Polygon', coordinates: [[[124.65, 1.23], [124.74, 1.23], [124.74, 1.30], [124.65, 1.30], [124.65, 1.23]]] } },
    { type: 'Feature', id: 10, properties: { id: 10, kode: '71.05.10', nama: 'Kumelembuai', luas_km2: 45.47 }, geometry: { type: 'Polygon', coordinates: [[[124.54, 1.07], [124.62, 1.07], [124.62, 1.14], [124.54, 1.14], [124.54, 1.07]]] } },
    { type: 'Feature', id: 11, properties: { id: 11, kode: '71.05.11', nama: 'Maesaan', luas_km2: 47.78 }, geometry: { type: 'Polygon', coordinates: [[[124.43, 0.86], [124.51, 0.86], [124.51, 0.94], [124.43, 0.94], [124.43, 0.86]]] } },
    { type: 'Feature', id: 12, properties: { id: 12, kode: '71.05.12', nama: 'Amurang Barat', luas_km2: 88.58 }, geometry: { type: 'Polygon', coordinates: [[[124.48, 1.13], [124.56, 1.13], [124.56, 1.21], [124.48, 1.21], [124.48, 1.13]]] } },
    { type: 'Feature', id: 13, properties: { id: 13, kode: '71.05.13', nama: 'Amurang Timur', luas_km2: 178.26 }, geometry: { type: 'Polygon', coordinates: [[[124.57, 1.12], [124.68, 1.12], [124.68, 1.23], [124.57, 1.23], [124.57, 1.12]]] } },
    { type: 'Feature', id: 14, properties: { id: 14, kode: '71.05.14', nama: 'Tatapaan', luas_km2: 65.48 }, geometry: { type: 'Polygon', coordinates: [[[124.58, 1.25], [124.70, 1.25], [124.70, 1.33], [124.58, 1.33], [124.58, 1.25]]] } },
    { type: 'Feature', id: 15, properties: { id: 15, kode: '71.05.15', nama: 'Suluun Tareran', luas_km2: 32.22 }, geometry: { type: 'Polygon', coordinates: [[[124.66, 1.19], [124.73, 1.19], [124.73, 1.25], [124.66, 1.25], [124.66, 1.19]]] } },
    { type: 'Feature', id: 16, properties: { id: 16, kode: '71.05.16', nama: 'Motoling Barat', luas_km2: 113.67 }, geometry: { type: 'Polygon', coordinates: [[[124.44, 1.00], [124.52, 1.00], [124.52, 1.10], [124.44, 1.10], [124.44, 1.00]]] } },
    { type: 'Feature', id: 17, properties: { id: 17, kode: '71.05.17', nama: 'Motoling Timur', luas_km2: 55.45 }, geometry: { type: 'Polygon', coordinates: [[[124.53, 1.01], [124.61, 1.01], [124.61, 1.09], [124.53, 1.09], [124.53, 1.01]]] } }
  ]
};

// Data Fallback Objek Spasial Tematik
const fallbackThematicGeoJson = {
  type: 'FeatureCollection',
  features: [
    {
      type: 'Feature',
      id: 1,
      properties: {
        id: 1,
        nama: 'Kantor Bupati Minahasa Selatan',
        kategori: 'pemerintahan',
        nama_kategori: 'Kantor Pemerintahan',
        kecamatan: 'Amurang',
        desa: 'Pondang',
        deskripsi: 'Pusat Pemerintahan Kabupaten Minahasa Selatan, Jl. Trans Sulawesi'
      },
      geometry: { type: 'Point', coordinates: [124.5772, 1.1824] }
    },
    {
      type: 'Feature',
      id: 2,
      properties: {
        id: 2,
        nama: 'RSUD Amurang',
        kategori: 'kesehatan',
        nama_kategori: 'Fasilitas Kesehatan',
        kecamatan: 'Amurang',
        desa: 'Pondang',
        deskripsi: 'Rumah Sakit Umum Daerah Kelas C Rujukan Kabupaten Minahasa Selatan'
      },
      geometry: { type: 'Point', coordinates: [124.5821, 1.1795] }
    },
    {
      type: 'Feature',
      id: 3,
      properties: {
        id: 3,
        nama: 'Pantai Alar Amurang',
        kategori: 'pariwisata',
        nama_kategori: 'Pariwisata & Budaya',
        kecamatan: 'Amurang',
        desa: 'Buyungon',
        deskripsi: 'Kawasan Wisata Bahari dan Kuliner Pesisir Teluk Amurang'
      },
      geometry: { type: 'Point', coordinates: [124.5684, 1.1887] }
    },
    {
      type: 'Feature',
      id: 4,
      properties: {
        id: 4,
        nama: 'Kantor Camat Tumpaan',
        kategori: 'pemerintahan',
        nama_kategori: 'Kantor Pemerintahan',
        kecamatan: 'Tumpaan',
        desa: 'Tumpaan Satu',
        deskripsi: 'Pusat Pelayanan Administrasi Pemerintahan Kecamatan Tumpaan'
      },
      geometry: { type: 'Point', coordinates: [124.6284, 1.2312] }
    },
    {
      type: 'Feature',
      id: 5,
      properties: {
        id: 5,
        nama: 'Puskesmas Modoinding',
        kategori: 'kesehatan',
        nama_kategori: 'Fasilitas Kesehatan',
        kecamatan: 'Modoinding',
        desa: 'Linelean',
        deskripsi: 'Puskesmas Rawat Inap Wilayah Dataran Tinggi Modoinding'
      },
      geometry: { type: 'Point', coordinates: [124.4412, 0.8385] }
    }
  ]
};

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

// ==============================================================================
// 2. LAYER KHAS GEOMINSEL: FOKUS MINAHASA SELATAN (BLUR + DESATURASI LUAR)
// ==============================================================================
const setupMinselFocusLayers = () => {
  if (!map || !map.isStyleLoaded()) return;

  // 1. Tambahkan GeoJSON Source Inverted Mask (Dunia Luas dengan Lubang Minahasa Selatan)
  if (!map.getSource('minsel-mask-source')) {
    map.addSource('minsel-mask-source', {
      type: 'geojson',
      data: worldMaskGeoJson as any
    });
  }

  // 2. Tambahkan GeoJSON Source Garis Batas Kabupaten
  if (!map.getSource('minsel-boundary-source')) {
    map.addSource('minsel-boundary-source', {
      type: 'geojson',
      data: minselBoundaryGeoJson as any
    });
  }

  const focusVisibility = layerStore.isFocusMinselActive ? 'visible' : 'none';

  // Layer 1: Desaturasi Slate Ringan Wilayah Luar Minsel (Grayscale Ringan)
  // Meredam saturasi wilayah luar sekitar 50% tanpa menutup basemap sehingga toponimi & jalan tetap terlihat
  if (!map.getLayer('minsel-outside-desaturate')) {
    map.addLayer({
      id: 'minsel-outside-desaturate',
      type: 'fill',
      source: 'minsel-mask-source',
      layout: {
        visibility: focusVisibility
      },
      paint: {
        'fill-color': '#475569',
        'fill-opacity': 0.42
      }
    });
  }

  // Layer 2: Vignette Kontras Halus Wilayah Luar Minsel
  if (!map.getLayer('minsel-outside-vignette')) {
    map.addLayer({
      id: 'minsel-outside-vignette',
      type: 'fill',
      source: 'minsel-mask-source',
      layout: {
        visibility: focusVisibility
      },
      paint: {
        'fill-color': '#0f172a',
        'fill-opacity': 0.12
      }
    });
  }

  // Layer 3: Feathered Blur di Tepi Batas Wilayah Luar (Optical Depth-of-Field)
  // Menghasilkan efek blur transisi yang lembut antara wilayah luar dengan Minsel
  if (!map.getLayer('minsel-boundary-feather-blur')) {
    map.addLayer({
      id: 'minsel-boundary-feather-blur',
      type: 'line',
      source: 'minsel-boundary-source',
      layout: {
        visibility: focusVisibility
      },
      paint: {
        'line-color': '#334155',
        'line-width': 28,
        'line-blur': 14,
        'line-opacity': 0.70
      }
    });
  }

  // Layer 4: Halo Cahaya Spasial (Luminous Glow Minsel)
  if (!map.getLayer('minsel-boundary-glow')) {
    map.addLayer({
      id: 'minsel-boundary-glow',
      type: 'line',
      source: 'minsel-boundary-source',
      layout: {
        visibility: focusVisibility
      },
      paint: {
        'line-color': '#0284c7',
        'line-width': 10,
        'line-blur': 5,
        'line-opacity': 0.75
      }
    });
  }

  // Layer 5: Garis Batas Kabupaten Utama (Tegas, Bold & Mudah Dikenali)
  // Selalu tampak tegas sebagai batas teritorial resmi Minahasa Selatan
  if (!map.getLayer('minsel-boundary-crisp')) {
    map.addLayer({
      id: 'minsel-boundary-crisp',
      type: 'line',
      source: 'minsel-boundary-source',
      paint: {
        'line-color': '#0284c7',
        'line-width': 3.5,
        'line-opacity': 1.0
      }
    });
  }

  // Layer 6: Garis Inti Presisi Biru Muda Cerah (Cyan Precision Core)
  if (!map.getLayer('minsel-boundary-core')) {
    map.addLayer({
      id: 'minsel-boundary-core',
      type: 'line',
      source: 'minsel-boundary-source',
      paint: {
        'line-color': '#38bdf8',
        'line-width': 2.0,
        'line-opacity': 0.95
      }
    });
  }

  // Layer 7: Garis Putus-Putus Kadaster Resmi (Official Cadastral Dash)
  if (!map.getLayer('minsel-boundary-dash')) {
    map.addLayer({
      id: 'minsel-boundary-dash',
      type: 'line',
      source: 'minsel-boundary-source',
      paint: {
        'line-color': '#ffffff',
        'line-width': 1.2,
        'line-dasharray': [4, 3],
        'line-opacity': 0.95
      }
    });
  }
};

// Update visibilitas layer fokus teritorial secara dinamis saat tombol toggle ditekan
const updateFocusMinselVisibility = () => {
  if (!map || !map.isStyleLoaded()) return;

  const visibility = layerStore.isFocusMinselActive ? 'visible' : 'none';
  const focusLayers = [
    'minsel-outside-desaturate',
    'minsel-outside-vignette',
    'minsel-boundary-feather-blur',
    'minsel-boundary-glow'
  ];

  for (const layerId of focusLayers) {
    if (map.getLayer(layerId)) {
      map.setLayoutProperty(layerId, 'visibility', visibility);
    }
  }
};

// ==============================================================================
// 3. MUAT DATA SPASIAL (KECAMATAN & OBJEK TEMATIK)
// ==============================================================================
const loadSpatialData = async () => {
  if (!map || !map.isStyleLoaded()) return;

  // 1. Selalu pasang layer Fokus Wilayah Minahasa Selatan terlebih dahulu
  setupMinselFocusLayers();

  // 2. Data Batas Kecamatan
  try {
    let kecData: any;
    try {
      kecData = await sigApi.getKecamatan();
      if (!kecData || !kecData.features || kecData.features.length === 0) {
        kecData = fallbackKecamatanGeoJson;
      }
    } catch {
      kecData = fallbackKecamatanGeoJson;
    }

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
            0.35,
            0.12
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
          'line-width': 1.6,
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

    // 3. Data Objek Spasial Tematik (Points)
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

    let layerData: any;
    try {
      layerData = await sigApi.getLayer('kesehatan', filters);
      if (!layerData || !layerData.features || layerData.features.length === 0) {
        layerData = fallbackThematicGeoJson;
      }
    } catch {
      layerData = fallbackThematicGeoJson;
    }

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

// ==============================================================================
// 4. MANAJEMEN MARKER LOKASI SAYA (ON / OFF)
// ==============================================================================
function syncUserLocationMarker() {
  if (!map) return;

  if (locationStore.isLocationActive && locationStore.currentCoords) {
    const { lng, lat, accuracy } = locationStore.currentCoords;

    if (!userLocationMarker) {
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
    if (userLocationMarker) {
      userLocationMarker.remove();
      userLocationMarker = null;
    }
  }
}

// Watcher toggle Fokus Minsel
watch(() => layerStore.isFocusMinselActive, () => {
  updateFocusMinselVisibility();
});

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

// Watcher pergantian basemap -> Rekonstruksi layer spasial & fokus teritorial Minsel
watch(() => layerStore.activeBasemap, (newBasemap) => {
  if (!map) return;
  const style = basemapStyles[newBasemap];
  map.setStyle(style);
  map.once('style.load', () => {
    loadSpatialData();
    syncUserLocationMarker();
  });
});

// Watcher pergantian filter kecamatan / desa -> Otomatis FlyTo ke wilayah terpilih
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
    // Reset ke Kabupaten Minahasa Selatan
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

    <!-- Badge Indikator Fokus Teritorial Minahasa Selatan -->
    <div
      v-if="layerStore.isFocusMinselActive"
      class="focus-status-badge animate-fade-in"
      title="Mode Fokus Wilayah Aktif: Wilayah luar Minsel sedikit diblur & didesaturasi, garis batas kabupaten ditegaskan"
    >
      <div class="badge-dot"></div>
      <span class="badge-text">Fokus Teritorial: <strong>Kab. Minahasa Selatan</strong></span>
      <button
        class="badge-close-btn"
        @click="layerStore.toggleFocusMinsel()"
        title="Nonaktifkan efek fokus"
      >
        &times;
      </button>
    </div>
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

/* Badge Indikator Fokus Teritorial Minsel */
.focus-status-badge {
  position: absolute;
  top: 14px;
  left: 14px;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px;
  background: rgba(255, 255, 255, 0.94);
  backdrop-filter: blur(8px);
  border: 1px solid #bae6fd;
  border-radius: 20px;
  box-shadow: 0 4px 12px rgba(2, 132, 199, 0.15);
  font-size: 11px;
  color: #0369a1;
  pointer-events: auto;
  user-select: none;
}

.badge-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #0284c7;
  box-shadow: 0 0 6px #38bdf8;
  animation: badgePulse 2s infinite ease-in-out;
}

@keyframes badgePulse {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.5;
    transform: scale(1.25);
  }
}

.badge-close-btn {
  background: transparent;
  border: none;
  font-size: 14px;
  line-height: 1;
  color: #94a3b8;
  cursor: pointer;
  padding: 0 2px;
  margin-left: 4px;
  transition: color 0.15s;
}

.badge-close-btn:hover {
  color: #ef4444;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
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
