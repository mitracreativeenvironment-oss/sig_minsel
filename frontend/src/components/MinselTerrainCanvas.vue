<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
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

// Scene Meshes & Groups
let terrainMesh: THREE.Mesh | null = null;
let wireframeMesh: THREE.LineSegments | null = null;
let waterMesh: THREE.Mesh | null = null;
let beaconsGroup: THREE.Group | null = null;
let compassGroup: THREE.Group | null = null;
let dataFlowGroup: THREE.Group | null = null;

// Mouse Parallax
const mouseX = ref(0);
const mouseY = ref(0);
const targetMouseX = ref(0);
const targetMouseY = ref(0);

// Key Locales in Minahasa Selatan Coordinates (3D World Space)
const minselLocales = [
  { name: 'Amurang (Ibu Kota)', pos: new THREE.Vector3(-14, 2.5, -4), elev: '5m', color: 0x38bdf8, code: 'AMR' },
  { name: 'Gunung Soputan', pos: new THREE.Vector3(26, 18.5, 12), elev: '1,784m', color: 0xf59e0b, code: 'SPT' },
  { name: 'Modoinding (Hortikultura)', pos: new THREE.Vector3(14, 9.8, -22), elev: '1,020m', color: 0x10b981, code: 'MDD' },
  { name: 'Tumpaan (Gerbang Utara)', pos: new THREE.Vector3(-8, 3.2, 18), elev: '15m', color: 0x6366f1, code: 'TMP' },
  { name: 'Sinonsayang (Pesisir Barat)', pos: new THREE.Vector3(-24, 2.8, -26), elev: '8m', color: 0xec4899, code: 'SNS' },
  { name: 'Ranoyapo (Lembah Sungai)', pos: new THREE.Vector3(4, 5.5, -6), elev: '280m', color: 0x14b8a6, code: 'RNP' }
];

// Waypoint Camera Interpolation Keyframes for the 5 Sections
interface CameraWaypoint {
  pos: THREE.Vector3;
  target: THREE.Vector3;
  fov: number;
}

const waypoints: CameraWaypoint[] = [
  // 0: Hero - High-Altitude Orbital Panoramic Overview
  { pos: new THREE.Vector3(0, 52, 68), target: new THREE.Vector3(0, 4, -4), fov: 48 },
  // 1: 17 Kecamatan & Teluk Amurang - Low Oblique Coastal Flight
  { pos: new THREE.Vector3(-28, 16, 26), target: new THREE.Vector3(-10, 3, -6), fov: 52 },
  // 2: Dataran Tinggi Modoinding & 28 Kategori - Climbing towards Agricultural Crater
  { pos: new THREE.Vector3(22, 26, -6), target: new THREE.Vector3(14, 9, -22), fov: 50 },
  // 3: Tata Kelola 8 Role & Persetujuan Terpusat - Bird's-Eye Data Hub
  { pos: new THREE.Vector3(6, 42, 14), target: new THREE.Vector3(0, 2, 0), fov: 45 },
  // 4: Call to Action - Epic Horizon Sunrise towards Soputan Peak
  { pos: new THREE.Vector3(-18, 14, 48), target: new THREE.Vector3(24, 16, 10), fov: 48 }
];

const currentCamPos = new THREE.Vector3().copy(waypoints[0].pos);
const currentCamTarget = new THREE.Vector3().copy(waypoints[0].target);

// Procedural Height Calculation for Minahasa Selatan Topography
function getMinselElevation(x: number, z: number): number {
  // Teluk Amurang Sea Basin (West / North-West)
  const distAmurangBay = Math.hypot(x - (-22), z - 6);
  if (distAmurangBay < 20) {
    return Math.max(0, (distAmurangBay / 20) * 1.5 - 0.4);
  }

  // Gunung Soputan Volcanic Cone (East / South-East)
  const distSoputan = Math.hypot(x - 26, z - 12);
  const soputanHeight = Math.exp(-Math.pow(distSoputan / 11, 2)) * 19.5;

  // Dataran Tinggi Modoinding Basin & Danau Moat Ridge (South)
  const distModoinding = Math.hypot(x - 14, z - (-22));
  let modoindingHeight = 0;
  if (distModoinding < 18) {
    // Caldera ridge rim with plateau depression
    const rim = Math.sin((distModoinding / 18) * Math.PI) * 3.5;
    modoindingHeight = 8.5 + rim - (distModoinding < 8 ? 1.5 : 0);
  }

  // Rolling Coastal & Forested Hills (Tumpaan, Ranoyapo, Sinonsayang)
  const rolling =
    Math.sin(x * 0.08) * Math.cos(z * 0.08) * 3.2 +
    Math.sin(x * 0.16 + 1.2) * 1.8 +
    Math.cos(z * 0.14 - 0.8) * 1.5;

  const baseCoastElevation = Math.max(0.2, (x + 35) * 0.18);
  const total = baseCoastElevation + soputanHeight + modoindingHeight + rolling;
  return Math.max(0.1, total);
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
  renderer.toneMappingExposure = 1.2;
  canvasContainer.value.appendChild(renderer.domElement);

  // 2. Scene & Fog Setup (Deep Maritime & Volcanic Atmosphere)
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x070d1e); // Very deep navy
  scene.fog = new THREE.FogExp2(0x070d1e, 0.013);

  // 3. Camera Setup
  camera = new THREE.PerspectiveCamera(48, width / height, 0.5, 500);
  camera.position.copy(currentCamPos);
  camera.lookAt(currentCamTarget);

  // 4. Lighting Rig
  const ambientLight = new THREE.AmbientLight(0x1e293b, 1.8);
  scene.add(ambientLight);

  // Primary Sun / Geodetic Key Light (Golden-Amber Dawn)
  const sunLight = new THREE.DirectionalLight(0xfef08a, 2.8);
  sunLight.position.set(40, 50, 30);
  scene.add(sunLight);

  // Secondary Maritime Rim Light (Cyan / Ocean Reflection)
  const oceanRimLight = new THREE.DirectionalLight(0x0284c7, 2.2);
  oceanRimLight.position.set(-45, 20, -30);
  scene.add(oceanRimLight);

  // Tertiary Emerald Highland Accent Light
  const emeraldLight = new THREE.PointLight(0x10b981, 2.0, 80);
  emeraldLight.position.set(14, 25, -22);
  scene.add(emeraldLight);

  // 5. Procedural 3D Terrain Construction
  const gridSize = 140;
  const segments = 120;
  const terrainGeo = new THREE.PlaneGeometry(gridSize, gridSize, segments, segments);
  terrainGeo.rotateX(-Math.PI / 2);

  const posAttr = terrainGeo.attributes.position;
  const colors: number[] = [];
  const colorLow = new THREE.Color(0x0a192f); // Deep oceanic coast
  const colorMid = new THREE.Color(0x047857); // Lush highland forest
  const colorHigh = new THREE.Color(0xd97706); // Volcanic ridge / Soputan
  const colorPeak = new THREE.Color(0xfef3c7); // Soputan crest

  for (let i = 0; i < posAttr.count; i++) {
    const vx = posAttr.getX(i);
    const vz = posAttr.getZ(i);
    const vy = getMinselElevation(vx, vz);
    posAttr.setY(i, vy);

    // Vertex coloring based on elevation
    const c = new THREE.Color();
    if (vy < 2.5) {
      c.copy(colorLow).lerp(colorMid, vy / 2.5);
    } else if (vy < 10.0) {
      c.copy(colorMid).lerp(colorHigh, (vy - 2.5) / 7.5);
    } else {
      c.copy(colorHigh).lerp(colorPeak, Math.min(1, (vy - 10) / 9));
    }
    colors.push(c.r, c.g, c.b);
  }

  terrainGeo.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
  terrainGeo.computeVertexNormals();

  const terrainMat = new THREE.MeshStandardMaterial({
    vertexColors: true,
    roughness: 0.72,
    metalness: 0.15,
    flatShading: true
  });
  terrainMesh = new THREE.Mesh(terrainGeo, terrainMat);
  scene.add(terrainMesh);

  // 6. Topographic Wireframe / Contour Overlay
  const wireGeo = new THREE.WireframeGeometry(terrainGeo);
  const wireMat = new THREE.LineBasicMaterial({
    color: 0x38bdf8,
    transparent: true,
    opacity: 0.18
  });
  wireframeMesh = new THREE.LineSegments(wireGeo, wireMat);
  scene.add(wireframeMesh);

  // 7. Teluk Amurang Water Plane
  const waterGeo = new THREE.PlaneGeometry(50, 50, 16, 16);
  waterGeo.rotateX(-Math.PI / 2);
  const waterMat = new THREE.MeshStandardMaterial({
    color: 0x0369a1,
    roughness: 0.2,
    metalness: 0.85,
    transparent: true,
    opacity: 0.75
  });
  waterMesh = new THREE.Mesh(waterGeo, waterMat);
  waterMesh.position.set(-25, 0.4, 8);
  scene.add(waterMesh);

  // 8. 3D Locales Beacons & Geodetic Rings
  beaconsGroup = new THREE.Group();
  minselLocales.forEach((loc) => {
    // Vertical Laser / Coordinate Line
    const lineMat = new THREE.LineBasicMaterial({ color: loc.color, transparent: true, opacity: 0.8 });
    const lineGeo = new THREE.BufferGeometry().setFromPoints([
      new THREE.Vector3(loc.pos.x, loc.pos.y, loc.pos.z),
      new THREE.Vector3(loc.pos.x, loc.pos.y + 6.0, loc.pos.z)
    ]);
    const line = new THREE.Line(lineGeo, lineMat);
    beaconsGroup?.add(line);

    // Glowing Sphere Top Beacon
    const beaconSphereGeo = new THREE.SphereGeometry(0.5, 16, 16);
    const beaconSphereMat = new THREE.MeshBasicMaterial({ color: loc.color });
    const beaconSphere = new THREE.Mesh(beaconSphereGeo, beaconSphereMat);
    beaconSphere.position.set(loc.pos.x, loc.pos.y + 6.0, loc.pos.z);
    beaconsGroup?.add(beaconSphere);

    // Horizontal Pulsing Ground Rings
    const ringGeo = new THREE.RingGeometry(0.8, 1.2, 32);
    ringGeo.rotateX(-Math.PI / 2);
    const ringMat = new THREE.MeshBasicMaterial({ color: loc.color, side: THREE.DoubleSide, transparent: true, opacity: 0.6 });
    const ring = new THREE.Mesh(ringGeo, ringMat);
    ring.position.set(loc.pos.x, loc.pos.y + 0.1, loc.pos.z);
    ring.userData = { initialScale: 1.0, speed: 1.5 + Math.random() };
    beaconsGroup?.add(ring);
  });
  scene.add(beaconsGroup);

  // 9. Geodetic Compass Reticle (Floating Grid)
  compassGroup = new THREE.Group();
  const outerRingGeo = new THREE.RingGeometry(18, 18.3, 64);
  outerRingGeo.rotateX(-Math.PI / 2);
  const outerRingMat = new THREE.MeshBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.35, side: THREE.DoubleSide });
  const outerRing = new THREE.Mesh(outerRingGeo, outerRingMat);
  outerRing.position.set(0, 0.5, 0);
  compassGroup.add(outerRing);

  // Crosshair Lines
  const crosshairGeo = new THREE.BufferGeometry().setFromPoints([
    new THREE.Vector3(-22, 0.5, 0),
    new THREE.Vector3(22, 0.5, 0),
    new THREE.Vector3(0, 0.5, -22),
    new THREE.Vector3(0, 0.5, 22)
  ]);
  const crosshairMat = new THREE.LineBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.25 });
  const crosshair = new THREE.LineSegments(crosshairGeo, crosshairMat);
  compassGroup.add(crosshair);
  scene.add(compassGroup);

  // 10. Central Approval Data Flow Arcs (Visualizing Fase 2 Central Workflow)
  dataFlowGroup = new THREE.Group();
  minselLocales.slice(1).forEach((loc) => {
    const curve = new THREE.QuadraticBezierCurve3(
      new THREE.Vector3(loc.pos.x, loc.pos.y + 5, loc.pos.z),
      new THREE.Vector3((loc.pos.x + minselLocales[0].pos.x) / 2, 22, (loc.pos.z + minselLocales[0].pos.z) / 2),
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

  // 11. Timer & Animation Loop
  timer = new THREE.Timer();
  timer.connect(document);

  const animate = () => {
    if (!renderer || !scene || !camera || !timer) return;
    timer.update();
    const delta = timer.getDelta();
    const elapsed = timer.getElapsed();

    // Subtle Water Shimmer
    if (waterMesh) {
      waterMesh.position.y = 0.4 + Math.sin(elapsed * 1.5) * 0.08;
    }

    // Compass Rotation
    if (compassGroup) {
      compassGroup.rotation.y = elapsed * 0.04;
    }

    // Pulse Beacon Rings
    if (beaconsGroup) {
      beaconsGroup.children.forEach((child) => {
        if (child instanceof THREE.Mesh && child.geometry instanceof THREE.RingGeometry) {
          const speed = (child.userData.speed || 1.0) * 1.8;
          const scale = 1.0 + ((elapsed * speed) % 2.0);
          child.scale.set(scale, scale, scale);
          const mat = child.material as THREE.MeshBasicMaterial;
          mat.opacity = Math.max(0, 0.7 - (scale - 1.0) * 0.35);
        }
      });
    }

    // Interpolate Mouse Parallax
    targetMouseX.value += (mouseX.value - targetMouseX.value) * 0.05;
    targetMouseY.value += (mouseY.value - targetMouseY.value) * 0.05;

    // Calculate Interpolated Camera Target & Position based on scroll progress
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
        <span class="hud-text">SRID: EPSG:4326 (WGS84) &bull; KABUPATEN MINAHASA SELATAN</span>
      </div>
      <div class="hud-meta">
        <span>LAT: 1°11'14" N</span>
        <span class="hud-sep">|</span>
        <span>LONG: 124°43'47" E</span>
        <span class="hud-sep">|</span>
        <span>ELEV: 0 - 1,784m dpl</span>
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
