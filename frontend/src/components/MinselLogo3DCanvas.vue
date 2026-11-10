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

// Master Assembly Groups
let assemblyRoot: THREE.Group | null = null;
let shieldGroup: THREE.Group | null = null;
let leftWingMesh: THREE.Mesh | null = null;
let rightWingMesh: THREE.Mesh | null = null;
let crownTuftsGroup: THREE.Group | null = null;
let leftEyeGroup: THREE.Group | null = null;
let rightEyeGroup: THREE.Group | null = null;
let beakMesh: THREE.Mesh | null = null;
let bookGroup: THREE.Group | null = null;
let ribbonGroup: THREE.Group | null = null;
let compassHalo: THREE.Mesh | null = null;
let pulseRingMesh: THREE.Mesh | null = null;
let starFieldGroup: THREE.Points | null = null;

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

// Dynamic HUD Phase Narrative based on progress
const currentPhase = computed(() => {
  const p = props.scrollProgress;
  if (p < 0.2) {
    return {
      step: 'FASE 1 / 5',
      name: 'Falsafah Kedaulatan & Perisai Segilima',
      desc: 'Fondasi batas kedaulatan 17 Kecamatan & falsafah luhur Pancasila',
      color: '#38bdf8'
    };
  } else if (p < 0.45) {
    return {
      step: 'FASE 2 / 5',
      name: 'Sayap Manguni & Maritim Teluk Amurang',
      desc: 'Kearifan lokal tanah Minahasa & bentangan pesisir bahari',
      color: '#ef4444'
    };
  } else if (p < 0.68) {
    return {
      step: 'FASE 3 / 5',
      name: 'Mata Geodetik Waspada & Paruh Emas',
      desc: 'Ketajaman penglihatan data geospasial & 28 kategori potensi wilayah',
      color: '#f59e0b'
    };
  } else if (p < 0.88) {
    return {
      step: 'FASE 4 / 5',
      name: 'Buku Satu Data & Semboyan "CITA WAYA ESA"',
      desc: 'Kebijakan Satu Data Spasial & persatuan luhur masyarakat Minahasa Selatan',
      color: '#10b981'
    };
  } else {
    return {
      step: 'FASE 5 / 5',
      name: 'Lambang Utuh Kabupaten Minahasa Selatan',
      desc: 'Integrasi sempurna Satu Data Geospasial Kabupaten Minahasa Selatan',
      color: '#fde047'
    };
  }
});

// Materials Definition (PBR Heraldic Palette)
function createMaterials() {
  const goldMaterial = new THREE.MeshStandardMaterial({
    color: 0xf59e0b,
    roughness: 0.22,
    metalness: 0.88,
    envMapIntensity: 1.5
  });

  const goldRimMaterial = new THREE.MeshStandardMaterial({
    color: 0xfde047,
    roughness: 0.18,
    metalness: 0.92
  });

  const shieldTitaniumMaterial = new THREE.MeshPhysicalMaterial({
    color: 0x091224,
    roughness: 0.35,
    metalness: 0.85,
    clearcoat: 0.4,
    clearcoatRoughness: 0.2
  });

  const manguniRedMaterial = new THREE.MeshPhysicalMaterial({
    color: 0xdc2626,
    roughness: 0.28,
    metalness: 0.35,
    clearcoat: 0.85,
    clearcoatRoughness: 0.15
  });

  const geodeticCyanMaterial = new THREE.MeshStandardMaterial({
    color: 0x38bdf8,
    roughness: 0.2,
    metalness: 0.5,
    emissive: 0x0284c7,
    emissiveIntensity: 0.6
  });

  const obsidianEyeMaterial = new THREE.MeshStandardMaterial({
    color: 0x070b14,
    roughness: 0.1,
    metalness: 0.95
  });

  const bookWhiteMaterial = new THREE.MeshStandardMaterial({
    color: 0xf8fafc,
    roughness: 0.4,
    metalness: 0.15
  });

  return {
    goldMaterial,
    goldRimMaterial,
    shieldTitaniumMaterial,
    manguniRedMaterial,
    geodeticCyanMaterial,
    obsidianEyeMaterial,
    bookWhiteMaterial
  };
}

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
  renderer.toneMappingExposure = 1.35;
  canvasContainer.value.appendChild(renderer.domElement);

  // 2. Scene & Fog Setup
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x060c1d);
  scene.fog = new THREE.FogExp2(0x060c1d, 0.015);

  // 3. Perspective Camera
  camera = new THREE.PerspectiveCamera(46, width / height, 0.5, 400);
  camera.position.set(0, 0, 36);
  camera.lookAt(0, 0, 0);

  // 4. Lighting Rig
  const ambientLight = new THREE.AmbientLight(0x1e293b, 2.0);
  scene.add(ambientLight);

  // Key Sun Light (Warm Gold highlights on bevels)
  const sunLight = new THREE.DirectionalLight(0xfef08a, 3.4);
  sunLight.position.set(30, 40, 35);
  scene.add(sunLight);

  // Secondary Maritime Rim Light (Cyan reflection)
  const rimLight = new THREE.DirectionalLight(0x38bdf8, 2.6);
  rimLight.position.set(-35, 20, -20);
  scene.add(rimLight);

  // Tertiary Fill Light from below
  const fillLight = new THREE.DirectionalLight(0x0284c7, 1.6);
  fillLight.position.set(0, -30, 20);
  scene.add(fillLight);

  // 5. Background Ambient Starfield Particles
  const starGeo = new THREE.BufferGeometry();
  const starCount = 180;
  const starPositions = new Float32Array(starCount * 3);
  for (let i = 0; i < starCount * 3; i += 3) {
    starPositions[i] = (Math.random() - 0.5) * 160;
    starPositions[i + 1] = (Math.random() - 0.5) * 120;
    starPositions[i + 2] = -30 - Math.random() * 80;
  }
  starGeo.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
  const starMat = new THREE.PointsMaterial({
    color: 0x38bdf8,
    size: 1.2,
    transparent: true,
    opacity: 0.45
  });
  starFieldGroup = new THREE.Points(starGeo, starMat);
  scene.add(starFieldGroup);

  // 6. Master Assembly Root
  assemblyRoot = new THREE.Group();
  scene.add(assemblyRoot);

  const mats = createMaterials();

  // ==========================================
  // COMPONENT 1: SHIELD BASE & GOLDEN CONTOUR
  // ==========================================
  shieldGroup = new THREE.Group();

  // Outer Pentagonal Shield Contour
  const shieldShape = new THREE.Shape();
  shieldShape.moveTo(0, 9.5);
  shieldShape.bezierCurveTo(4.5, 9.5, 7.5, 8.5, 8.2, 5.0);
  shieldShape.bezierCurveTo(8.8, 0.5, 8.2, -4.5, 5.5, -8.2);
  shieldShape.bezierCurveTo(3.2, -10.8, 1.2, -12.5, 0, -13.5);
  shieldShape.bezierCurveTo(-1.2, -12.5, -3.2, -10.8, -5.5, -8.2);
  shieldShape.bezierCurveTo(-8.2, -4.5, -8.8, 0.5, -8.2, 5.0);
  shieldShape.bezierCurveTo(-7.5, 8.5, -4.5, 9.5, 0, 9.5);

  const shieldExtrudeSettings: THREE.ExtrudeGeometryOptions = {
    depth: 1.6,
    bevelEnabled: true,
    bevelSegments: 4,
    steps: 1,
    bevelSize: 0.45,
    bevelThickness: 0.4
  };

  const shieldGeo = new THREE.ExtrudeGeometry(shieldShape, shieldExtrudeSettings);
  shieldGeo.center();
  const shieldPlate = new THREE.Mesh(shieldGeo, mats.shieldTitaniumMaterial);
  shieldGroup.add(shieldPlate);

  // Outer Gold Border Rim
  const rimGeo = new THREE.BufferGeometry().setFromPoints(shieldShape.getPoints(60));
  const rimLineMat = new THREE.LineBasicMaterial({ color: 0xf59e0b, linewidth: 3 });
  const rimLine = new THREE.LineLoop(rimGeo, rimLineMat);
  rimLine.position.z = 1.05;
  shieldGroup.add(rimLine);

  // Floating Compass Reticle Ring (Geodetic Halo behind shield)
  const haloGeo = new THREE.RingGeometry(13.8, 14.1, 64);
  const haloMat = new THREE.MeshBasicMaterial({ color: 0x38bdf8, transparent: true, opacity: 0.28, side: THREE.DoubleSide });
  compassHalo = new THREE.Mesh(haloGeo, haloMat);
  compassHalo.position.z = -1.2;
  shieldGroup.add(compassHalo);

  assemblyRoot.add(shieldGroup);

  // ==========================================
  // COMPONENT 2: SAYAP MANGUNI (LEFT & RIGHT WINGS)
  // ==========================================
  // Left Wing Shape
  const leftWingShape = new THREE.Shape();
  leftWingShape.moveTo(-1.2, 7.2);
  leftWingShape.bezierCurveTo(-4.2, 7.0, -7.5, 5.8, -8.5, 3.2);
  leftWingShape.lineTo(-7.8, 1.2);
  leftWingShape.lineTo(-7.0, 1.5);
  leftWingShape.lineTo(-6.8, -1.2);
  leftWingShape.lineTo(-5.8, -0.8);
  leftWingShape.lineTo(-5.2, -3.2);
  leftWingShape.bezierCurveTo(-3.5, -1.5, -1.5, 1.5, -1.2, 4.5);
  leftWingShape.closePath();

  const wingExtrudeSettings: THREE.ExtrudeGeometryOptions = {
    depth: 1.2,
    bevelEnabled: true,
    bevelSegments: 3,
    steps: 1,
    bevelSize: 0.28,
    bevelThickness: 0.28
  };

  const leftWingGeo = new THREE.ExtrudeGeometry(leftWingShape, wingExtrudeSettings);
  leftWingGeo.center();
  leftWingMesh = new THREE.Mesh(leftWingGeo, mats.manguniRedMaterial);
  assemblyRoot.add(leftWingMesh);

  // Right Wing Shape (Symmetric Mirror)
  const rightWingShape = new THREE.Shape();
  rightWingShape.moveTo(1.2, 7.2);
  rightWingShape.bezierCurveTo(4.2, 7.0, 7.5, 5.8, 8.5, 3.2);
  rightWingShape.lineTo(7.8, 1.2);
  rightWingShape.lineTo(7.0, 1.5);
  rightWingShape.lineTo(6.8, -1.2);
  rightWingShape.lineTo(5.8, -0.8);
  rightWingShape.lineTo(5.2, -3.2);
  rightWingShape.bezierCurveTo(3.5, -1.5, 1.5, 1.5, 1.2, 4.5);
  rightWingShape.closePath();

  const rightWingGeo = new THREE.ExtrudeGeometry(rightWingShape, wingExtrudeSettings);
  rightWingGeo.center();
  rightWingMesh = new THREE.Mesh(rightWingGeo, mats.manguniRedMaterial);
  assemblyRoot.add(rightWingMesh);

  // ==========================================
  // COMPONENT 3: CROWN TUFTS (TELINGA TEGAK MANGUNI)
  // ==========================================
  crownTuftsGroup = new THREE.Group();

  const tuftShape = new THREE.Shape();
  tuftShape.moveTo(0, 0);
  tuftShape.lineTo(1.8, 3.8);
  tuftShape.lineTo(0.6, 3.4);
  tuftShape.lineTo(0.2, 4.5);
  tuftShape.lineTo(-0.8, 2.2);
  tuftShape.closePath();

  const tuftGeo = new THREE.ExtrudeGeometry(tuftShape, { depth: 0.7, bevelEnabled: true, bevelThickness: 0.2, bevelSize: 0.15 });
  tuftGeo.center();

  const leftTuft = new THREE.Mesh(tuftGeo, mats.goldMaterial);
  leftTuft.position.set(-4.2, 8.8, 1.1);
  leftTuft.rotation.z = 0.2;
  crownTuftsGroup.add(leftTuft);

  const rightTuft = new THREE.Mesh(tuftGeo, mats.goldMaterial);
  rightTuft.position.set(4.2, 8.8, 1.1);
  rightTuft.rotation.z = -0.2;
  rightTuft.scale.x = -1;
  crownTuftsGroup.add(rightTuft);

  assemblyRoot.add(crownTuftsGroup);

  // ==========================================
  // COMPONENT 4: MATA GEODETIK WASPADA (EYES & RETICLE)
  // ==========================================
  function createGeodeticEye(): THREE.Group {
    const eye = new THREE.Group();

    // 1. Outer Golden Torus Ring
    const goldTorusGeo = new THREE.TorusGeometry(1.65, 0.22, 16, 32);
    const goldTorus = new THREE.Mesh(goldTorusGeo, mats.goldMaterial);
    eye.add(goldTorus);

    // 2. Glowing Cyan Coordinate Reticle Ring
    const cyanRingGeo = new THREE.TorusGeometry(1.15, 0.15, 16, 32);
    const cyanRing = new THREE.Mesh(cyanRingGeo, mats.geodeticCyanMaterial);
    eye.add(cyanRing);

    // 3. Dark Pupil Cylinder
    const pupilGeo = new THREE.CylinderGeometry(0.7, 0.7, 0.45, 24);
    pupilGeo.rotateX(Math.PI / 2);
    const pupil = new THREE.Mesh(pupilGeo, mats.obsidianEyeMaterial);
    eye.add(pupil);

    // 4. White Highlight Sparkle
    const glintGeo = new THREE.SphereGeometry(0.18, 12, 12);
    const glintMat = new THREE.MeshBasicMaterial({ color: 0xffffff });
    const glint = new THREE.Mesh(glintGeo, glintMat);
    glint.position.set(0.3, 0.3, 0.35);
    eye.add(glint);

    // 5. Geodetic Crosshair Ticks (North, South, East, West)
    const tickMat = new THREE.LineBasicMaterial({ color: 0xfde047 });
    const tickGeo = new THREE.BufferGeometry().setFromPoints([
      new THREE.Vector3(-1.8, 0, 0.1), new THREE.Vector3(-1.3, 0, 0.1),
      new THREE.Vector3(1.3, 0, 0.1), new THREE.Vector3(1.8, 0, 0.1),
      new THREE.Vector3(0, -1.8, 0.1), new THREE.Vector3(0, -1.3, 0.1),
      new THREE.Vector3(0, 1.3, 0.1), new THREE.Vector3(0, 1.8, 0.1)
    ]);
    const ticks = new THREE.LineSegments(tickGeo, tickMat);
    eye.add(ticks);

    return eye;
  }

  leftEyeGroup = createGeodeticEye();
  assemblyRoot.add(leftEyeGroup);

  rightEyeGroup = createGeodeticEye();
  assemblyRoot.add(rightEyeGroup);

  // ==========================================
  // COMPONENT 5: PARUH EMAS (GOLDEN BEAK & COMPASS POINTER)
  // ==========================================
  const beakShape = new THREE.Shape();
  beakShape.moveTo(0, 1.2);
  beakShape.lineTo(1.3, -0.4);
  beakShape.lineTo(0, -2.4);
  beakShape.lineTo(-1.3, -0.4);
  beakShape.closePath();

  const beakExtrudeSettings: THREE.ExtrudeGeometryOptions = {
    depth: 1.1,
    bevelEnabled: true,
    bevelSegments: 3,
    bevelThickness: 0.35,
    bevelSize: 0.25
  };
  const beakGeo = new THREE.ExtrudeGeometry(beakShape, beakExtrudeSettings);
  beakGeo.center();
  beakMesh = new THREE.Mesh(beakGeo, mats.goldMaterial);
  assemblyRoot.add(beakMesh);

  // ==========================================
  // COMPONENT 6: BUKU SATU DATA SPASIAL DI DADA
  // ==========================================
  bookGroup = new THREE.Group();

  const bookShape = new THREE.Shape();
  // Open book dual-arc curve
  bookShape.moveTo(-3.2, 0.8);
  bookShape.bezierCurveTo(-1.8, 1.3, -0.4, 0.8, 0, 0.5);
  bookShape.bezierCurveTo(0.4, 0.8, 1.8, 1.3, 3.2, 0.8);
  bookShape.lineTo(3.0, -1.4);
  bookShape.bezierCurveTo(1.6, -0.9, 0.4, -1.3, 0, -1.5);
  bookShape.bezierCurveTo(-0.4, -1.3, -1.6, -0.9, -3.0, -1.4);
  bookShape.closePath();

  const bookGeo = new THREE.ExtrudeGeometry(bookShape, { depth: 0.8, bevelEnabled: true, bevelThickness: 0.2, bevelSize: 0.15 });
  bookGeo.center();
  const bookMesh = new THREE.Mesh(bookGeo, mats.bookWhiteMaterial);
  bookGroup.add(bookMesh);

  // Book Spine & Coordinates Lines
  const spineGeo = new THREE.CylinderGeometry(0.12, 0.12, 2.2, 16);
  const spineMesh = new THREE.Mesh(spineGeo, mats.goldMaterial);
  spineMesh.position.set(0, 0, 0.5);
  bookGroup.add(spineMesh);

  assemblyRoot.add(bookGroup);

  // ==========================================
  // COMPONENT 7: PITA SEMBOYAN "CITA WAYA ESA" & PIN BEACON
  // ==========================================
  ribbonGroup = new THREE.Group();

  const ribbonShape = new THREE.Shape();
  ribbonShape.moveTo(-5.5, 0.8);
  ribbonShape.bezierCurveTo(-2.8, -0.6, 2.8, -0.6, 5.5, 0.8);
  ribbonShape.lineTo(6.2, -0.7);
  ribbonShape.bezierCurveTo(3.2, -1.9, -3.2, -1.9, -6.2, -0.7);
  ribbonShape.closePath();

  const ribbonGeo = new THREE.ExtrudeGeometry(ribbonShape, { depth: 0.9, bevelEnabled: true, bevelThickness: 0.2, bevelSize: 0.15 });
  ribbonGeo.center();
  const ribbonMesh = new THREE.Mesh(ribbonGeo, mats.goldMaterial);
  ribbonGroup.add(ribbonMesh);

  // Bottom Geodetic Pin Glowing Sphere
  const pinGeo = new THREE.SphereGeometry(0.7, 24, 24);
  const pinMesh = new THREE.Mesh(pinGeo, mats.geodeticCyanMaterial);
  pinMesh.position.set(0, -4.5, 0.6);
  ribbonGroup.add(pinMesh);

  assemblyRoot.add(ribbonGroup);

  // ==========================================
  // COMPONENT 8: GOLDEN-CYAN ENERGY PULSE RING
  // ==========================================
  const pulseGeo = new THREE.RingGeometry(1.0, 1.4, 64);
  const pulseMat = new THREE.MeshBasicMaterial({
    color: 0xfde047,
    transparent: true,
    opacity: 0.0,
    side: THREE.DoubleSide
  });
  pulseRingMesh = new THREE.Mesh(pulseGeo, pulseMat);
  pulseRingMesh.position.z = 2.0;
  assemblyRoot.add(pulseRingMesh);

  // ==========================================
  // ANIMATION LOOP & KINETIC CHOREOGRAPHY
  // ==========================================
  timer = new THREE.Timer();
  timer.connect(document);

  const animate = () => {
    if (!renderer || !scene || !camera || !timer) return;
    timer.update();
    const delta = timer.getDelta();
    const elapsed = timer.getElapsed();

    // Rotate ambient starfield
    if (starFieldGroup) {
      starFieldGroup.rotation.y = elapsed * 0.02;
    }

    // Smooth Mouse Parallax
    mouseX.value += (targetMouseX.value - mouseX.value) * 0.05;
    mouseY.value += (targetMouseY.value - mouseY.value) * 0.05;

    // Apply Assembly Kinetics based on props.scrollProgress
    updateAssemblyKinetics(props.scrollProgress, delta, elapsed);

    renderer.render(scene, camera);
    animationFrameId = requestAnimationFrame(animate);
  };

  animationFrameId = requestAnimationFrame(animate);
  window.addEventListener('resize', handleResize);
  window.addEventListener('mousemove', handleMouseMove);
}

// Update the position, rotation, and scale of every heraldic part based on scrollProgress
function updateAssemblyKinetics(progress: number, _delta: number, elapsed: number) {
  if (!assemblyRoot) return;

  const p = Math.max(0, Math.min(1, progress));

  // --- STAGE 0: SHIELD BASE (0.00 -> 0.22) ---
  const tShield = smoothStep(0.0, 0.22, p);
  if (shieldGroup) {
    shieldGroup.position.set(
      0,
      (1 - tShield) * 8.0,
      (1 - tShield) * -38.0
    );
    shieldGroup.rotation.set(
      (1 - tShield) * 0.35,
      (1 - tShield) * 0.55,
      (1 - tShield) * -0.15
    );
    shieldGroup.scale.setScalar(0.4 + tShield * 0.6);
  }

  // --- STAGE 1: WINGS & CROWN TUFTS (0.20 -> 0.45) ---
  const tWings = smoothStep(0.20, 0.45, p);
  if (leftWingMesh) {
    // Docked at (-4.2, 2.5, 0.8)
    leftWingMesh.position.set(
      -4.2 - (1 - tWings) * 28.0,
      2.5 + (1 - tWings) * 14.0,
      0.8 + (1 - tWings) * 20.0
    );
    leftWingMesh.rotation.set(
      (1 - tWings) * 0.4,
      -(1 - tWings) * 0.8,
      -(1 - tWings) * 0.7
    );
    leftWingMesh.scale.setScalar(0.2 + tWings * 0.8);
  }

  if (rightWingMesh) {
    // Docked at (4.2, 2.5, 0.8)
    rightWingMesh.position.set(
      4.2 + (1 - tWings) * 28.0,
      2.5 + (1 - tWings) * 14.0,
      0.8 + (1 - tWings) * 20.0
    );
    rightWingMesh.rotation.set(
      (1 - tWings) * 0.4,
      (1 - tWings) * 0.8,
      (1 - tWings) * 0.7
    );
    rightWingMesh.scale.setScalar(0.2 + tWings * 0.8);
  }

  if (crownTuftsGroup) {
    crownTuftsGroup.position.set(
      0,
      (1 - tWings) * 26.0,
      (1 - tWings) * 16.0
    );
    crownTuftsGroup.rotation.x = (1 - tWings) * 0.8;
    crownTuftsGroup.scale.setScalar(0.2 + tWings * 0.8);
  }

  // --- STAGE 2: GEODETIC EYES & GOLDEN BEAK (0.42 -> 0.68) ---
  const tEyes = smoothStep(0.42, 0.68, p);
  if (leftEyeGroup) {
    // Docked at (-3.4, 2.4, 1.4)
    leftEyeGroup.position.set(
      -3.4 - (1 - tEyes) * 18.0,
      2.4 + (1 - tEyes) * 6.0,
      1.4 + (1 - tEyes) * 26.0
    );
    leftEyeGroup.rotation.z = (1 - tEyes) * 4.0;
    leftEyeGroup.scale.setScalar(0.1 + tEyes * 0.9);
  }

  if (rightEyeGroup) {
    // Docked at (3.4, 2.4, 1.4)
    rightEyeGroup.position.set(
      3.4 + (1 - tEyes) * 18.0,
      2.4 + (1 - tEyes) * 6.0,
      1.4 + (1 - tEyes) * 26.0
    );
    rightEyeGroup.rotation.z = -(1 - tEyes) * 4.0;
    rightEyeGroup.scale.setScalar(0.1 + tEyes * 0.9);
  }

  if (beakMesh) {
    // Docked at (0, 0.2, 1.8)
    beakMesh.position.set(
      0,
      0.2 + (1 - tEyes) * 18.0,
      1.8 + (1 - tEyes) * 22.0
    );
    beakMesh.rotation.x = (1 - tEyes) * 1.2;
    beakMesh.scale.setScalar(0.1 + tEyes * 0.9);
  }

  // --- STAGE 3: BUKU SATU DATA & PITA CITA WAYA ESA (0.65 -> 0.88) ---
  const tBook = smoothStep(0.65, 0.88, p);
  if (bookGroup) {
    // Docked at (0, -3.2, 1.4)
    bookGroup.position.set(
      0,
      -3.2 - (1 - tBook) * 22.0,
      1.4 + (1 - tBook) * 18.0
    );
    bookGroup.rotation.x = -(1 - tBook) * 1.0;
    bookGroup.scale.setScalar(0.2 + tBook * 0.8);
  }

  if (ribbonGroup) {
    // Docked at (0, -6.8, 1.5)
    ribbonGroup.position.set(
      0,
      -6.8 - (1 - tBook) * 26.0,
      1.5 + (1 - tBook) * 15.0
    );
    ribbonGroup.rotation.x = -(1 - tBook) * 0.8;
    ribbonGroup.scale.setScalar(0.2 + tBook * 0.8);
  }

  // --- STAGE 4: HERO SNAP & ENERGY FLARE (0.85 -> 1.00) ---
  const tSnap = smoothStep(0.85, 1.0, p);

  // Pulse Ring Effect
  if (pulseRingMesh) {
    if (tSnap > 0.05) {
      const pulseCycle = (elapsed * 1.4) % 1.0;
      const pulseScale = 8.0 + pulseCycle * 16.0;
      pulseRingMesh.scale.set(pulseScale, pulseScale, 1);
      (pulseRingMesh.material as THREE.MeshBasicMaterial).opacity = Math.max(0, (1.0 - pulseCycle) * 0.55 * tSnap);
      pulseRingMesh.visible = true;
    } else {
      pulseRingMesh.visible = false;
    }
  }

  // Floating Idle Levitation & Interactive Mouse Tilt Parallax
  const idleY = Math.sin(elapsed * 1.6) * 0.35 * (0.3 + tSnap * 0.7);
  const idleRoll = Math.cos(elapsed * 1.1) * 0.025 * (0.3 + tSnap * 0.7);

  // Mouse Parallax Influence
  const parallaxX = mouseX.value * 0.35;
  const parallaxY = -mouseY.value * 0.25;

  assemblyRoot.position.y = idleY;
  assemblyRoot.rotation.x = parallaxY + (1 - tSnap) * 0.05;
  assemblyRoot.rotation.y = parallaxX + idleRoll + (1 - tSnap) * 0.1;

  // Slowly rotate background compass halo
  if (compassHalo) {
    compassHalo.rotation.z = elapsed * 0.08;
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
        <span>3D HERALDIC EMBLEM</span>
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
