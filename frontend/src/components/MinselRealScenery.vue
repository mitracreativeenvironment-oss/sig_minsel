<script setup lang="ts">
import { computed } from 'vue';
import { MapPin, Compass, Sparkles } from 'lucide-vue-next';

const props = withDefaults(
  defineProps<{
    scrollProgress: number; // 0.0 to 1.0
    activeSection: number;  // 0 to 4
  }>(),
  {
    scrollProgress: 0,
    activeSection: 0
  }
);

interface SceneryScene {
  id: string;
  title: string;
  subtitle: string;
  elevation: string;
  coords: string;
  tag: string;
  src: string;
}

// 5 Foto Nyata Autentik Koleksi Minahasa Selatan dari images/minsel
const scenes: SceneryScene[] = [
  {
    id: 'amurang',
    title: 'Panorama Udara Kota Amurang & Teluk Amurang',
    subtitle: 'Pusat Ibu Kota, Jalan Poros Trans Sulawesi & Pesisir Teluk Amurang',
    elevation: '0 – 25 mdpl',
    coords: '1.1824° N, 124.5772° E',
    tag: 'Ibu Kota & Pesisir',
    src: '/images/minsel/Screenshot%202026-10-27%20122041.png'
  },
  {
    id: 'kecamatan',
    title: 'Lembah Sungai Ranoyapo & Koridor 17 Kecamatan',
    subtitle: 'Jembatan Rangka Baja Ranoyapo, Hutan Tropis & Konektivitas Wilayah',
    elevation: '45 – 350 mdpl',
    coords: '1.1400° N, 124.5300° E',
    tag: 'Hierarki 17 Kecamatan',
    src: '/images/minsel/Screenshot%202026-10-27%20121902.png'
  },
  {
    id: 'modoinding',
    title: 'Lembah Pertanian Hortikultura Modoinding & Danau Moat',
    subtitle: 'Dapur Sayur Indonesia Timur, Lahan Subur Berundak & Danau Vulkanik',
    elevation: '1.020 mdpl',
    coords: '0.8385° N, 124.4412° E',
    tag: 'Ketahanan Pangan',
    src: '/images/minsel/Screenshot%202026-10-27%20123557.png'
  },
  {
    id: 'governance',
    title: 'Lanskap Teritorial Pesisir & Tata Ruang Minahasa Selatan',
    subtitle: 'Kawasan Strategis Daerah, Konservasi Mangrove & Tata Kelola Satu Data Spasial',
    elevation: '25 – 120 mdpl',
    coords: '1.1824° N, 124.5772° E',
    tag: 'Tata Kelola 8 Role',
    src: '/images/minsel/Screenshot%202026-10-27%20121450.png'
  },
  {
    id: 'maritim',
    title: 'Tradisi Bahari & Pamboat Nelayan Cadik Minahasa Selatan',
    subtitle: 'Kearifan Lokal Nelayan Pesisir, Pantai Alar & Gerbang Akses Geoportal Mandiri',
    elevation: '0 mdpl',
    coords: '1.1887° N, 124.5684° E',
    tag: 'Kearifan Lokal Maritim',
    src: '/images/minsel/pamboat-nelayan-cadik.jpg'
  }
];

// Menghitung opasitas dan efek parallax/Ken Burns zoom yang mulus saat scroll horizontal
function getSceneStyle(index: number) {
  const center = index / 4.0;
  const dist = Math.abs(props.scrollProgress - center);

  // Opasitas dengan kurva cosine bell yang halus
  const normDist = Math.min(1, dist / 0.26);
  const opacity = Math.max(0, Math.cos((normDist * Math.PI) / 2));

  // Translasi parallax berlawanan arah scroll (-140px s.d. +140px)
  const offset = (props.scrollProgress - center) * 140;
  // Bernapas lembut: 1.04x saat jauh -> 1.10x saat tepat aktif
  const scale = 1.04 + (1.0 - normDist) * 0.06;

  return {
    opacity: opacity.toFixed(3),
    transform: `scale(${scale.toFixed(3)}) translate3d(${-offset.toFixed(1)}px, 0, 0)`,
    zIndex: opacity > 0.5 ? 2 : 1
  };
}

// Scene aktif saat ini untuk label info lokasi
const currentScene = computed(() => {
  const idx = Math.max(0, Math.min(4, props.activeSection));
  return scenes[idx] || scenes[0];
});
</script>

<template>
  <div class="real-scenery-viewport">
    <!-- 5 Lapisan Foto Nyata Koleksi images/minsel dengan Parallax & Crossfade Mulus -->
    <div
      v-for="(scene, idx) in scenes"
      :key="scene.id"
      class="scenery-slide-layer"
      :style="getSceneStyle(idx)"
    >
      <img
        :src="scene.src"
        :alt="scene.title"
        class="scenery-img"
        loading="eager"
      />
    </div>

    <!-- Dual-Tone Cinematic Vignette Overlay untuk Kontras Tinggi Teks & Kartu Kaca -->
    <div class="scenery-vignette-overlay"></div>
    <div class="scenery-ambient-mesh"></div>

    <!-- Floating Location Badge di Sudut Kiri Bawah -->
    <div class="real-location-badge">
      <div class="badge-icon-wrap">
        <MapPin :size="15" class="icon-pulse-pin" />
      </div>
      <div class="badge-text-group">
        <div class="badge-loc-title">
          <span>{{ currentScene.title }}</span>
          <span class="badge-loc-elev">({{ currentScene.elevation }})</span>
          <span class="badge-tag-pill">{{ currentScene.tag }}</span>
        </div>
        <div class="badge-loc-sub">
          <Compass :size="11" />
          <span>{{ currentScene.coords }} &bull; {{ currentScene.subtitle }}</span>
        </div>
      </div>
    </div>

    <!-- Mini Indicator Koleksi Foto Asli di Sudut Kanan Bawah -->
    <div class="collection-counter-pill">
      <Sparkles :size="13" class="sparkle-gold" />
      <span>Dokumentasi Foto Asli Pemkab Minahasa Selatan</span>
      <span class="collection-dot">&bull;</span>
      <span class="collection-count">41 Arsip Foto Spasial</span>
    </div>
  </div>
</template>

<style scoped>
.real-scenery-viewport {
  position: absolute;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  z-index: 1;
  background-color: #030712;
}

.scenery-slide-layer {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  will-change: transform, opacity;
  transition: opacity 0.35s cubic-bezier(0.25, 1, 0.5, 1), transform 0.15s linear;
  overflow: hidden;
}

.scenery-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 45%;
  filter: contrast(1.1) saturate(1.15) brightness(0.88);
}

/* Dual-Tone Cinematic Vignette Overlay */
.scenery-vignette-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 5;
  background: radial-gradient(circle at 50% 40%, rgba(7, 13, 30, 0.18) 0%, rgba(7, 13, 30, 0.85) 100%),
              linear-gradient(180deg, rgba(7, 13, 30, 0.85) 0%, rgba(7, 13, 30, 0.22) 28%, rgba(7, 13, 30, 0.4) 68%, rgba(7, 13, 30, 0.92) 100%);
}

.scenery-ambient-mesh {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 6;
  background: radial-gradient(circle at 10% 25%, rgba(2, 132, 199, 0.16) 0%, transparent 45%),
              radial-gradient(circle at 90% 75%, rgba(37, 99, 235, 0.14) 0%, transparent 45%);
}

/* Floating Real Location Badge */
.real-location-badge {
  position: absolute;
  bottom: 24px;
  left: 32px;
  z-index: 25;
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(56, 189, 248, 0.35);
  padding: 8px 18px;
  border-radius: 30px;
  backdrop-filter: blur(14px);
  box-shadow: 0 4px 22px rgba(0, 0, 0, 0.5);
  pointer-events: auto;
  user-select: none;
  animation: fadeInBadge 0.4s ease-out;
}

.badge-icon-wrap {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: rgba(14, 165, 233, 0.2);
  color: #38bdf8;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-pulse-pin {
  animation: pulsePin 2s infinite ease-in-out;
}

@keyframes pulsePin {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); color: #7dd3fc; }
}

.badge-text-group {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.badge-loc-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 0.2px;
  flex-wrap: wrap;
}

.badge-loc-elev {
  font-size: 11px;
  font-weight: 600;
  color: #38bdf8;
}

.badge-tag-pill {
  font-size: 9.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  padding: 2px 7px;
  border-radius: 4px;
  background: rgba(56, 189, 248, 0.15);
  color: #7dd3fc;
  border: 1px solid rgba(56, 189, 248, 0.25);
}

.badge-loc-sub {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 10.5px;
  color: #94a3b8;
}

/* Floating Real Collection Pill (Kanan Bawah) */
.collection-counter-pill {
  position: absolute;
  bottom: 24px;
  right: 32px;
  z-index: 25;
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 11px;
  color: #cbd5e1;
  backdrop-filter: blur(10px);
  user-select: none;
}

.sparkle-gold {
  color: #fbbf24;
}

.collection-dot {
  color: #64748b;
}

.collection-count {
  font-weight: 700;
  color: #38bdf8;
}

@keyframes fadeInBadge {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 1024px) {
  .collection-counter-pill {
    display: none;
  }
  .real-location-badge {
    bottom: 74px;
    left: 16px;
    right: 16px;
  }
}
</style>
