<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import * as THREE from 'three';
import elevationData from '../data/minsel_elevation_grid.json';

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

// Scene Meshes & Groups
let terrainMesh: THREE.Mesh | null = null;
let wireframeMesh: THREE.LineSegments | null = null;
let waterMesh: THREE.Mesh | null = null;
let beaconsGroup: THREE.Group | null = null;
let compassGroup: THREE.Group | null = null;
let dataFlowGroup: THREE.Group | null = null;

// Textures to dispose cleanly
let loadedTextures: THREE.Texture[] = [];

// Mouse Parallax
const mouseX = ref(0);
const mouseY = ref(0);
const targetMouseX = ref(0);
const targetMouseY = ref(0);

// Key Locales in Minahasa Selatan Coordinates (3D World Space)
const minselLocales = [
  { name: 'Amurang (Ibu Kota & Teluk)', pos: new THREE.Vector3(-18, 0, -2), elev: '5m', color: 0x38bdf8, code: 'AMR' },
  { name: 'Gunung Soputan (Puncak)', pos: new THREE.Vector3(34, 0, -18), elev: '1,784m', color: 0xf59e0b, code: 'SPT' },
  { name: 'Modoinding (Hortikultura)', pos: new THREE.Vector3(20, 0, 22), elev: '1,020m', color: 0x10b981, code: 'MDD' },
  { name: 'Tumpaan (Gerbang Utara)', pos: new THREE.Vector3(-8, 0, -26), elev: '15m', color: 0x6366f1, code: 'TMP' },
  { name: 'Sinonsayang (Pesisir Barat)', pos: new THREE.Vector3(-26, 0, 20), elev: '8m', color: 0xec4899, code: 'SNS' },
  { name: 'Ranoyapo (Lembah Sungai)', pos: new THREE.Vector3(4, 0, 0), elev: '280m', color: 0x14b8a6, code: 'RNP' }
];

// Waypoint Camera Interpolation Keyframes for the 5 Sections
interface CameraWaypoint {
  pos: THREE.Vector3;
  target: THREE.Vector3;
  fov: number;
}

const waypoints: CameraWaypoint[] = [
  // 0: Hero - High-Altitude Orbital Panoramic Overview
  { pos: new THREE.Vector3(0, 52, 70), target: new THREE.Vector3(0, 4, -4), fov: 48 },
  // 1: 17 Kecamatan & Teluk Amurang - Low Oblique Coastal Flight
  { pos: new THREE.Vector3(-30, 16, 24), target: new THREE.Vector3(-14, 3, -4), fov: 52 },
  // 2: Dataran Tinggi Modoinding & 28 Kategori - Climbing towards Agricultural Crater
  { pos: new THREE.Vector3(22, 28, -6), target: new THREE.Vector3(18, 10, 20), fov: 50 },
  // 3: Tata Kelola 8 Role & Persetujuan Terpusat - Bird's-Eye Data Hub
  { pos: new THREE.Vector3(4, 44, 16), target: new THREE.Vector3(0, 3, 0), fov: 46 },
  // 4: Call to Action - Epic Horizon Sunrise towards Soputan Peak
  { pos: new THREE.Vector3(-16, 16, 50), target: new THREE.Vector3(28, 18, -14), fov: 48 }
];

const currentCamPos = new THREE.Vector3().copy(waypoints[0].pos);
const currentCamTarget = new THREE.Vector3().copy(waypoints[0].target);

// Topographic Elevation from Real Minahasa Selatan Photos (Bilinear Grid Interpolation)
const GRID_WORLD_SIZE = 140;
const MAX_ELEVATION = 22.0;

function getRealPhotoElevation(x: number, z: number): number {
  if (!elevationData || !elevationData.data) return 1.0;
  
  const gridSize = elevationData.grid_size || 64;
  // Map world x, z from [-GRID_WORLD_SIZE/2, GRID_WORLD_SIZE/2] to [0, 1]
  const u = (x + GRID_WORLD_SIZE / 2) / GRID_WORLD_SIZE;
  const v = (z + GRID_WORLD_SIZE / 2) / GRID_WORLD_SIZE;

  if (u < 0 || u > 1 || v < 0 || v > 1) return 0.5;

  // Image row 0 is top (North, v=0), row 63 is bottom (South, v=1)
  const row = v * (gridSize - 1);
  const col = u * (gridSize - 1);

  const r0 = Math.floor(row);
  const r1 = Math.min(gridSize - 1, r0 + 1);
  const c0 = Math.floor(col);
  const c1 = Math.min(gridSize - 1, c0 + 1);

  const fr = row - r0;
  const fc = col - c0;

  const v00 = elevationData.data[r0][c0];
  const v01 = elevationData.data[r0][c1];
  const v10 = elevationData.data[r1][c0];
  const v11 = elevationData.data[r1][c1];

  const top = v00 * (1 - fc) + v01 * fc;
  const btm = v10 * (1 - fc) + v11 * fc;
  const rawElev = top * (1 - fr) + btm * fr;

  return Math.max(0.1, rawElev * MAX_ELEVATION);
}

function initThreeScene() {
  if (!canvasContainer.value) return;

  const width = canvasContainer.value.clientWidth;
  const height = canvasContainer.value.clientHeight;

  // 1. Renderer Setup
  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  renderer.setSize(width, height);
  renderer.outputColorSpace = THREE.SRGBColorSpace;
  renderer.toneMapping = THREE.ACESFilmicToneMapping;
  renderer.toneMappingExposure = 1.25;
  canvasContainer.value.appendChild(renderer.domElement);

  // 2. Scene & Fog Setup (Deep Maritime & Volcanic Atmosphere)
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x060c1d); // Very deep oceanic navy
  scene.fog = new THREE.FogExp2(0x060c1d, 0.012);

  // 3. Camera Setup
  camera = new THREE.PerspectiveCamera(48, width / height, 0.5, 500);
  camera.position.copy(currentCamPos);
  camera.lookAt(currentCamTarget);

  // 4. Lighting Rig
  const ambientLight = new THREE.AmbientLight(0x334155, 1.9);
  scene.add(ambientLight);

  // Primary Sun Key Light (Warm Tropical Sunlight)
  const sunLight = new THREE.DirectionalLight(0xfef08a, 2.9);
  sunLight.position.set(45, 55, 30);
  scene.add(sunLight);

  // Secondary Maritime Rim Light (Cyan / Ocean Reflection from Teluk Amurang)
  const oceanRimLight = new THREE.DirectionalLight(0x0284c7, 2.4);
  oceanRimLight.position.set(-50, 20, -30);
  scene.add(oceanRimLight);

  // Tertiary Emerald Highland Accent Light (Modoinding Agro Valley)
  const emeraldLight = new THREE.PointLight(0x10b981, 2.2, 90);
  emeraldLight.position.set(20, 25, 20);
  scene.add(emeraldLight);

  // 5. Load Real Photo PBR Textures
  const textureLoader = new THREE.TextureLoader();

  // Diffuse Map: Real photo composite of Amurang & Modoinding
  const diffuseTexture = textureLoader.load('/images/minsel/3d_terrain/minsel_topo_diffuse.jpg');
  diffuseTexture.colorSpace = THREE.SRGBColorSpace;
  diffuseTexture.wrapS = diffuseTexture.wrapT = THREE.ClampToEdgeWrapping;
  loadedTextures.push(diffuseTexture);

  // Normal Map: Real terrain slope vectors (OpenGL +Y Up)
  const normalTexture = textureLoader.load('/images/minsel/3d_terrain/minsel_topo_normal.png');
  normalTexture.colorSpace = THREE.NoColorSpace;
  normalTexture.wrapS = normalTexture.wrapT = THREE.ClampToEdgeWrapping;
  loadedTextures.push(normalTexture);

  // Roughness Map: Soil, vegetation & water specular distinction
  const roughnessTexture = textureLoader.load('/images/minsel/3d_terrain/minsel_topo_roughness.png');
  roughnessTexture.colorSpace = THREE.NoColorSpace;
  roughnessTexture.wrapS = roughnessTexture.wrapT = THREE.ClampToEdgeWrapping;
  loadedTextures.push(roughnessTexture);

  // Ambient Occlusion Map: Cavity & ravine shading
  const aoTexture = textureLoader.load('/images/minsel/3d_terrain/minsel_topo_ao.png');
  aoTexture.colorSpace = THREE.NoColorSpace;
  aoTexture.wrapS = aoTexture.wrapT = THREE.ClampToEdgeWrapping;
  loadedTextures.push(aoTexture);

  // 6. 3D Terrain Construction Displaced by Real Photo Elevation
  const segments = 130;
  const terrainGeo = new THREE.PlaneGeometry(GRID_WORLD_SIZE, GRID_WORLD_SIZE, segments, segments);
  terrainGeo.rotateX(-Math.PI / 2);

  // Set secondary UV for Ambient Occlusion
  terrainGeo.attributes.uv2 = terrainGeo.attributes.uv;

  const posAttr = terrainGeo.attributes.position;
  for (let i = 0; i < posAttr.count; i++) {
    const vx = posAttr.getX(i);
    const vz = posAttr.getZ(i);
    const vy = getRealPhotoElevation(vx, vz);
    posAttr.setY(i, vy);
  }
  terrainGeo.computeVertexNormals();

  const terrainMat = new THREE.MeshStandardMaterial({
    map: diffuseTexture,
    normalMap: normalTexture,
    normalScale: new THREE.Vector2(1.6, 1.6),
    roughnessMap: roughnessTexture,
    roughness: 0.82,
    metalness: 0.12,
    aoMap: aoTexture,
    aoMapIntensity: 0.7,
    flatShading: false
  });
  terrainMesh = new THREE.Mesh(terrainGeo, terrainMat);
  scene.add(terrainMesh);

  // 7. Topographic Wireframe / Contour Overlay (Subtle Cartographic Grid)
  const wireGeo = new THREE.WireframeGeometry(terrainGeo);
  const wireMat = new THREE.LineBasicMaterial({
    color: 0x38bdf8,
    transparent: true,
    opacity: 0.13
  });
  wireframeMesh = new THREE.LineSegments(wireGeo, wireMat);
  scene.add(wireframeMesh);

  // 8. Teluk Amurang Water Plane
  const waterGeo = new THREE.PlaneGeometry(55, 55, 20, 20);
  waterGeo.rotateX(-Math.PI / 2);
  const waterMat = new THREE.MeshStandardMaterial({
    color: 0x0284c7,
    roughness: 0.15,
    metalness: 0.9,
    transparent: true,
    opacity: 0.8
  });
  waterMesh = new THREE.Mesh(waterGeo, waterMat);
  waterMesh.position.set(-28, 1.3, -4);
  scene.add(waterMesh);

  // 9. 3D Locales Beacons & Geodetic Rings (Anchored to Real Terrain Surface)
  beaconsGroup = new THREE.Group();
  minselLocales.forEach((loc) => {
    // Dynamically calculate accurate ground level from real photo elevation
    const groundY = getRealPhotoElevation(loc.pos.x, loc.pos.z);
    loc.pos.y = groundY;

    // Vertical Laser / Coordinate Line
    const lineMat = new THREE.LineBasicMaterial({ color: loc.color, transparent: true, opacity: 0.85 });
    const lineGeo = new THREE.BufferGeometry().setFromPoints([
      new THREE.Vector3(loc.pos.x, loc.pos.y, loc.pos.z),
      new THREE.Vector3(loc.pos.x, loc.pos.y + 7.0, loc.pos.z)
    ]);
    const line = new THREE.Line(lineGeo, lineMat);
    beaconsGroup?.add(line);

    // Glowing Sphere Top Beacon
    const beaconSphereGeo = new THREE.SphereGeometry(0.55, 16, 16);
    const beaconSphereMat = new THREE.MeshBasicMaterial({ color: loc.color });
    const beaconSphere = new THREE.Mesh(beaconSphereGeo, beaconSphereMat);
    beaconSphere.position.set(loc.pos.x, loc.pos.y + 7.0, loc.pos.z);
    beaconsGroup?.add(beaconSphere);

    // Horizontal Pulsing Ground Rings
    const ringGeo = new THREE.RingGeometry(0.9, 1.4, 32);
    ringGeo.rotateX(-Math.PI / 2);
    const ringMat = new THREE.MeshBasicMaterial({ color: loc.color, side: THREE.DoubleSide, transparent: true, opacity: 0.65 });
    const ring = new THREE.Mesh(ringGeo, ringMat);
    ring.position.set(loc.pos.x, loc.pos.y + 0.15, loc.pos.z);
    ring.userData = { initialScale: 1.0, speed: 1.5 + Math.random() };
    beaconsGroup?.add(ring);
  });
  scene.add(beaconsGroup);

  // 10. Geodetic Compass Reticle (Floating Grid)
  compassGroup = new THREE.Group();
  const outerRingGeo = new THREE.RingGeometry(20, 20.4, 64);
  outerRingGeo.rotateX(-Math.PI / 2);
  const outerRingMat = new THREE.MeshBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.35, side: THREE.DoubleSide });
  const outerRing = new THREE.Mesh(outerRingGeo, outerRingMat);
  outerRing.position.set(0, 1.0, 0);
  compassGroup.add(outerRing);

  // Crosshair Lines
  const crosshairGeo = new THREE.BufferGeometry().setFromPoints([
    new THREE.Vector3(-24, 1.0, 0),
    new THREE.Vector3(24, 1.0, 0),
    new THREE.Vector3(0, 1.0, -24),
    new THREE.Vector3(0, 1.0, 24)
  ]);
  const crosshairMat = new THREE.LineBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.25 });
  const crosshair = new THREE.LineSegments(crosshairGeo, crosshairMat);
  compassGroup.add(crosshair);
  scene.add(compassGroup);

  // 11. Central Approval Data Flow Arcs
  dataFlowGroup = new THREE.Group();
  minselLocales.slice(1).forEach((loc) => {
    const curve = new THREE.QuadraticBezierCurve3(
      new THREE.Vector3(loc.pos.x, loc.pos.y + 5, loc.pos.z),
      new THREE.Vector3((loc.pos.x + minselLocales[0].pos.x) / 2, 24, (loc.pos.z + minselLocales[0].pos.z) / 2),
      new THREE.Vector3(minselLocales[0].pos.x, minselLocales[0].pos.y + 5, minselLocales[0].pos.z)
    );
    const points = curve.getPoints(40);
    const arcGeo = new THREE.BufferGeometry().setFromPoints(points);
    const arcMat = new THREE.LineDashedMaterial({
      color: 0x38bdf8,
      dashSize: 1.2,
      gapSize: 0.8,
      transparent: true,
      opacity: 0.4
    });
    const arc = new THREE.Line(arcGeo, arcMat);
    arc.computeLineDistances();
    dataFlowGroup?.add(arc);
  });
  scene.add(dataFlowGroup);

  // 12. Timer & Animation Loop
  timer = new THREE.Timer();
  timer.connect(document);

  const animate = () => {
    if (!renderer || !scene || !camera || !timer) return;
    timer.update();
    const delta = timer.getDelta();
    const elapsed = timer.getElapsed();

    // Subtle Water Shimmer
    if (waterMesh) {
      waterMesh.position.y = 1.3 + Math.sin(elapsed * 1.5) * 0.12;
    }

    // Pulse Ground Rings
    if (beaconsGroup) {
      beaconsGroup.children.forEach((child) => {
        if (child instanceof THREE.Mesh && child.geometry instanceof THREE.RingGeometry) {
          const uData = child.userData as { speed?: number };
          const scale = 1.0 + Math.sin(elapsed * (uData.speed || 1.5)) * 0.25;
          child.scale.set(scale, scale, 1);
        }
      });
    }

    // Slow Compass Reticle Rotation
    if (compassGroup) {
      compassGroup.rotation.y = elapsed * 0.04;
    }

    // Smooth Mouse Parallax Interpolation
    targetMouseX.value += (mouseX.value - targetMouseX.value) * 0.05;
    targetMouseY.value += (mouseY.value - targetMouseY.value) * 0.05;

    // Smooth Camera Waypoint Interpolation based on Horizontal Scrollytelling
    updateCameraFromScroll(props.scrollProgress, delta);

    renderer.render(scene, camera);
    animationFrameId = requestAnimationFrame(animate);
  };

  animationFrameId = requestAnimationFrame(animate);
  window.addEventListener('resize', handleResize);
  window.addEventListener('mousemove', handleMouseMove);
}

function updateCameraFromScroll(progress: number, delta: number) {
  if (!camera) return;

  // Clamp progress to [0, 1]
  const p = Math.max(0, Math.min(1, progress));
  const numSegments = waypoints.length - 1;
  const scaledP = p * numSegments;
  const index = Math.min(Math.floor(scaledP), numSegments - 1);
  const t = scaledP - index;

  // Smooth Hermite / Smoothstep curve for graceful transitions
  const smoothT = t * t * (3 - 2 * t);

  const startWP = waypoints[index];
  const endWP = waypoints[index + 1] || startWP;

  // Target Camera Pos & LookAt
  const destCamPos = new THREE.Vector3().lerpVectors(startWP.pos, endWP.pos, smoothT);
  const destCamTarget = new THREE.Vector3().lerpVectors(startWP.target, endWP.target, smoothT);
  const destFov = THREE.MathUtils.lerp(startWP.fov, endWP.fov, smoothT);

  // Apply subtle mouse parallax
  destCamPos.x += targetMouseX.value * 3.5;
  destCamPos.y += -targetMouseY.value * 2.0;

  // Smooth lerp for buttery camera dampening
  const lerpFactor = Math.min(1, delta * 3.5);
  currentCamPos.lerp(destCamPos, lerpFactor);
  currentCamTarget.lerp(destCamTarget, lerpFactor);

  camera.position.copy(currentCamPos);
  camera.lookAt(currentCamTarget);
  camera.fov = destFov;
  camera.updateProjectionMatrix();

  // Dynamic Visibility of Data Arcs on Section 3 (Tata Kelola)
  if (dataFlowGroup) {
    const isGovernanceActive = progress >= 0.55 && progress <= 0.9;
    dataFlowGroup.visible = isGovernanceActive;
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

  // Dispose Three.js Resources
  if (renderer) {
    renderer.dispose();
    if (renderer.domElement && renderer.domElement.parentNode) {
      renderer.domElement.parentNode.removeChild(renderer.domElement);
    }
  }

  // Dispose Textures
  loadedTextures.forEach((tex) => tex.dispose());
  loadedTextures = [];

  terrainMesh?.geometry.dispose();
  (terrainMesh?.material as THREE.Material)?.dispose();
  wireframeMesh?.geometry.dispose();
  (wireframeMesh?.material as THREE.Material)?.dispose();
  waterMesh?.geometry.dispose();
  (waterMesh?.material as THREE.Material)?.dispose();
});
</script>

<template>
  <div ref="canvasContainer" class="threejs-terrain-canvas">
    <!-- Spatial Coordinates HUD Overlay -->
    <div class="hud-coordinates">
      <div class="hud-pill">
        <span class="hud-pulse"></span>
        <span class="hud-text">3D TOPOGRAFI PBR &bull; FOTO UDARA ASLI MINAHASA SELATAN</span>
      </div>
      <div class="hud-meta">
        <span>TELUK AMURANG (0m) &bull; MODOINDING (1.020m) &bull; G. SOPUTAN (1.784m)</span>
        <span class="hud-sep">|</span>
        <span>EPSG:4326</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.threejs-terrain-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
  overflow: hidden;
}

.threejs-terrain-canvas :deep(canvas) {
  display: block;
  width: 100% !important;
  height: 100% !important;
}

/* Minimal HUD Coordinate Display */
.hud-coordinates {
  position: absolute;
  bottom: 24px;
  right: 24px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 6px;
  z-index: 5;
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: 11px;
  color: #94a3b8;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
  pointer-events: none;
}

.hud-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(15, 23, 42, 0.75);
  border: 1px solid rgba(56, 189, 248, 0.25);
  backdrop-filter: blur(8px);
  padding: 4px 10px;
  border-radius: 9999px;
  color: #38bdf8;
  font-weight: 500;
  letter-spacing: 0.5px;
}

.hud-pulse {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: #38bdf8;
  box-shadow: 0 0 8px #38bdf8;
  animation: pulse 2s infinite ease-in-out;
}

@keyframes pulse {
  0%, 100% { opacity: 0.4; transform: scale(0.9); }
  50% { opacity: 1; transform: scale(1.3); }
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
  color: #334155;
}
</style>
