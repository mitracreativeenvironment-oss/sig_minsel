<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import gsap from 'gsap';
import MinselRealScenery from '../components/MinselRealScenery.vue';
import MinselTerrainCanvas from '../components/MinselTerrainCanvas.vue';
import {
  Layers,
  ShieldCheck,
  ArrowRight,
  Database,
  ChevronRight,
  ChevronLeft,
  Camera,
  Globe2
} from 'lucide-vue-next';

const emit = defineEmits<{
  (e: 'navigate', view: 'geoportal' | 'dashboard' | 'landing'): void;
  (e: 'open-login'): void;
}>();

// ==============================================================================
// HORIZONTAL SCROLLYTELLING STATE & ENGINE
// ==============================================================================
const currentProgress = ref(0); // 0.0 to 1.0
const targetProgress = ref(0);
const activeSection = ref(0); // 0 to 4
const bgMode = ref<'real' | '3d'>('real'); // Default: Menggunakan Gambar / Foto Nyata Lanskap Minsel
let rafId: number | null = null;
const selectedZone = ref<'all' | 'pesisir' | 'tinggi' | 'tengah'>('all');

// 17 Kecamatan Resmi Minahasa Selatan dikelompokkan menurut Karakter Wilayah
const kecamatanList = [
  { nama: 'Amurang', zone: 'pesisir', tipe: 'Ibu Kota Kabupaten', luas: '105.7 km²', desa: 8, trait: 'Pusat Pemerintahan & Teluk Amurang' },
  { nama: 'Amurang Barat', zone: 'pesisir', tipe: 'Penyangga Pesisir', luas: '87.4 km²', desa: 10, trait: 'Kawasan Pesisir & Perikanan' },
  { nama: 'Amurang Timur', zone: 'pesisir', tipe: 'Kawasan Aglomerasi', luas: '94.2 km²', desa: 10, trait: 'Akses Poros Trans Sulawesi' },
  { nama: 'Tumpaan', zone: 'pesisir', tipe: 'Gerbang Utara', luas: '76.1 km²', desa: 10, trait: 'Kawasan Perdagangan & Jasa' },
  { nama: 'Tatapaan', zone: 'pesisir', tipe: 'Pesisir Utara & Mangrove', luas: '85.4 km²', desa: 11, trait: 'Konservasi Mangrove & Tambak' },
  { nama: 'Tenga', zone: 'pesisir', tipe: 'Kawasan Strategis Pesisir', luas: '122.6 km²', desa: 18, trait: 'Pertanian Padi & Perikanan' },
  { nama: 'Sinonsayang', zone: 'pesisir', tipe: 'Pesisir Barat Daya', luas: '134.2 km²', desa: 13, trait: 'Perikanan Tangkap & Kelapa' },

  { nama: 'Modoinding', zone: 'tinggi', tipe: 'Dataran Tinggi Subur', luas: '52.6 km²', desa: 10, trait: 'Dapur Sayur Indonesia Timur (1.000+ mdpl)' },
  { nama: 'Tompaso Baru', zone: 'tinggi', tipe: 'Sentra Pangan', luas: '112.8 km²', desa: 10, trait: 'Lahan Pertanian & Holtikultura' },
  { nama: 'Maesaan', zone: 'tinggi', tipe: 'Kawasan Lembah', luas: '88.3 km²', desa: 12, trait: 'Pertanian Terpadu & Perkebunan' },

  { nama: 'Tareran', zone: 'tengah', tipe: 'Kawasan Perbukitan', luas: '82.5 km²', desa: 13, trait: 'Perkebunan Kelapa & Aren' },
  { nama: 'Suluun Tareran', zone: 'tengah', tipe: 'Dataran Menengah', luas: '61.3 km²', desa: 9, trait: 'Sentra Gula Aren & Holtikultura' },
  { nama: 'Ranoyapo', zone: 'tengah', tipe: 'Lembah Sungai Utama', luas: '96.4 km²', desa: 12, trait: 'DAS Ranoyapo & Hutan Lindung' },
  { nama: 'Motoling', zone: 'tengah', tipe: 'Pusat Kawasan Tengah', luas: '64.9 km²', desa: 7, trait: 'Pendidikan & Perdagangan Lokal' },
  { nama: 'Motoling Barat', zone: 'tengah', tipe: 'Kawasan Agrowisata', luas: '71.2 km²', desa: 8, trait: 'Perkebunan Cengkih & Kopi' },
  { nama: 'Motoling Timur', zone: 'tengah', tipe: 'Perbukitan Hijau', luas: '68.5 km²', desa: 8, trait: 'Konservasi & Perkebunan Rakyat' },
  { nama: 'Kumelembuai', zone: 'tengah', tipe: 'Kawasan Perkebunan', luas: '54.7 km²', desa: 8, trait: 'Komoditas Cengkih Minsel' }
];

const filteredKecamatan = () => {
  if (selectedZone.value === 'all') return kecamatanList;
  return kecamatanList.filter(k => k.zone === selectedZone.value);
};

// 5 Pilar Tematik dari 28 Kategori Geospasial SDI
const thematicPillars = [
  {
    title: 'Batas & Administrasi Wilayah',
    opd: 'Bagian Tata Pemerintahan & Setda',
    categories: ['Batas Kabupaten Minsel', 'Batas 17 Kecamatan', 'Batas 177 Desa/Kelurahan', 'Kode Wilayah Kemendagri'],
    geom: 'MultiPolygon',
    color: '#38bdf8'
  },
  {
    title: 'Infrastruktur & Pekerjaan Umum',
    opd: 'Dinas PUPR & Dinas Perkim',
    categories: ['Jalan Poros Trans Sulawesi', 'Jaringan Irigasi Modoinding', 'Jembatan & Drainase', 'Jaringan Air Minum'],
    geom: 'LineString / Point',
    color: '#f59e0b'
  },
  {
    title: 'Pertanian & Ketahanan Pangan',
    opd: 'Dinas Pertanian & Dinas Perkebunan',
    categories: ['Sentra Hortikultura Sayur', 'Lahan Sawah Dilindungi (LSD)', 'Perkebunan Cengkih & Kelapa'],
    geom: 'Polygon',
    color: '#10b981'
  },
  {
    title: 'Tata Ruang RTRW & Lingkungan',
    opd: 'Bappelitbangda & DLH',
    categories: ['Pola Ruang RTRW 2024-2044', 'Kawasan Lindung & Hutan', 'Ruang Terbuka Hijau (RTH)'],
    geom: 'Polygon',
    color: '#8b5cf6'
  },
  {
    title: 'Kebencanaan & Fasilitas Publik',
    opd: 'BPBD, Dinas Kesehatan & Dikbud',
    categories: ['Zona Bahaya Erupsi Soputan', 'Rawan Longsor Ranoyapo', 'Puskesmas & RSUD Amurang', 'Gedung Sekolah'],
    geom: 'Point / Polygon',
    color: '#ef4444'
  }
];

// 8 Role Resmi Fase 2
const officialRolesList = [
  { role: 'Superadmin', opd: 'Diskominfo', desc: 'Kontrol penuh sistem, user, OPD & upload batas administrasi resmi.' },
  { role: 'Admin GIS', opd: 'Diskominfo GIS', desc: 'Standardisasi format, CRS EPSG:4326, katalog layer, dan publikasi.' },
  { role: 'Admin OPD', opd: 'OPD Terkait (PUPR, Pertanian, dll)', desc: 'Pengendalian akun operator & review substansi sektoral dinas.' },
  { role: 'Operator OPD', opd: 'Petugas Teknis OPD', desc: 'Digitalisasi objek spasial, upload dataset & kelengkapan atribut.' },
  { role: 'Validator GIS', opd: 'Tim Teknis GIS', desc: 'Validasi topologi, bebas overlap, kesesuaian koordinat, dan atribut.' },
  { role: 'Approver Data', opd: 'Bappelitbangda (Walidata)', desc: 'Persetujuan terpusat legalitas data sebelum dapat dipublikasikan.' },
  { role: 'Pimpinan', opd: 'Bupati / Sekda / Kepala OPD', desc: 'Pemantauan eksekutif capaian pembangunan berbasis peta tematik.' },
  { role: 'Publik', opd: 'Masyarakat & Investor', desc: 'Akses terbuka peta interaktif, unduh data publik, transparansi wilayah.' }
];

// Chapter Titles untuk Timeline Bawah
const chapterTitles = [
  '01 Ikhtisar GeoMinsel',
  '02 17 Wilayah Kecamatan',
  '03 28 Kategori Spasial',
  '04 Alur Persetujuan 8 Role',
  '05 Gerbang Akses Portal'
];

// ==============================================================================
// HORIZONTAL SCROLL & GESTURE HANDLERS
// ==============================================================================
function onWheel(e: WheelEvent) {
  // Cegah scroll vertikal default browser dan alihkan ke scroll horizontal mulus
  e.preventDefault();

  const delta = Math.abs(e.deltaY) > Math.abs(e.deltaX) ? e.deltaY : e.deltaX;
  const sensitivity = 0.00085;

  targetProgress.value = Math.max(0, Math.min(1, targetProgress.value + delta * sensitivity));
}

// Navigasi ke Chapter Tertentu (Animasi Lembut dengan GSAP)
function goToSection(index: number) {
  const target = Math.max(0, Math.min(4, index)) / 4;
  gsap.to(targetProgress, {
    value: target,
    duration: 0.9,
    ease: 'power2.out'
  });
}

function nextSection() {
  if (activeSection.value < 4) {
    goToSection(activeSection.value + 1);
  }
}

function prevSection() {
  if (activeSection.value > 0) {
    goToSection(activeSection.value - 1);
  }
}

// Keyboard Navigation (Arrow Keys)
function onKeyDown(e: KeyboardEvent) {
  if (e.key === 'ArrowRight' || e.key === 'ArrowDown' || e.key === 'PageDown') {
    e.preventDefault();
    nextSection();
  } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp' || e.key === 'PageUp') {
    e.preventDefault();
    prevSection();
  }
}

// Touch Gestures (Mobile / Tablet Horizontal Swipe)
let touchStartX = 0;
function onTouchStart(e: TouchEvent) {
  if (e.touches[0]) {
    touchStartX = e.touches[0].clientX;
  }
}

function onTouchMove(e: TouchEvent) {
  if (!e.touches[0]) return;
  const currentX = e.touches[0].clientX;
  const deltaX = touchStartX - currentX;
  touchStartX = currentX;

  const sensitivity = 0.0018;
  targetProgress.value = Math.max(0, Math.min(1, targetProgress.value + deltaX * sensitivity));
}

// Engine Loop Animasi Lerp Buttery 60/120fps
function loop() {
  // Lerp dampening
  currentProgress.value += (targetProgress.value - currentProgress.value) * 0.12;

  // Active section (0 to 4)
  const calcActive = Math.min(4, Math.floor(currentProgress.value * 4 + 0.5));
  activeSection.value = calcActive;

  rafId = requestAnimationFrame(loop);
}

onMounted(() => {
  window.addEventListener('keydown', onKeyDown);
  rafId = requestAnimationFrame(loop);
});

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeyDown);
  if (rafId) cancelAnimationFrame(rafId);
});
</script>

<template>
  <div
    class="landing-viewport"
    @wheel.prevent="onWheel"
    @touchstart="onTouchStart"
    @touchmove="onTouchMove"
  >
    <!-- Background Latar: Foto Nyata Lanskap Minsel (Default) atau Peta 3D Digital -->
    <MinselRealScenery
      v-if="bgMode === 'real'"
      :scroll-progress="currentProgress"
      :active-section="activeSection"
    />
    <MinselTerrainCanvas
      v-else
      :scroll-progress="currentProgress"
      :active-section="activeSection"
    />

    <!-- Backdrop Vignette untuk Legibilitas Tinggi -->
    <div class="viewport-backdrop"></div>

    <!-- Header Atas Mengambang (Floating Header) -->
    <header class="landing-header">
      <div class="brand-container" @click="goToSection(0)">
        <div class="brand-emblem">
          <img src="/favicon.svg" alt="Logo Minahasa Selatan" width="28" height="28" />
        </div>
        <div class="brand-info">
          <span class="brand-name">GEOMINSEL</span>
          <span class="brand-tag">Kabupaten Minahasa Selatan</span>
        </div>
      </div>

      <!-- Navigasi Horizontal Bab -->
      <nav class="landing-nav">
        <button
          class="nav-link"
          :class="{ active: activeSection === 0 }"
          @click="goToSection(0)"
        >
          01 Ikhtisar
        </button>
        <button
          class="nav-link"
          :class="{ active: activeSection === 1 }"
          @click="goToSection(1)"
        >
          02 17 Kecamatan
        </button>
        <button
          class="nav-link"
          :class="{ active: activeSection === 2 }"
          @click="goToSection(2)"
        >
          03 28 Kategori SDI
        </button>
        <button
          class="nav-link"
          :class="{ active: activeSection === 3 }"
          @click="goToSection(3)"
        >
          04 Tata Kelola
        </button>
        <button
          class="nav-link"
          :class="{ active: activeSection === 4 }"
          @click="goToSection(4)"
        >
          05 Mulai Akses
        </button>
      </nav>

      <div class="header-actions">
        <!-- Switcher Mode Latar: Foto Nyata / 3D Topografi -->
        <div class="bg-mode-switcher">
          <button
            class="mode-switch-btn"
            :class="{ active: bgMode === 'real' }"
            @click="bgMode = 'real'"
            title="Gunakan Foto Nyata Lanskap Minahasa Selatan"
          >
            <Camera :size="13" />
            <span>Foto Nyata</span>
          </button>
          <button
            class="mode-switch-btn"
            :class="{ active: bgMode === '3d' }"
            @click="bgMode = '3d'"
            title="Gunakan Animasi 3D Topografi"
          >
            <Globe2 :size="13" />
            <span>3D Topografi</span>
          </button>
        </div>

        <button class="btn-portal" @click="emit('navigate', 'geoportal')">
          <Layers :size="16" />
          <span>Peta Interaktif</span>
        </button>
        <button class="btn-login" @click="emit('open-login')">
          <ShieldCheck :size="16" />
          <span>Masuk Dashboard</span>
        </button>
      </div>
    </header>

    <!-- ============================================================================== -->
    <!-- HORIZONTAL SCROLLYTELLING TRACK (500vw WIDTH - SLIDES HORIZONTALLY ON WHEEL)   -->
    <!-- ============================================================================== -->
    <div
      class="horizontal-track"
      :style="{ transform: `translate3d(-${currentProgress * 400}vw, 0, 0)` }"
    >
      <!-- ============================================== -->
      <!-- SLIDE 0: HERO OVERVIEW                         -->
      <!-- ============================================== -->
      <section class="horizontal-slide slide-hero">
        <div class="slide-content-wrap hero-wrap">
          <div class="pill-badge">
            <span class="pill-dot"></span>
            <span>Geoportal Resmi Satu Data Spasial &bull; WGS84 EPSG:4326</span>
          </div>

          <h1 class="hero-title">
            Infrastruktur Geospasial Resmi <br />
            <span class="text-gradient">Kabupaten Minahasa Selatan</span>
          </h1>

          <p class="hero-description">
            Platform mandiri Pemerintah Kabupaten Minahasa Selatan untuk pengelolaan data spasial terpadu.
            Menghubungkan 17 Kecamatan, 167 Desa, dan 10 Kelurahan dengan kepemilikan data sektoral OPD 
            dan tata kelola persetujuan terpusat (*Central Approval*).
          </p>

          <div class="hero-cta-group">
            <button class="btn-hero-primary" @click="emit('navigate', 'geoportal')">
              <span>Buka Geoportal Peta</span>
              <ArrowRight :size="18" />
            </button>
            <button class="btn-hero-secondary" @click="emit('open-login')">
              <ShieldCheck :size="18" />
              <span>Login Dashboard 8 Role</span>
            </button>
          </div>

          <!-- Quick Metrics Bar -->
          <div class="metrics-strip">
            <div class="metric-item">
              <span class="metric-num">17</span>
              <span class="metric-lbl">Kecamatan Terpetakan</span>
            </div>
            <div class="metric-div"></div>
            <div class="metric-item">
              <span class="metric-num">177</span>
              <span class="metric-lbl">Desa &amp; Kelurahan</span>
            </div>
            <div class="metric-div"></div>
            <div class="metric-item">
              <span class="metric-num">28</span>
              <span class="metric-lbl">Kategori Geospasial SDI</span>
            </div>
            <div class="metric-div"></div>
            <div class="metric-item">
              <span class="metric-num">8 Role</span>
              <span class="metric-lbl">Persetujuan Terpusat</span>
            </div>
          </div>
        </div>

        <!-- Horizontal Scroll Cue -->
        <div class="horizontal-scroll-cue" @click="goToSection(1)">
          <span>Gulir ke kanan untuk jelajah wilayah</span>
          <ChevronRight :size="20" class="icon-pulse-x" />
        </div>
      </section>

      <!-- ============================================== -->
      <!-- SLIDE 1: 17 WILAYAH KECAMATAN                  -->
      <!-- ============================================== -->
      <section class="horizontal-slide slide-kecamatan">
        <div class="slide-content-wrap wide-wrap">
          <div class="panel-header">
            <div class="panel-badge-row">
              <span class="panel-kicker">Bab 02 &bull; Hierarki Administrasi Spasial</span>
              <div class="zone-filter-tabs">
                <button
                  class="tab-btn"
                  :class="{ active: selectedZone === 'all' }"
                  @click="selectedZone = 'all'"
                >
                  Semua (17)
                </button>
                <button
                  class="tab-btn"
                  :class="{ active: selectedZone === 'pesisir' }"
                  @click="selectedZone = 'pesisir'"
                >
                  Pesisir &amp; Teluk (7)
                </button>
                <button
                  class="tab-btn"
                  :class="{ active: selectedZone === 'tinggi' }"
                  @click="selectedZone = 'tinggi'"
                >
                  Dataran Tinggi (3)
                </button>
                <button
                  class="tab-btn"
                  :class="{ active: selectedZone === 'tengah' }"
                  @click="selectedZone = 'tengah'"
                >
                  Lembah &amp; Tengah (7)
                </button>
              </div>
            </div>
            <h2 class="panel-title">17 Wilayah Kecamatan Terhubung Hierarki</h2>
            <p class="panel-sub">
              Kabupaten Minahasa Selatan menaungi 17 Kecamatan dan 177 Desa/Kelurahan. 
              Setiap data sektoral terikat secara relasional pada hierarki induk.
            </p>
          </div>

          <!-- Horizontal Cards Reel -->
          <div class="horizontal-cards-reel">
            <div
              v-for="kec in filteredKecamatan()"
              :key="kec.nama"
              class="kec-card"
              @click="emit('navigate', 'geoportal')"
            >
              <div class="kec-top">
                <span class="kec-name">{{ kec.nama }}</span>
                <span class="kec-type">{{ kec.tipe }}</span>
              </div>
              <p class="kec-trait">{{ kec.trait }}</p>
              <div class="kec-meta">
                <span>Luas: {{ kec.luas }}</span>
                <span>&bull;</span>
                <span>{{ kec.desa }} Desa/Kel</span>
              </div>
            </div>
          </div>

          <div class="panel-action-bar">
            <button class="btn-subtle" @click="emit('navigate', 'geoportal')">
              <span>Buka peta interaktif batas poligon 17 kecamatan</span>
              <ArrowRight :size="16" />
            </button>
          </div>
        </div>
      </section>

      <!-- ============================================== -->
      <!-- SLIDE 2: 28 KATEGORI SATU DATA INDONESIA       -->
      <!-- ============================================== -->
      <section class="horizontal-slide slide-categories">
        <div class="slide-content-wrap wide-wrap">
          <div class="panel-header">
            <span class="panel-kicker">Bab 03 &bull; Standar Satu Data Indonesia</span>
            <h2 class="panel-title">5 Pilar Tematik &amp; 28 Kategori Spasial Nasional</h2>
            <p class="panel-sub">
              Dari sentra hortikultura dataran tinggi Modoinding hingga pelabuhan Teluk Amurang, 
              seluruh data diklasifikasikan dengan metadata SNI/ISO dan koordinat WGS84 resmi.
            </p>
          </div>

          <div class="pillars-horizontal-row">
            <div
              v-for="pillar in thematicPillars"
              :key="pillar.title"
              class="pillar-card"
            >
              <div class="pillar-indicator" :style="{ backgroundColor: pillar.color }"></div>
              <div class="pillar-body">
                <div class="pillar-opd">
                  <Database :size="14" />
                  <span>{{ pillar.opd }}</span>
                </div>
                <h3 class="pillar-title">{{ pillar.title }}</h3>
                <ul class="pillar-cat-list">
                  <li v-for="cat in pillar.categories" :key="cat">
                    <span class="cat-bullet"></span>
                    <span>{{ cat }}</span>
                  </li>
                </ul>
                <div class="pillar-geom">
                  <span>Geometri: {{ pillar.geom }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ============================================== -->
      <!-- SLIDE 3: TATA KELOLA 8 ROLE & PERSETUJUAN      -->
      <!-- ============================================== -->
      <section class="horizontal-slide slide-governance">
        <div class="slide-content-wrap wide-wrap">
          <div class="panel-header">
            <span class="panel-kicker">Bab 04 &bull; Tata Kelola &amp; Validasi Data</span>
            <h2 class="panel-title">Alur Persetujuan Terpusat (Central Approval Flow)</h2>
            <p class="panel-sub">
              Tidak ada data yang langsung terbit ke publik tanpa pemeriksaan teknis dan persetujuan legal 
              oleh Walidata (Bappelitbangda) dan Admin GIS (Diskominfo).
            </p>
          </div>

          <!-- 5-Step Visual Horizontal Pipeline -->
          <div class="pipeline-track-horizontal">
            <div class="pipeline-step">
              <div class="step-num">01</div>
              <div class="step-title">Penginputan Data</div>
              <div class="step-actor">Operator OPD</div>
              <p class="step-desc">Input objek spasial &amp; koordinat WGS84 sesuai urusan sektoral dinas.</p>
            </div>
            <div class="pipeline-arrow"><ArrowRight :size="16" /></div>
            <div class="pipeline-step">
              <div class="step-num">02</div>
              <div class="step-title">Review Substansi</div>
              <div class="step-actor">Admin OPD</div>
              <p class="step-desc">Pemeriksaan kebenaran substansi teknis dinas sebelum diajukan ke pusat.</p>
            </div>
            <div class="pipeline-arrow"><ArrowRight :size="16" /></div>
            <div class="pipeline-step">
              <div class="step-num">03</div>
              <div class="step-title">Validasi Teknis GIS</div>
              <div class="step-actor">Validator GIS</div>
              <p class="step-desc">Verifikasi topologi poligon, bebas overlap, CRS EPSG:4326, dan format.</p>
            </div>
            <div class="pipeline-arrow"><ArrowRight :size="16" /></div>
            <div class="pipeline-step highlight-step">
              <div class="step-num">04</div>
              <div class="step-title">Persetujuan Terpusat</div>
              <div class="step-actor">Approver (Bappelitbangda)</div>
              <p class="step-desc">Pemberian persetujuan resmi legalitas dataset sebelum dipublikasikan.</p>
            </div>
            <div class="pipeline-arrow"><ArrowRight :size="16" /></div>
            <div class="pipeline-step">
              <div class="step-num">05</div>
              <div class="step-title">Publikasi Geoportal</div>
              <div class="step-actor">Admin GIS Diskominfo</div>
              <p class="step-desc">Katalog layer dirilis ke publik dan dapat diakses di peta terbuka.</p>
            </div>
          </div>

          <!-- 8 Roles Ribbon -->
          <div class="roles-ribbon">
            <div v-for="r in officialRolesList" :key="r.role" class="role-chip" :title="r.desc">
              <span class="role-dot"></span>
              <span class="role-name">{{ r.role }}</span>
              <span class="role-opd">({{ r.opd }})</span>
            </div>
          </div>
        </div>
      </section>

      <!-- ============================================== -->
      <!-- SLIDE 4: CALL TO ACTION & GERBANG MASUK        -->
      <!-- ============================================== -->
      <section class="horizontal-slide slide-cta">
        <div class="slide-content-wrap cta-wrap">
          <div class="cta-header">
            <span class="panel-kicker">Bab 05 &bull; Mulai Akses GeoMinsel</span>
            <h2 class="panel-title">Pusat Data Geospasial Kabupaten Minahasa Selatan</h2>
            <p class="panel-sub">
              Pilih gerbang akses sesuai hak akses dan kebutuhan Anda:
            </p>
          </div>

          <div class="access-boxes-horizontal">
            <!-- Box 1: Geoportal Publik -->
            <div class="access-box" @click="emit('navigate', 'geoportal')">
              <div class="box-icon icon-cyan">
                <Layers :size="32" />
              </div>
              <h3 class="box-title">Geoportal Publik Interaktif</h3>
              <p class="box-desc">
                Akses terbuka untuk masyarakat, akademisi, dan investor. Jelajahi 17 kecamatan, 
                efek fokus teritorial Minahasa Selatan, peta satelit, kontur topografi, dan unduh data terbuka.
              </p>
              <div class="box-action">
                <span>Buka Peta Interaktif Sekarang</span>
                <ArrowRight :size="16" />
              </div>
            </div>

            <!-- Box 2: Dashboard OPD -->
            <div class="access-box" @click="emit('open-login')">
              <div class="box-icon icon-amber">
                <ShieldCheck :size="32" />
              </div>
              <h3 class="box-title">Dashboard Pengguna &amp; OPD</h3>
              <p class="box-desc">
                Portal terproteksi otorisasi 8 Role: Superadmin, Admin GIS, Admin OPD, Operator, 
                Validator Teknis, Approver Walidata, dan Pimpinan Eksekutif Pemkab.
              </p>
              <div class="box-action">
                <span>Masuk Dashboard Resmi</span>
                <ArrowRight :size="16" />
              </div>
            </div>
          </div>

          <footer class="landing-footer">
            <div class="footer-copy">
              <strong>Pemerintah Kabupaten Minahasa Selatan</strong> &bull; Dinas Komunikasi dan Informatika 
              bersama Bappelitbangda &amp; Seluruh Organisasi Perangkat Daerah (OPD).
            </div>
            <div class="footer-meta">
              <span>Sistem Informasi Geografis Mandiri (GeoMinsel)</span>
              <span>&bull;</span>
              <span>Versi 0.5.2</span>
              <span>&bull;</span>
              <span>WGS84 EPSG:4326</span>
            </div>
          </footer>
        </div>
      </section>
    </div>

    <!-- ============================================================================== -->
    <!-- CINEMATIC BOTTOM HORIZONTAL TIMELINE & CONTROL BAR                             -->
    <!-- ============================================================================== -->
    <div class="horizontal-control-bar">
      <!-- Chapter Counter -->
      <div class="chapter-counter">
        <span class="counter-curr">0{{ activeSection + 1 }}</span>
        <span class="counter-sep">/</span>
        <span class="counter-total">05</span>
      </div>

      <!-- Navigation Step Dots & Titles -->
      <div class="timeline-step-track">
        <button
          v-for="(title, idx) in chapterTitles"
          :key="idx"
          class="timeline-step-btn"
          :class="{ active: activeSection === idx }"
          @click="goToSection(idx)"
          :title="title"
        >
          <span class="step-indicator-dot"></span>
          <span class="step-indicator-title">{{ title.split(' ')[1] }}</span>
        </button>
      </div>

      <!-- Next & Previous Arrow Controls -->
      <div class="step-nav-arrows">
        <button
          class="arrow-btn"
          :disabled="activeSection === 0"
          @click="prevSection"
          title="Bab Sebelumnya (Panah Kiri)"
        >
          <ChevronLeft :size="18" />
        </button>
        <button
          class="arrow-btn"
          :disabled="activeSection === 4"
          @click="nextSection"
          title="Bab Selanjutnya (Panah Kanan)"
        >
          <ChevronRight :size="18" />
        </button>
      </div>
    </div>

    <!-- Glowing Bottom Progress Bar (0% to 100%) -->
    <div class="horizontal-progress-line" :style="{ width: `${currentProgress * 100}%` }"></div>
  </div>
</template>

<style scoped>
.landing-viewport {
  position: relative;
  width: 100vw;
  height: 100vh;
  overflow: hidden;
  background-color: #070d1e;
  color: #f8fafc;
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
  user-select: none;
}

/* Vignette and Dark Gradient Layer */
.viewport-backdrop {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 2;
  background: radial-gradient(circle at 50% 40%, rgba(7, 13, 30, 0.2) 0%, rgba(7, 13, 30, 0.85) 100%);
}

/* Floating Header */
.landing-header {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 68px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  z-index: 30;
  background: linear-gradient(180deg, rgba(7, 13, 30, 0.9) 0%, rgba(7, 13, 30, 0) 100%);
  backdrop-filter: blur(8px);
}

.brand-container {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.brand-emblem {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 20px rgba(37, 99, 235, 0.4);
}

.brand-info {
  display: flex;
  flex-direction: column;
}

.brand-name {
  font-size: 16px;
  font-weight: 800;
  letter-spacing: 1px;
  color: #ffffff;
}

.brand-tag {
  font-size: 11px;
  color: #94a3b8;
}

.landing-nav {
  display: flex;
  align-items: center;
  gap: 4px;
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 4px 6px;
  border-radius: 9999px;
  backdrop-filter: blur(12px);
}

.nav-link {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 12px;
  font-weight: 500;
  padding: 6px 14px;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.nav-link:hover {
  color: #ffffff;
}

.nav-link.active {
  background: rgba(56, 189, 248, 0.18);
  color: #38bdf8;
  font-weight: 700;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

/* Switcher Mode Latar: Foto Nyata vs 3D Digital */
.bg-mode-switcher {
  display: flex;
  align-items: center;
  background: rgba(15, 23, 42, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 9999px;
  padding: 3px;
  backdrop-filter: blur(8px);
}

.mode-switch-btn {
  display: flex;
  align-items: center;
  gap: 5px;
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 600;
  padding: 5px 11px;
  border-radius: 9999px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.mode-switch-btn:hover {
  color: #ffffff;
}

.mode-switch-btn.active {
  background: #0284c7;
  color: #ffffff;
  box-shadow: 0 0 10px rgba(2, 132, 199, 0.5);
}

.btn-portal {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #f8fafc;
  font-size: 13px;
  font-weight: 600;
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-portal:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.2);
}

.btn-login {
  display: flex;
  align-items: center;
  gap: 8px;
  background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
  border: none;
  color: #ffffff;
  font-size: 13px;
  font-weight: 600;
  padding: 8px 18px;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 2px 10px rgba(37, 99, 235, 0.3);
  transition: all 0.2s ease;
}

.btn-login:hover {
  filter: brightness(1.1);
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.45);
}

/* ============================================================================== */
/* HORIZONTAL TRACK & SLIDES (500vw WIDTH)                                        */
/* ============================================================================== */
.horizontal-track {
  position: absolute;
  top: 0;
  left: 0;
  width: 500vw;
  height: 100vh;
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  will-change: transform;
  z-index: 10;
  transition: transform 0.05s linear;
}

.horizontal-slide {
  position: relative;
  width: 100vw;
  height: 100vh;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 80px 48px 90px;
  box-sizing: border-box;
}

/* Slide Wrappers */
.slide-content-wrap {
  position: relative;
  max-height: calc(100vh - 170px);
  display: flex;
  flex-direction: column;
}

.hero-wrap {
  max-width: 860px;
  margin-right: auto;
  margin-left: 5vw;
  gap: 20px;
}

.wide-wrap {
  width: 90vw;
  max-width: 1380px;
  gap: 20px;
}

.cta-wrap {
  max-width: 1100px;
  width: 100%;
  gap: 24px;
  align-items: center;
  text-align: center;
}

/* SECTION 0: HERO */
.pill-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: rgba(14, 165, 233, 0.12);
  border: 1px solid rgba(14, 165, 233, 0.35);
  color: #38bdf8;
  font-size: 12px;
  font-weight: 600;
  padding: 6px 14px;
  border-radius: 9999px;
  width: fit-content;
}

.pill-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #38bdf8;
  box-shadow: 0 0 8px #38bdf8;
  animation: pulseDot 2s infinite;
}

@keyframes pulseDot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.4; transform: scale(1.3); }
}

.hero-title {
  font-size: clamp(32px, 4vw, 54px);
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -0.5px;
  color: #ffffff;
  margin: 0;
}

.text-gradient {
  background: linear-gradient(135deg, #38bdf8 0%, #60a5fa 50%, #c084fc 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-description {
  font-size: 15px;
  line-height: 1.6;
  color: #cbd5e1;
  max-width: 680px;
  margin: 0;
}

.hero-cta-group {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 6px;
}

.btn-hero-primary {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
  color: #ffffff;
  border: none;
  font-size: 14px;
  font-weight: 700;
  padding: 13px 26px;
  border-radius: 10px;
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
  transition: all 0.25s ease;
}

.btn-hero-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(37, 99, 235, 0.6);
}

.btn-hero-secondary {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: rgba(15, 23, 42, 0.7);
  color: #e2e8f0;
  border: 1px solid rgba(255, 255, 255, 0.15);
  font-size: 14px;
  font-weight: 600;
  padding: 13px 24px;
  border-radius: 10px;
  cursor: pointer;
  backdrop-filter: blur(8px);
  transition: all 0.25s ease;
}

.btn-hero-secondary:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.25);
}

.metrics-strip {
  display: flex;
  align-items: center;
  gap: 18px;
  background: rgba(15, 23, 42, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 12px 20px;
  border-radius: 12px;
  backdrop-filter: blur(12px);
  width: fit-content;
  margin-top: 10px;
}

.metric-item {
  display: flex;
  flex-direction: column;
}

.metric-num {
  font-size: 20px;
  font-weight: 800;
  color: #38bdf8;
  letter-spacing: -0.5px;
}

.metric-lbl {
  font-size: 11px;
  color: #94a3b8;
}

.metric-div {
  width: 1px;
  height: 28px;
  background: rgba(255, 255, 255, 0.12);
}

/* Horizontal Scroll Cue on Hero */
.horizontal-scroll-cue {
  position: absolute;
  right: 60px;
  bottom: 110px;
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(15, 23, 42, 0.75);
  border: 1px solid rgba(56, 189, 248, 0.3);
  padding: 10px 18px;
  border-radius: 30px;
  color: #38bdf8;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  backdrop-filter: blur(8px);
  transition: all 0.2s ease;
}

.horizontal-scroll-cue:hover {
  background: rgba(56, 189, 248, 0.15);
  transform: translateX(4px);
}

.icon-pulse-x {
  animation: pulseArrowX 1.5s infinite ease-in-out;
}

@keyframes pulseArrowX {
  0%, 100% { transform: translateX(0); }
  50% { transform: translateX(5px); }
}

/* SLIDE HEADERS */
.panel-header {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.panel-badge-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.panel-kicker {
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  color: #38bdf8;
}

.panel-title {
  font-size: clamp(24px, 2.5vw, 36px);
  font-weight: 800;
  letter-spacing: -0.5px;
  color: #ffffff;
  margin: 0;
}

.panel-sub {
  font-size: 13.5px;
  line-height: 1.5;
  color: #94a3b8;
  max-width: 800px;
  margin: 0;
}

/* ZONE FILTER TABS */
.zone-filter-tabs {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(15, 23, 42, 0.6);
  padding: 4px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.tab-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 11px;
  font-weight: 600;
  padding: 5px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.tab-btn.active {
  background: #0284c7;
  color: #ffffff;
}

/* HORIZONTAL CARDS REEL (17 KECAMATAN) */
.horizontal-cards-reel {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
  max-height: 52vh;
  overflow-y: auto;
  padding-right: 6px;
}

.horizontal-cards-reel::-webkit-scrollbar {
  width: 5px;
}
.horizontal-cards-reel::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 4px;
}

.kec-card {
  background: rgba(15, 23, 42, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 12px 14px;
  cursor: pointer;
  backdrop-filter: blur(8px);
  transition: all 0.2s ease;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.kec-card:hover {
  background: rgba(30, 41, 59, 0.85);
  border-color: rgba(56, 189, 248, 0.4);
  transform: translateY(-2px);
  box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
}

.kec-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.kec-name {
  font-size: 13.5px;
  font-weight: 700;
  color: #ffffff;
}

.kec-type {
  font-size: 10px;
  background: rgba(14, 165, 233, 0.15);
  color: #38bdf8;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 600;
}

.kec-trait {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.35;
  margin: 0;
}

.kec-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10.5px;
  color: #64748b;
  margin-top: 2px;
}

.panel-action-bar {
  display: flex;
  align-items: center;
  margin-top: 4px;
}

.btn-subtle {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  color: #38bdf8;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  transition: all 0.2s;
}

.btn-subtle:hover {
  color: #7dd3fc;
  gap: 12px;
}

/* 5 THEMATIC PILLARS HORIZONTAL ROW */
.pillars-horizontal-row {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 14px;
  width: 100%;
}

.pillar-card {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  overflow: hidden;
  backdrop-filter: blur(10px);
  display: flex;
  flex-direction: column;
  transition: all 0.25s ease;
}

.pillar-card:hover {
  border-color: rgba(255, 255, 255, 0.2);
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
}

.pillar-indicator {
  height: 4px;
  width: 100%;
}

.pillar-body {
  padding: 16px 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex: 1;
}

.pillar-opd {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 10.5px;
  color: #94a3b8;
  font-weight: 500;
}

.pillar-title {
  font-size: 14px;
  font-weight: 700;
  color: #ffffff;
  line-height: 1.3;
  margin: 0;
}

.pillar-cat-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.pillar-cat-list li {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #cbd5e1;
}

.cat-bullet {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #38bdf8;
  flex-shrink: 0;
}

.pillar-geom {
  margin-top: auto;
  font-size: 10.5px;
  color: #64748b;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 8px;
}

/* 5-STEP HORIZONTAL PIPELINE */
.pipeline-track-horizontal {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
}

.pipeline-step {
  flex: 1;
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 14px 12px;
  backdrop-filter: blur(8px);
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.highlight-step {
  border-color: #0284c7;
  background: rgba(2, 132, 199, 0.12);
  box-shadow: 0 0 16px rgba(2, 132, 199, 0.25);
}

.step-num {
  font-size: 11px;
  font-weight: 800;
  color: #38bdf8;
  letter-spacing: 1px;
}

.step-title {
  font-size: 13px;
  font-weight: 700;
  color: #ffffff;
}

.step-actor {
  font-size: 10.5px;
  color: #60a5fa;
  font-weight: 600;
}

.step-desc {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.35;
  margin: 4px 0 0;
}

.pipeline-arrow {
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.roles-ribbon {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 6px;
}

.role-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 5px 10px;
  border-radius: 6px;
  font-size: 11px;
  color: #cbd5e1;
  cursor: help;
}

.role-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #38bdf8;
}

.role-name {
  font-weight: 600;
  color: #ffffff;
}

.role-opd {
  font-size: 10px;
  color: #64748b;
}

/* ACCESS BOXES (SLIDE 4) */
.access-boxes-horizontal {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  width: 100%;
  max-width: 900px;
}

.access-box {
  background: rgba(15, 23, 42, 0.75);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 28px 24px;
  backdrop-filter: blur(12px);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 12px;
  text-align: left;
  transition: all 0.25s ease;
}

.access-box:hover {
  transform: translateY(-4px);
  border-color: rgba(56, 189, 248, 0.4);
  box-shadow: 0 12px 32px rgba(2, 132, 199, 0.25);
  background: rgba(30, 41, 59, 0.85);
}

.box-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-cyan {
  background: rgba(14, 165, 233, 0.15);
  color: #38bdf8;
  border: 1px solid rgba(14, 165, 233, 0.3);
}

.icon-amber {
  background: rgba(245, 158, 11, 0.15);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.box-title {
  font-size: 18px;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
}

.box-desc {
  font-size: 12.5px;
  line-height: 1.5;
  color: #94a3b8;
  margin: 0;
  flex: 1;
}

.box-action {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: #38bdf8;
  margin-top: 8px;
}

.landing-footer {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 11.5px;
  color: #64748b;
  text-align: center;
}

.footer-copy strong {
  color: #cbd5e1;
}

.footer-meta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 11px;
  color: #475569;
}

/* ============================================================================== */
/* CINEMATIC BOTTOM HORIZONTAL TIMELINE & CONTROLS                                */
/* ============================================================================== */
.horizontal-control-bar {
  position: absolute;
  bottom: 18px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 30;
  display: flex;
  align-items: center;
  gap: 20px;
  background: rgba(15, 23, 42, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 8px 18px;
  border-radius: 9999px;
  backdrop-filter: blur(14px);
  box-shadow: 0 8px 28px rgba(0, 0, 0, 0.45);
}

.chapter-counter {
  display: flex;
  align-items: baseline;
  gap: 4px;
  font-variant-numeric: tabular-nums;
}

.counter-curr {
  font-size: 15px;
  font-weight: 800;
  color: #38bdf8;
}

.counter-sep {
  font-size: 11px;
  color: #475569;
}

.counter-total {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
}

.timeline-step-track {
  display: flex;
  align-items: center;
  gap: 12px;
}

.timeline-step-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 6px;
  transition: all 0.2s;
}

.step-indicator-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #334155;
  transition: all 0.25s ease;
}

.step-indicator-title {
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  transition: color 0.2s;
}

.timeline-step-btn.active .step-indicator-dot {
  background: #38bdf8;
  box-shadow: 0 0 8px #38bdf8;
  transform: scale(1.3);
}

.timeline-step-btn.active .step-indicator-title {
  color: #ffffff;
}

.step-nav-arrows {
  display: flex;
  align-items: center;
  gap: 4px;
  border-left: 1px solid rgba(255, 255, 255, 0.1);
  padding-left: 12px;
}

.arrow-btn {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #f8fafc;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.arrow-btn:hover:not(:disabled) {
  background: #0284c7;
  color: #ffffff;
  border-color: #38bdf8;
}

.arrow-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

/* Glowing Progress Line at Very Bottom */
.horizontal-progress-line {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: linear-gradient(90deg, #0284c7, #38bdf8, #818cf8);
  box-shadow: 0 0 10px #38bdf8;
  z-index: 40;
  transition: width 0.05s linear;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
  .pillars-horizontal-row {
    grid-template-columns: repeat(3, 1fr);
  }
  .pipeline-track-horizontal {
    flex-direction: column;
  }
  .pipeline-arrow {
    transform: rotate(90deg);
  }
}
</style>
