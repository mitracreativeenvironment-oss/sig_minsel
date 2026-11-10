<script setup lang="ts">
import { computed } from 'vue';
import { MapPin, Compass } from 'lucide-vue-next';

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
  src: string;
}

const scenes: SceneryScene[] = [
  {
    id: 'amurang',
    title: 'Teluk Amurang & Pesisir Pantai Alar',
    subtitle: 'Pusat Ibu Kota & Gerbang Maritim Kabupaten Minahasa Selatan',
    elevation: '0 – 25 mdpl',
    coords: '1.1824° N, 124.5772° E',
    src: '/images/minsel/teluk-amurang.jpg'
  },
  {
    id: 'kecamatan',
    title: 'Bentang Alam 17 Wilayah Kecamatan',
    subtitle: 'Kawasan Permukiman, Pesisir, dan Lembah Ranoyapo',
    elevation: '15 – 350 mdpl',
    coords: '1.1400° N, 124.5300° E',
    src: '/images/minsel/kecamatan-aerial.jpg'
  },
  {
    id: 'modoinding',
    title: 'Dataran Tinggi Modoinding & Danau Moat',
    subtitle: 'Sentra Hortikultura Sayur Indonesia Timur & Lahan Subur',
    elevation: '1.020 mdpl',
    coords: '0.8385° N, 124.4412° E',
    src: '/images/minsel/modoinding-plateau.jpg'
  },
  {
    id: 'kantor-bupati',
    title: 'Pusat Komando Pemerintahan Minsel',
    subtitle: 'Kantor Bupati & Simpul Integrasi Satu Data Spasial',
    elevation: '45 mdpl',
    coords: '1.1824° N, 124.5772° E',
    src: '/images/minsel/kantor-bupati.jpg'
  },
  {
    id: 'soputan',
    title: 'Puncak Gunung Soputan & Hutan Lindung',
    subtitle: 'Puncak Vulkanik Tertinggi & Keanekaragaman Hayati Minsel',
    elevation: '1.784 mdpl',
    coords: '1.1160° N, 124.7300° E',
    src: '/images/minsel/gunung-soputan.jpg'
  }
];

// Menghitung opasitas dan transformasi pergerakan parallax tiap foto
function getSceneStyle(index: number) {
  const center = index / 4.0;
  const dist = Math.abs(props.scrollProgress - center);
  
  // Opasitas dengan transisi smooth cosine bell
  // Rentang pengaruh selebar 0.25 (jarak antar bab)
  const normDist = Math.min(1, dist / 0.26);
  const opacity = Math.max(0, Math.cos((normDist * Math.PI) / 2));

  // Parallax translation horizontal dan efek Ken Burns zoom
  const offset = (props.scrollProgress - center) * 120; // geser 120px berlawanan arah scroll
  const scale = 1.04 + (1.0 - normDist) * 0.06; // bernapas lembut 1.04x ke 1.10x

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
    <!-- 5 Lapisan Foto Lanskap Nyata dengan Parallax & Crossfade -->
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

    <!-- Dual-Tone Gradient Vignette agar Teks di Depan Tetap Kontras & Tajam -->
    <div class="scenery-vignette-overlay"></div>
    <div class="scenery-ambient-mesh"></div>

    <!-- Floating Location Badge di Sudut Kiri Bawah -->
    <div class="real-location-badge">
      <div class="badge-icon-wrap">
        <MapPin :size="14" class="icon-pulse-pin" />
      </div>
      <div class="badge-text-group">
        <div class="badge-loc-title">
          <span>{{ currentScene.title }}</span>
          <span class="badge-loc-elev">({{ currentScene.elevation }})</span>
        </div>
        <div class="badge-loc-sub">
          <Compass :size="11" />
          <span>{{ currentScene.coords }} &bull; {{ currentScene.subtitle }}</span>
        </div>
      </div>
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
  background-color: #050a17;
}

.scenery-slide-layer {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  will-change: transform, opacity;
  transition: opacity 0.3s cubic-bezier(0.25, 1, 0.5, 1), transform 0.15s linear;
  overflow: hidden;
}

.scenery-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center 40%;
  filter: contrast(1.08) saturate(1.12) brightness(0.85);
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
  background: radial-gradient(circle at 50% 45%, rgba(7, 13, 30, 0.25) 0%, rgba(7, 13, 30, 0.88) 100%),
              linear-gradient(180deg, rgba(7, 13, 30, 0.82) 0%, rgba(7, 13, 30, 0.2) 30%, rgba(7, 13, 30, 0.45) 70%, rgba(7, 13, 30, 0.92) 100%);
}

.scenery-ambient-mesh {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 6;
  background: radial-gradient(circle at 15% 20%, rgba(2, 132, 199, 0.18) 0%, transparent 50%),
              radial-gradient(circle at 85% 80%, rgba(37, 99, 235, 0.15) 0%, transparent 50%);
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
  background: rgba(15, 23, 42, 0.88);
  border: 1px solid rgba(56, 189, 248, 0.3);
  padding: 8px 16px;
  border-radius: 30px;
  backdrop-filter: blur(12px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.45);
  pointer-events: auto;
  user-select: none;
  animation: fadeInBadge 0.4s ease-out;
}

.badge-icon-wrap {
  width: 26px;
  height: 26px;
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
  gap: 6px;
  font-size: 12px;
  font-weight: 700;
  color: #ffffff;
  letter-spacing: 0.2px;
}

.badge-loc-elev {
  font-size: 11px;
  font-weight: 600;
  color: #38bdf8;
}

.badge-loc-sub {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 10.5px;
  color: #94a3b8;
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

@media (max-width: 768px) {
  .real-location-badge {
    bottom: 70px;
    left: 16px;
    right: 16px;
  }
}
</style>

