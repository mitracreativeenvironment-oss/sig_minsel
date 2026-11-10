<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import * as THREE from 'three';
import { Volume2, VolumeX, Sparkles } from 'lucide-vue-next';
import { createEnvironment, type Story01Environment } from './landing/stories/Story01Manguni/scene/createEnvironment';
import { createLighting, type Story01Lighting } from './landing/stories/Story01Manguni/scene/createLighting';
import { createOwlCharacter, type Story01OwlCharacter } from './landing/stories/Story01Manguni/scene/createOwlCharacter';
import { getStory01CurrentShot } from './landing/stories/Story01Manguni/animation/createStoryTimeline';

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

// Audio State (User-Controlled Ambience Engine)
const isAudioEnabled = ref(false);
let audioContext: AudioContext | null = null;
let nightNoiseNode: AudioNode | null = null;

// Three.js Core Objects
let renderer: THREE.WebGLRenderer | null = null;
let scene: THREE.Scene | null = null;
let camera: THREE.PerspectiveCamera | null = null;
let timer: THREE.Timer | null = null;
let animationFrameId: number | null = null;

// Story 01 Subsystems (Realistic Celepuk Sulawesi & Wilderness)
let envSystem: Story01Environment | null = null;
let lightSystem: Story01Lighting | null = null;
let owlCharacter: Story01OwlCharacter | null = null;

// Assembly 3D Mesh Groups (Stories 02 - 05 Components)
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

// Relative progress within Story 01 (0.0 to 1.0 when global progress is 0.00 to 0.20)
const story01Progress = computed(() => {
  const p = Math.max(0, Math.min(1, (props.scrollProgress || 0) / 0.20));
  return p;
});

const currentShot = computed(() => {
  return getStory01CurrentShot(story01Progress.value);
});

// Whether Story 01 is currently active (Section 0, progress <= 0.20)
const isStory01Active = computed(() => {
  return (props.scrollProgress || 0) <= 0.20;
});

// Dynamic HUD Phase Narrative based on authentic heraldry (for Stories 02 to 05)
const currentPhase = computed(() => {
  const p = props.scrollProgress || 0;
  if (p < 0.22) {
    return {
      step: 'STORY 01',
      name: 'Kebangkitan Burung Manguni',
      desc: 'Perjalanan sinematik dari langit malam belantara Minahasa menuju fajar keemasan',
      color: '#facc15'
    };
  } else if (p < 0.45) {
    return {
      step: 'STORY 02',
      name: 'Kekayaan Alam: Pohon Kelapa & Cengkih',
      desc: 'Komoditas perkebunan Minahasa Selatan terbentuk di samping jalur penerbangan Manguni',
      color: '#10b981'
    };
  } else if (p < 0.68) {
    return {
      step: 'STORY 03',
      name: 'Ilmu Pengetahuan & Potensi Maritim (Buku & Jangkar)',
      desc: 'Buku Satu Data Spasial & potensi maritim bahari Teluk Amurang',
      color: '#38bdf8'
    };
  } else if (p < 0.88) {
    return {
      step: 'STORY 04',
      name: 'Perisai & Pita "CITA WAYA ESA"',
      desc: 'Struktur perisai kedaulatan & pita semboyan persatuan "Kita Semua Satu"',
      color: '#ef4444'
    };
  } else {
    return {
      step: 'STORY 05',
      name: 'Lambang Utuh & Interactive 3D Mouse Parallax',
      desc: 'Penyatuan paripurna Satu Data Geospasial Kabupaten Minahasa Selatan',
      color: '#fde047'
    };
  }
});

// Audio Synthesizer Toggle
function toggleAudio() {
  isAudioEnabled.value = !isAudioEnabled.value;
  if (isAudioEnabled.value) {
    startNatureAudio();
  } else {
    stopNatureAudio();
  }
}

function startNatureAudio() {
  try {
    const AudioCtx = window.AudioContext || (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext;
    if (!audioContext) {
      audioContext = new AudioCtx();
    }
    if (audioContext.state === 'suspended') {
      audioContext.resume();
    }

    // Natural night forest wind & crickets ambience
    const bufferSize = audioContext.sampleRate * 2;
    const noiseBuffer = audioContext.createBuffer(1, bufferSize, audioContext.sampleRate);
    const output = noiseBuffer.getChannelData(0);
    let b0 = 0, b1 = 0, b2 = 0;
    for (let i = 0; i < bufferSize; i++) {
      const white = Math.random() * 2 - 1;
      b0 = 0.99886 * b0 + white * 0.0555179;
      b1 = 0.99332 * b1 + white * 0.0750759;
      b2 = 0.96900 * b2 + white * 0.1538520;
      output[i] = (b0 + b1 + b2) * 0.04;
    }

    const whiteNoise = audioContext.createBufferSource();
    whiteNoise.buffer = noiseBuffer;
    whiteNoise.loop = true;

    const filter = audioContext.createBiquadFilter();
    filter.type = 'bandpass';
    filter.frequency.value = 3200;
    filter.Q.value = 2.5;

    const gainNode = audioContext.createGain();
    gainNode.gain.setValueAtTime(0.12, audioContext.currentTime);

    whiteNoise.connect(filter);
    filter.connect(gainNode);
    gainNode.connect(audioContext.destination);

    whiteNoise.start();
    nightNoiseNode = whiteNoise;
  } catch (err) {
    console.warn('AudioContext not supported or blocked:', err);
  }
}

function stopNatureAudio() {
  if (nightNoiseNode && 'stop' in nightNoiseNode) {
    try {
      (nightNoiseNode as AudioBufferSourceNode).stop();
    } catch {}
    nightNoiseNode = null;
  }
}

function adjustCameraForAspect(width: number, height: number) {
  if (!camera) return;
  const aspect = width / height;
  camera.aspect = aspect;
  if (aspect < 0.75) {
    // Mobile Portrait
    camera.fov = 58;
  } else if (aspect < 1.15) {
    // Tablet / Square
    camera.fov = 50;
  } else {
    // Desktop Landscape
    camera.fov = 46;
  }
  camera.updateProjectionMatrix();
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

  // 2. Persistent Scene & Fog Setup
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x030712); // Deep nocturnal sky
  scene.fog = new THREE.FogExp2(0x030712, 0.016);

  // 3. Perspective Camera
  camera = new THREE.PerspectiveCamera(46, width / height, 0.5, 400);
  adjustCameraForAspect(width, height);
  camera.position.set(0, 0, 38);
  camera.lookAt(0, 0, 0);

  // 4. Lighting Rig
  lightSystem = createLighting(scene);

  // 5. Wilderness & Celestial Environment (Story 01 Shot 01-02)
  envSystem = createEnvironment();
  scene.add(envSystem.group);

  // 6. Owl Character System (Celepuk Sulawesi & Authentic Manguni Emblem)
  owlCharacter = createOwlCharacter();
  scene.add(owlCharacter.rootGroup);

  // 7. Master Assembly Root for Subsequent Stories (Shield, Ribbon, Book, Halo)
  assemblyRoot = new THREE.Group();
  scene.add(assemblyRoot);

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

  const planeW = 20.0;
  const planeH = planeW * (1227 / 929);
  const layerGeo = new THREE.PlaneGeometry(planeW, planeH);

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

  shieldMesh = new THREE.Mesh(layerGeo, createLayerMaterial(texShield));
  shieldMesh.renderOrder = 1;
  assemblyRoot.add(shieldMesh);

  wingLeftMesh = new THREE.Mesh(layerGeo, createLayerMaterial(texWingLeft));
  wingLeftMesh.renderOrder = 2;
  assemblyRoot.add(wingLeftMesh);

  wingRightMesh = new THREE.Mesh(layerGeo, createLayerMaterial(texWingRight));
  wingRightMesh.renderOrder = 3;
  assemblyRoot.add(wingRightMesh);

  bodyHeadMesh = new THREE.Mesh(layerGeo, createLayerMaterial(texBodyHead));
  bodyHeadMesh.renderOrder = 4;
  assemblyRoot.add(bodyHeadMesh);

  chestMesh = new THREE.Mesh(layerGeo, createLayerMaterial(texChest));
  chestMesh.renderOrder = 5;
  assemblyRoot.add(chestMesh);

  ribbonMesh = new THREE.Mesh(layerGeo, createLayerMaterial(texRibbon));
  ribbonMesh.renderOrder = 6;
  assemblyRoot.add(ribbonMesh);

  completeEmblemMesh = new THREE.Mesh(layerGeo, createLayerMaterial(texComplete));
  completeEmblemMesh.renderOrder = 7;
  completeEmblemMesh.visible = false;
  assemblyRoot.add(completeEmblemMesh);

  // Floating Compass Halo
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

  // 8. Animation Loop
  timer = new THREE.Timer();
  timer.connect(document);

  const animate = () => {
    if (!renderer || !scene || !camera || !timer) return;
    timer.update();
    const delta = timer.getDelta();
    const elapsed = timer.getElapsed();

    // Mouse Parallax Lerp
    mouseX.value += (targetMouseX.value - mouseX.value) * 0.05;
    mouseY.value += (targetMouseY.value - mouseY.value) * 0.05;

    // Orchestrate Story 01 vs Subsequent Stories
    updateStoryScenes(props.scrollProgress || 0, delta, elapsed, width, height);

    renderer.render(scene, camera);
    animationFrameId = requestAnimationFrame(animate);
  };

  animationFrameId = requestAnimationFrame(animate);
  window.addEventListener('resize', handleResize);
  window.addEventListener('mousemove', handleMouseMove);
}

// Orchestrator for Story 01 (Shot 01 to 07) through Story 05
function updateStoryScenes(globalProgress: number, _delta: number, elapsed: number, _width: number, _height: number) {
  if (!scene || !camera) return;

  const t01 = story01Progress.value; // 0.0 to 1.0 within Story 01

  // =========================================================================
  // STORY 01 ACTIVE (Global progress <= 0.20): Kebangkitan Burung Manguni
  // =========================================================================
  if (globalProgress <= 0.20) {
    // 1. Enable wilderness environment & owl character
    if (envSystem) {
      envSystem.group.visible = true;
      envSystem.update(t01, elapsed);
    }
    if (lightSystem) {
      lightSystem.update(t01);
    }
    if (owlCharacter) {
      owlCharacter.rootGroup.visible = true;
      owlCharacter.update(t01, elapsed);
    }

    // 2. Hide other emblem components (shield, ribbon, book) during Story 01 Shot 01-05
    if (assemblyRoot) {
      assemblyRoot.visible = false;
    }

    // 3. Story 01 Camera Rig (Shot 01 to 07)
    updateStory01Camera(t01);

    // Dynamic Fog shift from midnight navy to dawn haze
    if (scene.fog && scene.fog instanceof THREE.FogExp2) {
      if (t01 < 0.42) {
        scene.fog.color.setHex(0x030712);
        scene.background = new THREE.Color(0x030712);
      } else if (t01 < 0.72) {
        const dawnRatio = (t01 - 0.42) / (0.72 - 0.42);
        scene.fog.color.lerpColors(new THREE.Color(0x030712), new THREE.Color(0x1e1b4b), dawnRatio);
        scene.background = new THREE.Color().lerpColors(new THREE.Color(0x030712), new THREE.Color(0x0c1b33), dawnRatio);
      } else {
        scene.fog.color.setHex(0x060c1d);
        scene.background = new THREE.Color(0x060c1d);
      }
    }
  }
  // =========================================================================
  // STORIES 02 - 05 (Global progress > 0.20): Persistent Emblem Assembly
  // =========================================================================
  else {
    // Fade out wilderness canopy & realistic branch smoothly
    if (envSystem) {
      envSystem.group.visible = false;
    }
    if (owlCharacter) {
      // Manguni from Story 01 stays alive in scene!
      owlCharacter.realisticOwlGroup.visible = false;
      owlCharacter.emblemManguniGroup.visible = true;
      owlCharacter.emblemManguniGroup.position.set(0, 0.5 + Math.sin(elapsed * 1.6) * 0.2, 0);
    }

    if (assemblyRoot) {
      assemblyRoot.visible = true;
    }

    // Reset camera to frontal framing for Stories 02 to 05
    const p = globalProgress;
    camera.position.set(0, 0, 38);
    camera.lookAt(0, 0, 0);

    // Apply Assembly Kinetics for subsequent stories
    updateSubsequentAssembly(p, elapsed);
  }
}

// Cinematic Camera System for Story 01 Shot 01 - 07
function updateStory01Camera(t: number) {
  if (!camera) return;

  const currentPos = new THREE.Vector3();
  const currentTarget = new THREE.Vector3();

  // SHOT 01: The Night Sky (0.00 -> 0.12)
  if (t < 0.12) {
    const p = t / 0.12;
    currentPos.set(0, -2.5, 8.0);
    currentTarget.set(0, THREE.MathUtils.lerp(45.0, 8.0, p), -5.0);
  }
  // SHOT 02: Into the Wilderness (0.12 -> 0.25)
  else if (t < 0.25) {
    const p = (t - 0.12) / (0.25 - 0.12);
    currentPos.set(
      THREE.MathUtils.lerp(0.0, 1.2, p),
      THREE.MathUtils.lerp(-2.5, -1.0, p),
      THREE.MathUtils.lerp(8.0, 5.5, p)
    );
    currentTarget.set(0, THREE.MathUtils.lerp(8.0, -1.8, p), 0.0);
  }
  // SHOT 03: The Awakening (0.25 -> 0.42)
  else if (t < 0.42) {
    const p = (t - 0.25) / (0.42 - 0.25);
    currentPos.set(
      THREE.MathUtils.lerp(1.2, 0.4, p),
      THREE.MathUtils.lerp(-1.0, -0.6, p),
      THREE.MathUtils.lerp(5.5, 4.0, p)
    );
    currentTarget.set(0, -0.5, 0.0);
  }
  // SHOT 04: The First Light (0.42 -> 0.55)
  else if (t < 0.55) {
    const p = (t - 0.42) / (0.55 - 0.42);
    currentPos.set(
      THREE.MathUtils.lerp(0.4, -0.8, p),
      THREE.MathUtils.lerp(-0.6, -0.2, p),
      THREE.MathUtils.lerp(4.0, 6.2, p)
    );
    currentTarget.set(0, 0.0, 0.0);
  }
  // SHOT 05: The Flight (0.55 -> 0.72)
  else if (t < 0.72) {
    const p = (t - 0.55) / (0.72 - 0.55);
    const owlY = -1.5 + p * 6.5;
    const owlZ = p * -8.0;

    currentPos.set(0.0, owlY - 1.2, owlZ + 6.5);
    currentTarget.set(0.0, owlY + 0.5, owlZ);
  }
  // SHOT 06: The Transformation (0.72 -> 0.92)
  else if (t < 0.92) {
    const p = (t - 0.72) / (0.92 - 0.72);
    const angle = (1 - p) * Math.PI;
    const radius = THREE.MathUtils.lerp(8.0, 32.0, p);
    const targetOwlY = THREE.MathUtils.lerp(5.0, 0.5, p);

    currentPos.set(
      Math.sin(angle) * 14.0,
      THREE.MathUtils.lerp(3.8, 1.5, p),
      THREE.MathUtils.lerp(-1.5, radius, p)
    );
    currentTarget.set(0, targetOwlY, 0);
  }
  // SHOT 07: The Emblem Manguni (0.92 -> 1.00)
  else {
    currentPos.set(0, 1.5, 34.0);
    currentTarget.set(0, 0.5, 0.0);
  }

  camera.position.copy(currentPos);
  camera.lookAt(currentTarget);
}

// Assembly kinetics for Stories 02 to 05
function updateSubsequentAssembly(p: number, elapsed: number) {
  if (!assemblyRoot) return;

  // --- STAGE 1: WINGS DOCK (0.20 -> 0.45) ---
  const tWings = smoothStep(0.20, 0.45, p);
  if (wingLeftMesh) {
    wingLeftMesh.position.set(-(1 - tWings) * 28.0, (1 - tWings) * 14.0, 0.3 + (1 - tWings) * 20.0);
    wingLeftMesh.rotation.set((1 - tWings) * 0.4, -(1 - tWings) * 0.8, -(1 - tWings) * 0.7);
    (wingLeftMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tWings * 1.4);
    wingLeftMesh.visible = tWings > 0.01;
  }
  if (wingRightMesh) {
    wingRightMesh.position.set((1 - tWings) * 28.0, (1 - tWings) * 14.0, 0.3 + (1 - tWings) * 20.0);
    wingRightMesh.rotation.set((1 - tWings) * 0.4, (1 - tWings) * 0.8, (1 - tWings) * 0.7);
    (wingRightMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tWings * 1.4);
    wingRightMesh.visible = tWings > 0.01;
  }

  // --- STAGE 2: HEAD & BODY (0.42 -> 0.68) ---
  const tBody = smoothStep(0.42, 0.68, p);
  if (bodyHeadMesh) {
    bodyHeadMesh.position.set(0, (1 - tBody) * 24.0, 0.6 + (1 - tBody) * 22.0);
    (bodyHeadMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tBody * 1.4);
    bodyHeadMesh.visible = tBody > 0.01;
  }

  // --- STAGE 3: CHEST EMBLEM & RIBBON (0.65 -> 0.88) ---
  const tChest = smoothStep(0.65, 0.88, p);
  if (chestMesh) {
    chestMesh.position.set(0, -(1 - tChest) * 6.0, 0.9 + (1 - tChest) * 22.0);
    (chestMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tChest * 1.4);
    chestMesh.visible = tChest > 0.01;
  }
  if (ribbonMesh) {
    ribbonMesh.position.set(0, -(1 - tChest) * 24.0, 1.2 + (1 - tChest) * 16.0);
    (ribbonMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tChest * 1.4);
    ribbonMesh.visible = tChest > 0.01;
  }

  // --- STAGE 4: SHIELD & FINAL EMBLEM (0.85 -> 1.00) ---
  const tShield = smoothStep(0.60, 0.85, p);
  if (shieldMesh) {
    shieldMesh.position.set(0, (1 - tShield) * 8.0, (1 - tShield) * -38.0);
    (shieldMesh.material as THREE.MeshStandardMaterial).opacity = Math.min(1, tShield * 1.5 + 0.1);
  }

  const tSnap = smoothStep(0.85, 1.0, p);
  if (completeEmblemMesh) {
    if (tSnap > 0.7) {
      completeEmblemMesh.visible = true;
      (completeEmblemMesh.material as THREE.MeshStandardMaterial).opacity = (tSnap - 0.7) / 0.3;
      completeEmblemMesh.position.z = 1.4;
    } else {
      completeEmblemMesh.visible = false;
    }
  }

  // Golden Flare Shockwave
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

  // Levitation & Mouse Tilt Parallax
  const idleY = Math.sin(elapsed * 1.6) * 0.35 * (0.3 + tSnap * 0.7);
  const idleRoll = Math.cos(elapsed * 1.1) * 0.02 * (0.3 + tSnap * 0.7);
  const parallaxX = mouseX.value * 0.32;
  const parallaxY = -mouseY.value * 0.22;

  assemblyRoot.position.y = idleY;
  assemblyRoot.rotation.x = parallaxY + (1 - tSnap) * 0.04;
  assemblyRoot.rotation.y = parallaxX + idleRoll + (1 - tSnap) * 0.08;

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

  adjustCameraForAspect(width, height);
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

  stopNatureAudio();
  if (audioContext) {
    try {
      audioContext.close();
    } catch {}
    audioContext = null;
  }

  loadedTextures.forEach((tex) => tex.dispose());
  loadedTextures = [];

  envSystem?.dispose();
  lightSystem?.dispose();
  owlCharacter?.dispose();

  if (renderer) {
    renderer.dispose();
    if (renderer.domElement && renderer.domElement.parentNode) {
      renderer.domElement.parentNode.removeChild(renderer.domElement);
    }
  }

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
    <!-- Top Audio Ambience Toggle Button -->
    <div class="audio-control-bar">
      <button
        class="btn-audio-toggle"
        :class="{ active: isAudioEnabled }"
        @click="toggleAudio"
        title="Aktifkan / Matikan Suara Alam Sinematik Minahasa Selatan"
      >
        <Volume2 v-if="isAudioEnabled" :size="13" />
        <VolumeX v-else :size="13" />
        <span>{{ isAudioEnabled ? 'Suara Alam Aktif' : 'Nyalakan Suara' }}</span>
      </button>
    </div>

    <!-- Minimal HUD Shot Tracker for Story 01 -->
    <div v-if="isStory01Active" class="hud-phase-tracker">
      <div class="hud-story-badge">
        <Sparkles :size="11" class="hud-sparkle" />
        <span>STORY 01 &bull; KEBANGKITAN BURUNG MANGUNI</span>
      </div>

      <div class="hud-phase-pill" :style="{ borderColor: currentShot.color + '66' }">
        <span
          class="hud-pulse"
          :style="{ backgroundColor: currentShot.color, boxShadow: '0 0 10px ' + currentShot.color }"
        ></span>
        <span class="hud-step-tag">SHOT 0{{ currentShot.index }}</span>
        <span class="hud-sep">&bull;</span>
        <span class="hud-name" :style="{ color: currentShot.color }">{{ currentShot.name }}</span>
      </div>

      <div class="hud-phase-desc">
        {{ currentShot.desc }}
      </div>

      <div class="hud-meta">
        <span>SHOT {{ currentShot.index }} / 07</span>
        <span class="hud-sep">&bull;</span>
        <span>PROGRES: {{ Math.round(story01Progress * 100) }}%</span>
        <span class="hud-sep">&bull;</span>
        <span>CELEPUK SULAWESI &rarr; MANGUNI 3D</span>
      </div>
    </div>

    <!-- Minimal HUD Phase Tracker for Stories 02 to 05 -->
    <div v-else class="hud-phase-tracker">
      <div class="hud-phase-pill" :style="{ borderColor: currentPhase.color + '55' }">
        <span
          class="hud-pulse"
          :style="{ backgroundColor: currentPhase.color, boxShadow: '0 0 10px ' + currentPhase.color }"
        ></span>
        <span class="hud-step-tag">{{ currentPhase.step }}</span>
        <span class="hud-sep">&bull;</span>
        <span class="hud-name" :style="{ color: currentPhase.color }">{{ currentPhase.name }}</span>
      </div>

      <div class="hud-phase-desc">
        {{ currentPhase.desc }}
      </div>

      <div class="hud-meta">
        <span>ASSEMBLY: {{ Math.round((props.scrollProgress || 0) * 100) }}%</span>
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

/* Audio Ambience Toggle in Upper Right */
.audio-control-bar {
  position: absolute;
  top: 76px;
  right: 24px;
  z-index: 10;
  pointer-events: auto;
}

.btn-audio-toggle {
  display: flex;
  align-items: center;
  gap: 7px;
  background: rgba(15, 23, 42, 0.78);
  border: 1px solid rgba(56, 189, 248, 0.28);
  backdrop-filter: blur(10px);
  padding: 5px 12px;
  border-radius: 9999px;
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: 11px;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-audio-toggle:hover {
  background: rgba(30, 41, 59, 0.92);
  color: #e2e8f0;
  border-color: rgba(56, 189, 248, 0.55);
}

.btn-audio-toggle.active {
  background: rgba(16, 185, 129, 0.2);
  border-color: #10b981;
  color: #34d399;
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

.hud-story-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10px;
  font-weight: 600;
  color: #94a3b8;
  letter-spacing: 0.8px;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.85);
}

.hud-sparkle {
  color: #facc15;
}

.hud-phase-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(15, 23, 42, 0.88);
  border: 1px solid rgba(56, 189, 248, 0.3);
  backdrop-filter: blur(12px);
  padding: 5px 14px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.6);
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
  max-width: 400px;
  text-align: right;
  line-height: 1.4;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.95);
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

@media (max-width: 640px) {
  .audio-control-bar {
    top: 65px;
    right: 14px;
  }
  .hud-phase-tracker {
    bottom: 56px;
    right: 14px;
    left: 14px;
    align-items: center;
  }
  .hud-phase-pill {
    font-size: 10px;
    padding: 4px 10px;
  }
  .hud-phase-desc {
    text-align: center;
    font-size: 10px;
    max-width: 100%;
  }
  .hud-meta {
    font-size: 9px;
  }
}
</style>
