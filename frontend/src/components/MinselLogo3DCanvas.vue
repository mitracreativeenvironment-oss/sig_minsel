<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import * as THREE from 'three';

const props = withDefaults(
  defineProps<{
    scrollProgress?: number;
    activeSection?: number;
  }>(),
  {
    scrollProgress: 0,
    activeSection: 0
  }
);

const canvasContainer = ref<HTMLDivElement | null>(null);

// Three.js Core Objects
let renderer: THREE.WebGLRenderer | null = null;
let scene: THREE.Scene | null = null;
let camera: THREE.PerspectiveCamera | null = null;
let timer: THREE.Timer | null = null;
let animationFrameId: number | null = null;

// Assembly 3D Mesh Groups
let assemblyRoot: THREE.Group | null = null;
let shieldMesh: THREE.Mesh | null = null;
let wingLeftMesh: THREE.Mesh | null = null;
let wingRightMesh: THREE.Mesh | null = null;
let bodyHeadMesh: THREE.Mesh | null = null;
let chestMesh: THREE.Mesh | null = null;
let ribbonMesh: THREE.Mesh | null = null;
let completeEmblemMesh: THREE.Mesh | null = null;
let compassHalo: THREE.Mesh | null = null;
let pulseRingMesh: THREE.Mesh | null = null;
let starFieldGroup: THREE.Points | null = null;

// Textures to dispose cleanly
let loadedTextures: THREE.Texture[] = [];

// Mouse Parallax
const mouseX = ref(0);
const mouseY = ref(0);
const targetMouseX = ref(0);
const targetMouseY = ref(0);

// Helper for smooth Hermite interpolation
function smoothStep(edge0: number, edge1: number, x: number): number {
  const t = Math.max(0, Math.min(1, (x - edge0) / (edge1 - edge0)));
  return t * t * (3 - 2 * t);
}

// Dynamic HUD Phase Narrative based on authentic heraldry
const currentPhase = computed(() => {
  const p = props.scrollProgress;
  if (p < 0.22) {
    return {
      step: 'FASE 1 / 5',
      name: 'Perisai Merah Putih & Header "MINAHASA SELATAN"',
      desc: 'Fondasi kedaulatan 17 Kecamatan & bingkai kebangsaan Sang Saka Merah Putih',
      color: '#ef4444'
    };
  } else if (p < 0.45) {
    return {
      step: 'FASE 2 / 5',
      name: 'Sayap Manguni Minahasa (17 Helai Sayap)',
      desc: 'Ketajaman penglihatan, kearifan lokal tanah Minahasa & bentangan maritim',
      color: '#b45309'
    };
  } else if (p < 0.68) {
    return {
      step: 'FASE 3 / 5',
      name: 'Kepala & Badan Burung Manguni Waspada',
      desc: 'Mata bulat waspada mengawasi tata kelola data & keterbukaan spasial',
      color: '#f59e0b'
    };
  } else if (p < 0.88) {
    return {
      step: 'FASE 4 / 5',
      name: 'Buku Satu Data, Pohon Kelapa & Semboyan "Cita Waya Esa"',
      desc: 'Kemakmuran bahari/hortikultura & persatuan luhur "Kita Semua Satu"',
      color: '#10b981'
    };
  } else {
    return {
      step: 'FASE 5 / 5',
      name: 'Lambang Resmi Kabupaten Minahasa Selatan',
      desc: 'Integrasi utuh Satu Data Geospasial Kabupaten Minahasa Selatan (Cita Waya Esa)',
      color: '#fde047'
    };
  }
});

function initThreeScene() {
  if (!canvasContainer.value) return;

  const width = canvasContainer.value.clientWidth;
  const height = canvasContainer.value.clientHeight;

  // 1. WebGL Renderer
  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setSize(width, height);
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.3;
  canvasContainer.value.appendChild(renderer.domElement);

  // 2. Scene & Fog Setup
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x060c1d); // Deep celestial midnight navy
  scene.fog = new THREE.FogExp2(0x060c1d, 0.014);

  // 3. Perspective Camera
  camera = new THREE.PerspectiveCamera(46, width / height, 0.5, 400);
  camera.position.set(0, 0, 38);
  camera.lookAt(0, 0, 0);

  // 4. Studio Lighting Rig
  const ambientLight = new THREE.AmbientLight(0x334155, 2.2);
  scene.add(ambientLight);

  // Primary Sun Key Light (Warm Sunlight on heraldic gold & red)
  const sunLight = new THREE.DirectionalLight(0xfef08a, 3.2);
  sunLight.position.set(35, 45, 40);
  scene.add(sunLight);

  // Maritime Cyan Rim Light from top-left
  const rimLight = new THREE.DirectionalLight(0x38bdf8, 2.4);
  rimLight.position.set(-40, 25, -20);
  scene.add(rimLight);

  // Warm Bottom Fill Light
  const fillLight = new THREE.DirectionalLight(0xf59e0b, 1.4);
  fillLight.position.set(0, -35, 20);
  scene.add(fillLight);

  // 5. Celestial Background Starfield
  const starGeo = new THREE.BufferGeometry();
  const starCount = 200;
  const starPositions = new Float32Array(starCount * 3);
  for (let i = 0; i < starCount * 3; i += 3) {
    starPositions[i] = (Math.random() - 0.5) * 160;
    starPositions[i + 1] = (Math.random() - 0.5) * 120;
    starPositions[i + 2] = -40 - Math.random() * 80;
  }
  starGeo.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
  const starMat = new THREE.PointsMaterial({
    color: 0x38bdf8,
    size: 1.3,
    transparent: true,
    opacity: 0.4
  });
  starFieldGroup = new THREE.Points(starGeo, starMat);
  scene.add(starFieldGroup);

  // 6. Master Assembly Root
  assemblyRoot = new THREE.Group();
  scene.add(assemblyRoot);

  // 7. Load Authentic Segmented Layers from Lambang_Kabupaten_Minahasa_Selatan.png
  const textureLoader = new THREE.TextureLoader();

  function loadLayerTexture(url: string): THREE.Texture {
    const tex = textureLoader.load(url);
    tex.colorSpace = THREE.SRGBColorSpace;
    tex.wrapS = tex.wrapT = THREE.ClampToEdgeWrapping;
    loadedTextures.push(tex);
    return tex;
  }

  const texShield = loadLayerTexture('/images/minsel_logo_3d/layer_shield_base.png');
  const texWingLeft = loadLayerTexture('/images/minsel_logo_3d/layer_wing_left.png');
  const texWingRight = loadLayerTexture('/images/minsel_logo_3d/layer_wing_right.png');
  const texBodyHead = loadLayerTexture('/images/minsel_logo_3d/layer_body_head.png');
  const texChest = loadLayerTexture('/images/minsel_logo_3d/layer_chest_emblem.png');
  const texRibbon = loadLayerTexture('/images/minsel_logo_3d/layer_ribbon.png');
  const texComplete = loadLayerTexture('/images/minsel_logo_3d/layer_emblem_complete.png');

  // Aspect ratio derived from 929 x 1227 official emblem
  const planeW = 20.0;
  const planeH = planeW * (1227 / 929); // ~26.41 units
  const layerGeo = new THREE.PlaneGeometry(planeW, planeH, 32, 32);

  function createLayerMaterial(texture: THREE.Texture) {
    return new THREE.MeshStandardMaterial({
      map: texture,
      transparent: true,
      alphaTest: 0.02,
      roughness: 0.32,
      metalness: 0.2,
      side: THREE.DoubleSide
    });
  }

  // Layer 1: Shield Base (Red/White + Header "MINAHASA SELATAN" + Black Contour)
  const shieldMat = createLayerMaterial(texShield);
  shieldMesh = new THREE.Mesh(layerGeo, shieldMat);
  shieldMesh.renderOrder = 1;
  assemblyRoot.add(shieldMesh);

  // Layer 2: Left Wing
  const wingLeftMat = createLayerMaterial(texWingLeft);
  wingLeftMesh = new THREE.Mesh(layerGeo, wingLeftMat);
  wingLeftMesh.renderOrder = 2;
  assemblyRoot.add(wingLeftMesh);

  // Layer 3: Right Wing
  const wingRightMat = createLayerMaterial(texWingRight);
  wingRightMesh = new THREE.Mesh(layerGeo, wingRightMat);
  wingRightMesh.renderOrder = 3;
  assemblyRoot.add(wingRightMesh);

  // Layer 4: Body, Head, Tail & Talons
  const bodyHeadMat = createLayerMaterial(texBodyHead);
  bodyHeadMesh = new THREE.Mesh(layerGeo, bodyHeadMat);
  bodyHeadMesh.renderOrder = 4;
  assemblyRoot.add(bodyHeadMesh);

  // Layer 5: Chest Book, Sea & Coconut Palm Tree
  const chestMat = createLayerMaterial(texChest);
  chestMesh = new THREE.Mesh(layerGeo, chestMat);
  chestMesh.renderOrder = 5;
  assemblyRoot.add(chestMesh);

  // Layer 6: Yellow Banner "Cita Waya Esa"
  const ribbonMat = createLayerMaterial(texRibbon);
  ribbonMesh = new THREE.Mesh(layerGeo, ribbonMat);
  ribbonMesh.renderOrder = 6;
  assemblyRoot.add(ribbonMesh);

  // Layer 7: Complete Official Emblem (for final perfect lock)
  const completeMat = createLayerMaterial(texComplete);
  completeEmblemMesh = new THREE.Mesh(layerGeo, completeMat);
  completeEmblemMesh.renderOrder = 7;
  completeEmblemMesh.visible = false;
  assemblyRoot.add(completeEmblemMesh);

  // Floating Geodetic Azimuth Ring (behind the shield)
  const haloGeo = new THREE.RingGeometry(15.2, 15.6, 64);
  const haloMat = new THREE.MeshBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.3, side: THREE.DoubleSide });
  compassHalo = new THREE.Mesh(haloGeo, haloMat);
  compassHalo.position.z = -1.5;
  assemblyRoot.add(compassHalo);

  // Golden-Cyan Flare Shockwave Ring
  const pulseGeo = new THREE.RingGeometry(1.0, 1.5, 64);
  const pulseMat = new THREE.MeshBasicMaterial({
    color: 0xfde047,
    transparent: true,
    opacity: 0.0,
    side: THREE.DoubleSide
  });
  pulseRingMesh = new THREE.Mesh(pulseGeo, pulseMat);
  pulseRingMesh.position.z = 2.5;
  assemblyRoot.add(pulseRingMesh);

  // 8. Animation Loop & Kinetic Choreography
  timer = new THREE.Timer();
  timer.connect(document);

  const animate = () => {
    if (!renderer || !scene || !camera || !timer) return;
    timer.update();
    const delta = timer.getDelta();
    const elapsed = timer.getElapsed();

    // Rotate starfield
    if (starFieldGroup) {
      starFieldGroup.rotation.y = elapsed * 0.02;
    }

    // Smooth Mouse Parallax
    mouseX.value += (targetMouseX.value - mouseX.value) * 0.05;
    mouseY.value += (targetMouseY.value - mouseY.value) * 0.05;

    // Apply Assembly Kinetics
    updateAssemblyKinetics(props.scrollProgress, delta, elapsed);

    renderer.render(scene, camera);
    animationFrameId = requestAnimationFrame(animate);
  };

  animationFrameId = requestAnimationFrame(animate);
  window.addEventListener('resize', handleResize);
  window.addEventListener('mousemove', handleMouseMove);
}

// Update the position, rotation, and visibility of each authentic layer based on scrollProgress
function updateAssemblyKinetics(progress: number, _delta: number, elapsed: number) {
  if (!assemblyRoot) return;

  const p = Math.max(0, Math.min(1, progress));

  // --- STAGE 0: SHIELD BASE & "MINAHASA SELATAN" HEADER (0.00 -> 0.22) ---
  const tShield = smoothStep(0.0, 0.22, p);
  if (shieldMesh) {
    shieldMesh.position.set(
      0,
      (1 - tShield) * 8.0,
      (1 - tShield) * -38.0
    );
    shieldMesh.rotation.set(
      (1 - tShield) * 0.35,
      (1 - tShield) * 0.55,
      (1 - tShield) * -0.15
    );
    shieldMesh.scale.setScalar(0.45 + tShield * 0.55);
    (shieldMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tShield * 1.5 + 0.1);
  }

  // --- STAGE 1: WINGS OF BURUNG MANGUNI (0.20 -> 0.45) ---
  const tWings = smoothStep(0.20, 0.45, p);
  if (wingLeftMesh) {
    // Docked at (0, 0, 0.3)
    wingLeftMesh.position.set(
      -(1 - tWings) * 28.0,
      (1 - tWings) * 14.0,
      0.3 + (1 - tWings) * 20.0
    );
    wingLeftMesh.rotation.set(
      (1 - tWings) * 0.4,
      -(1 - tWings) * 0.8,
      -(1 - tWings) * 0.7
    );
    wingLeftMesh.scale.setScalar(0.25 + tWings * 0.75);
    (wingLeftMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tWings * 1.4);
    wingLeftMesh.visible = tWings > 0.01;
  }

  if (wingRightMesh) {
    // Docked at (0, 0, 0.3)
    wingRightMesh.position.set(
      (1 - tWings) * 28.0,
      (1 - tWings) * 14.0,
      0.3 + (1 - tWings) * 20.0
    );
    wingRightMesh.rotation.set(
      (1 - tWings) * 0.4,
      (1 - tWings) * 0.8,
      (1 - tWings) * 0.7
    );
    wingRightMesh.scale.setScalar(0.25 + tWings * 0.75);
    (wingRightMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tWings * 1.4);
    wingRightMesh.visible = tWings > 0.01;
  }

  // --- STAGE 2: HEAD & BODY OF BURUNG MANGUNI (0.42 -> 0.68) ---
  const tBody = smoothStep(0.42, 0.68, p);
  if (bodyHeadMesh) {
    // Docked at (0, 0, 0.6)
    bodyHeadMesh.position.set(
      0,
      (1 - tBody) * 24.0,
      0.6 + (1 - tBody) * 22.0
    );
    bodyHeadMesh.rotation.set(
      (1 - tBody) * 0.7,
      0,
      -(1 - tBody) * 0.3
    );
    bodyHeadMesh.scale.setScalar(0.2 + tBody * 0.8);
    (bodyHeadMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tBody * 1.4);
    bodyHeadMesh.visible = tBody > 0.01;
  }

  // --- STAGE 3: CHEST EMBLEM & YELLOW RIBBON (0.65 -> 0.88) ---
  const tChest = smoothStep(0.65, 0.88, p);
  if (chestMesh) {
    // Docked at (0, 0, 0.9)
    chestMesh.position.set(
      0,
      -(1 - tChest) * 6.0,
      0.9 + (1 - tChest) * 22.0
    );
    chestMesh.scale.setScalar(0.2 + tChest * 0.8);
    (chestMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tChest * 1.4);
    chestMesh.visible = tChest > 0.01;
  }

  if (ribbonMesh) {
    // Docked at (0, 0, 1.2)
    ribbonMesh.position.set(
      0,
      -(1 - tChest) * 24.0,
      1.2 + (1 - tChest) * 16.0
    );
    ribbonMesh.rotation.x = -(1 - tChest) * 0.8;
    ribbonMesh.scale.setScalar(0.2 + tChest * 0.8);
    (ribbonMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tChest * 1.4);
    ribbonMesh.visible = tChest > 0.01;
  }

  // --- STAGE 4: COMPLETE EMBLEM LOCK & GOLDEN FLARE (0.85 -> 1.00) ---
  const tSnap = smoothStep(0.85, 1.0, p);

  // Crossfade between individual exploded layers and single pristine solid emblem
  if (completeEmblemMesh) {
    if (tSnap > 0.7) {
      completeEmblemMesh.visible = true;
      (completeEmblemMesh.material as THREE.MeshStandardMaterial).opacity = (tSnap - 0.7) / 0.3;
      completeEmblemMesh.position.z = 1.4;
    } else {
      completeEmblemMesh.visible = false;
    }
  }

  // Flare Shockwave Effect
  if (pulseRingMesh) {
    if (tSnap > 0.05) {
      const pulseCycle = (elapsed * 1.5) % 1.0;
      const pulseScale = 10.0 + pulseCycle * 18.0;
      pulseRingMesh.scale.set(pulseScale, pulseScale, 1);
      (pulseRingMesh.material as THREE.MeshBasicMaterial).opacity = Math.max(0, (1.0 - pulseCycle) * 0.6 * tSnap);
      pulseRingMesh.visible = true;
    } else {
      pulseRingMesh.visible = false;
    }
  }

  // Floating Levitation & Mouse Tilt Parallax
  const idleY = Math.sin(elapsed * 1.6) * 0.35 * (0.3 + tSnap * 0.7);
  const idleRoll = Math.cos(elapsed * 1.1) * 0.02 * (0.3 + tSnap * 0.7);

  const parallaxX = mouseX.value * 0.32;
  const parallaxY = -mouseY.value * 0.22;

  assemblyRoot.position.y = idleY;
  assemblyRoot.rotation.x = parallaxY + (1 - tSnap) * 0.04;
  assemblyRoot.rotation.y = parallaxX + idleRoll + (1 - tSnap) * 0.08;

  // Slowly rotate background compass halo
  if (compassHalo) {
    compassHalo.rotation.z = elapsed * 0.06;
  }
}

function handleMouseMove(e: MouseEvent) {
  mouseX.value = (e.clientX / window.innerWidth) * 2 - 1;
  mouseY.value = (e.clientY / window.innerHeight) * 2 - 1;
}

function handleResize() {
  if (!canvasContainer.value || !renderer || !camera) return;
  const width = canvasContainer.value.clientWidth;
  const height = canvasContainer.value.clientHeight;

  camera.aspect = width / height;
  camera.updateProjectionMatrix();
  renderer.setSize(width, height);
}

onMounted(() => {
  initThreeScene();
});

onBeforeUnmount(() => {
  if (animationFrameId !== null) {
    cancelAnimationFrame(animationFrameId);
  }
  window.removeEventListener('resize', handleResize);
  window.removeEventListener('mousemove', handleMouseMove);

  // Dispose Textures
  loadedTextures.forEach((tex) => tex.dispose());
  loadedTextures = [];

  // Dispose Three.js Resources
  if (renderer) {
    renderer.dispose();
    if (renderer.domElement && renderer.domElement.parentNode) {
      renderer.domElement.parentNode.removeChild(renderer.domElement);
    }
  }

  // Dispose Geometries & Materials recursively
  assemblyRoot?.traverse((obj) => {
    if (obj instanceof THREE.Mesh) {
      obj.geometry?.dispose();
      if (Array.isArray(obj.material)) {
        obj.material.forEach((m) => m.dispose());
      } else if (obj.material) {
        obj.material.dispose();
      }
    }
  });
});
</script>

<template>
  <div ref="canvasContainer" class="threejs-logo-canvas">
    <!-- Spatial Coordinates & Assembly Phase HUD Overlay -->
    <div class="hud-phase-tracker">
      <div class="hud-phase-pill" :style="{ borderColor: currentPhase.color + '55' }">
        <span class="hud-pulse" :style="{ backgroundColor: currentPhase.color, boxShadow: '0 0 10px ' + currentPhase.color }"></span>
        <span class="hud-step-tag">{{ currentPhase.step }}</span>
        <span class="hud-sep">&bull;</span>
        <span class="hud-name" :style="{ color: currentPhase.color }">{{ currentPhase.name }}</span>
      </div>
      <div class="hud-phase-desc">
        {{ currentPhase.desc }}
      </div>
      <div class="hud-meta">
        <span>ASSEMBLY: {{ Math.round(props.scrollProgress * 100) }}%</span>
        <span class="hud-sep">|</span>
        <span>EPSG:4326 (WGS84)</span>
        <span class="hud-sep">|</span>
        <span>KAB. MINAHASA SELATAN</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.threejs-logo-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
  overflow: hidden;
}

.threejs-logo-canvas :deep(canvas) {
  display: block;
  width: 100% !important;
  height: 100% !important;
}

/* Minimal HUD Assembly Phase Tracker */
.hud-phase-tracker {
  position: absolute;
  bottom: 24px;
  right: 24px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
  z-index: 5;
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  pointer-events: none;
}

.hud-phase-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(56, 189, 248, 0.3);
  backdrop-filter: blur(10px);
  padding: 5px 12px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.5);
  transition: border-color 0.4s ease;
}

.hud-step-tag {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 500;
}

.hud-name {
  font-weight: 700;
  transition: color 0.4s ease;
}

.hud-pulse {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  animation: pulse 1.8s infinite ease-in-out;
  transition: background-color 0.4s ease, box-shadow 0.4s ease;
}

@keyframes pulse {
  0%, 100% { opacity: 0.4; transform: scale(0.9); }
  50% { opacity: 1; transform: scale(1.3); }
}

.hud-phase-desc {
  font-size: 11px;
  color: #cbd5e1;
  max-width: 380px;
  text-align: right;
  line-height: 1.4;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.9);
}

.hud-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  color: #64748b;
  letter-spacing: 0.5px;
}

.hud-sep {
  color: #475569;
}
</style>
