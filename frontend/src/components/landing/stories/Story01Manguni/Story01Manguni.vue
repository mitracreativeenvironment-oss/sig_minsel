<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import * as THREE from 'three';
import { Volume2, VolumeX, Sparkles } from 'lucide-vue-next';
import { createEnvironment, type Story01Environment } from './scene/createEnvironment';
import { createLighting, type Story01Lighting } from './scene/createLighting';
import { createOwlCharacter, type Story01OwlCharacter } from './scene/createOwlCharacter';
import { createCamera, type Story01CameraRig } from './scene/createCamera';
import { getStory01CurrentShot } from './animation/createStoryTimeline';

const props = withDefaults(
  defineProps<{
    scrollProgress: number; // Global scroll progress (0.0 to 1.0)
    activeSection?: number;
  }>(),
  {
    scrollProgress: 0,
    activeSection: 0
  }
);

const canvasContainer = ref<HTMLDivElement | null>(null);

// Audio State (User-Controlled Audio Engine)
const isAudioEnabled = ref(false);
let audioContext: AudioContext | null = null;
let nightNoiseNode: AudioNode | null = null;

// Three.js Core Objects
let renderer: THREE.WebGLRenderer | null = null;
let scene: THREE.Scene | null = null;
let timer: THREE.Timer | null = null;
let animationFrameId: number | null = null;

// Story 01 Scene Subsystems
let envSystem: Story01Environment | null = null;
let lightSystem: Story01Lighting | null = null;
let owlCharacter: Story01OwlCharacter | null = null;
let cameraRig: Story01CameraRig | null = null;

// Mouse Parallax
const mouseX = ref(0);
const mouseY = ref(0);

// Relative progress within Story 01 (0.0 to 1.0 when scrollProgress is 0.00 to 0.20)
const story01Progress = computed(() => {
  // Story 01 spans 0.00 to 0.20 of global scrollytelling
  const p = Math.max(0, Math.min(1, props.scrollProgress / 0.20));
  return p;
});

const currentShot = computed(() => {
  return getStory01CurrentShot(story01Progress.value);
});

// Audio Synthesizer Toggle (Nature Ambience without external file dependencies)
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

    // Gentle pink noise filtered for night forest breeze & cricket whisper
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

    // Highpass filter for nocturnal crickets & wind whisper
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

  // 2. Persistent Scene & Atmospheric Fog
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x030712); // Deep midnight sky
  scene.fog = new THREE.FogExp2(0x030712, 0.016);

  // 3. Camera System
  cameraRig = createCamera(width, height);

  // 4. Lighting System
  lightSystem = createLighting(scene);

  // 5. Wilderness & Celestial Environment (Shot 01 & 02)
  envSystem = createEnvironment();
  scene.add(envSystem.group);

  // 6. Owl Character System (Celepuk Sulawesi & Official Emblem Manguni)
  owlCharacter = createOwlCharacter();
  scene.add(owlCharacter.rootGroup);

  // 7. Animation Loop
  timer = new THREE.Timer();
  timer.connect(document);

  const animate = () => {
    if (!renderer || !scene || !cameraRig || !timer) return;
    timer.update();
    const elapsed = timer.getElapsed();

    const t = story01Progress.value;
    const aspect = width / height;

    // Update Subsystems
    envSystem?.update(t, elapsed);
    lightSystem?.update(t);
    owlCharacter?.update(t, elapsed);
    cameraRig?.update(t, aspect);

    // Dynamic Fog shift from midnight navy to dawn haze
    if (scene.fog && scene.fog instanceof THREE.FogExp2) {
      if (t < 0.42) {
        scene.fog.color.setHex(0x030712);
        scene.background = new THREE.Color(0x030712);
      } else if (t < 0.72) {
        const dawnRatio = (t - 0.42) / (0.72 - 0.42);
        scene.fog.color.lerpColors(new THREE.Color(0x030712), new THREE.Color(0x1e1b4b), dawnRatio);
        scene.background = new THREE.Color().lerpColors(new THREE.Color(0x030712), new THREE.Color(0x0c1b33), dawnRatio);
      } else {
        scene.fog.color.setHex(0x060c1d);
        scene.background = new THREE.Color(0x060c1d);
      }
    }

    // Render Scene
    renderer.render(scene, cameraRig.camera);
    animationFrameId = requestAnimationFrame(animate);
  };

  animationFrameId = requestAnimationFrame(animate);
  window.addEventListener('resize', handleResize);
  window.addEventListener('mousemove', handleMouseMove);
}

function handleMouseMove(e: MouseEvent) {
  mouseX.value = (e.clientX / window.innerWidth) * 2 - 1;
  mouseY.value = (e.clientY / window.innerHeight) * 2 - 1;
}

function handleResize() {
  if (!canvasContainer.value || !renderer || !cameraRig) return;
  const width = canvasContainer.value.clientWidth;
  const height = canvasContainer.value.clientHeight;

  renderer.setSize(width, height);
  cameraRig.update(story01Progress.value, width / height);
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

  // Dispose Subsystems
  envSystem?.dispose();
  lightSystem?.dispose();
  owlCharacter?.dispose();
  cameraRig?.dispose();

  if (renderer) {
    renderer.dispose();
    if (renderer.domElement && renderer.domElement.parentNode) {
      renderer.domElement.parentNode.removeChild(renderer.domElement);
    }
  }
});
</script>

<template>
  <div ref="canvasContainer" class="threejs-story01-canvas">
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

    <!-- Minimal HUD Shot Narrative Tracker -->
    <div class="hud-story01-tracker">
      <div class="hud-story-badge">
        <Sparkles :size="11" class="hud-sparkle" />
        <span>STORY 01 &bull; KEBANGKITAN BURUNG MANGUNI</span>
      </div>

      <div class="hud-shot-pill" :style="{ borderColor: currentShot.color + '66' }">
        <span
          class="hud-pulse"
          :style="{ backgroundColor: currentShot.color, boxShadow: '0 0 10px ' + currentShot.color }"
        ></span>
        <span class="hud-shot-name" :style="{ color: currentShot.color }">{{ currentShot.name }}</span>
      </div>

      <div class="hud-shot-desc">
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
  </div>
</template>

<style scoped>
.threejs-story01-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
  overflow: hidden;
}

.threejs-story01-canvas :deep(canvas) {
  display: block;
  width: 100% !important;
  height: 100% !important;
}

/* Audio Toggle Button in Upper Right */
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
  background: rgba(15, 23, 42, 0.75);
  border: 1px solid rgba(56, 189, 248, 0.25);
  backdrop-filter: blur(8px);
  padding: 5px 12px;
  border-radius: 9999px;
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  font-size: 11px;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-audio-toggle:hover {
  background: rgba(30, 41, 59, 0.9);
  color: #e2e8f0;
  border-color: rgba(56, 189, 248, 0.5);
}

.btn-audio-toggle.active {
  background: rgba(16, 185, 129, 0.2);
  border-color: #10b981;
  color: #34d399;
}

/* Bottom HUD Shot Narrative Tracker */
.hud-story01-tracker {
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
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
}

.hud-sparkle {
  color: #facc15;
}

.hud-shot-pill {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(15, 23, 42, 0.88);
  border: 1px solid rgba(56, 189, 248, 0.3);
  backdrop-filter: blur(12px);
  padding: 5px 14px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  box-shadow: 0 4px 18px rgba(0, 0, 0, 0.6);
  transition: border-color 0.4s ease;
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

.hud-shot-name {
  transition: color 0.4s ease;
}

.hud-shot-desc {
  font-size: 11px;
  color: #cbd5e1;
  max-width: 420px;
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
  .hud-story01-tracker {
    bottom: 56px;
    right: 14px;
    left: 14px;
    align-items: center;
  }
  .hud-shot-desc {
    text-align: center;
    font-size: 10px;
    max-width: 100%;
  }
  .hud-meta {
    font-size: 9px;
  }
}
</style>
