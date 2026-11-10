<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import MinselTerrainCanvas from '../components/MinselTerrainCanvas.vue';
import {
  Compass,
  Layers,
  ShieldCheck,
  ArrowRight,
  Database,
  Sparkles,
  ChevronDown
} from 'lucide-vue-next';

const emit = defineEmits<{
  (e: 'navigate', view: 'geoportal' | 'dashboard' | 'landing'): void;
  (e: 'open-login'): void;
}>();

const scrollProgress = ref(0);
const activeSection = ref(0);
const scrollContainer = ref<HTMLDivElement | null>(null);

function handleScroll() {
  if (!scrollContainer.value) return;
  const scrollTop = scrollContainer.value.scrollTop;
  const scrollHeight = scrollContainer.value.scrollHeight - scrollContainer.value.clientHeight;
  if (scrollHeight > 0) {
    const p = Math.max(0, Math.min(1, scrollTop / scrollHeight));
    scrollProgress.value = p;
    activeSection.value = Math.min(4, Math.floor(p * 5));
  }
}

function scrollToSection(index: number) {
  if (!scrollContainer.value) return;
  const sections = scrollContainer.value.querySelectorAll('.scrolly-section');
  if (sections[index]) {
    sections[index].scrollIntoView({ behavior: 'smooth' });
  }
}

onMounted(() => {
  if (scrollContainer.value) {
    scrollContainer.value.addEventListener('scroll', handleScroll, { passive: true });
  }
});

onBeforeUnmount(() => {
  if (scrollContainer.value) {
    scrollContainer.value.removeEventListener('scroll', handleScroll);
  }
});

// 17 Kecamatan Resmi Minahasa Selatan
const kecamatanList = [
  { nama: 'Amurang', tipe: 'Ibu Kota Kabupaten', luas: '105.7 km²', desa: 8, trait: 'Pusat Pemerintahan & Teluk Amurang' },
  { nama: 'Amurang Barat', tipe: 'Penyangga Pesisir', luas: '87.4 km²', desa: 10, trait: 'Kawasan Pesisir & Perikanan' },
  { nama: 'Amurang Timur', tipe: 'Kawasan Aglomerasi', luas: '94.2 km²', desa: 10, trait: 'Akses Poros Trans Sulawesi' },
  { nama: 'Tumpaan', tipe: 'Gerbang Utara', luas: '76.1 km²', desa: 10, trait: 'Kawasan Perdagangan & Jasa' },
  { nama: 'Tareran', tipe: 'Kawasan Perbukitan', luas: '82.5 km²', desa: 13, trait: 'Perkebunan Kelapa & Aren' },
  { nama: 'Suluun Tareran', tipe: 'Dataran Menengah', luas: '61.3 km²', desa: 9, trait: 'Sentra Gula Aren & Holtikultura' },
  { nama: 'Modoinding', tipe: 'Dataran Tinggi Subur', luas: '52.6 km²', desa: 10, trait: 'Dapur Sayur Indonesia Timur' },
  { nama: 'Tompaso Baru', tipe: 'Sentra Pangan', luas: '112.8 km²', desa: 10, trait: 'Lahan Pertanian & Holtikultura' },
  { nama: 'Maesaan', tipe: 'Kawasan Lembah', luas: '88.3 km²', desa: 12, trait: 'Pertanian Terpadu & Perkebunan' },
  { nama: 'Ranoyapo', tipe: 'Lembah Sungai Utama', luas: '96.4 km²', desa: 12, trait: 'DAS Ranoyapo & Hutan Lindung' },
  { nama: 'Motoling', tipe: 'Pusat Kawasan Tengah', luas: '64.9 km²', desa: 7, trait: 'Pendidikan & Perdagangan Lokal' },
  { nama: 'Motoling Barat', tipe: 'Kawasan Agrowisata', luas: '71.2 km²', desa: 8, trait: 'Perkebunan Cengkih & Kopi' },
  { nama: 'Motoling Timur', tipe: 'Perbukitan Hijau', luas: '68.5 km²', desa: 8, trait: 'Konservasi & Perkebunan Rakyat' },
  { nama: 'Kumelembuai', tipe: 'Kawasan Perkebunan', luas: '54.7 km²', desa: 8, trait: 'Komoditas Cengkih Minsel' },
  { nama: 'Sinonsayang', tipe: 'Pesisir Barat Daya', luas: '134.2 km²', desa: 13, trait: 'Perikanan Tangkap & Kelapa' },
  { nama: 'Tenga', tipe: 'Kawasan Strategis Pesisir', luas: '122.6 km²', desa: 18, trait: 'Pertanian Padi & Perikanan' },
  { nama: 'Tatapaan', tipe: 'Pesisir Utara & Mangrove', luas: '85.4 km²', desa: 11, trait: 'Konservasi Mangrove & Tambak' }
];

// 5 Pilar Tematik dari 28 Kategori Geospasial SDI
const thematicPillars = [
  {
    title: 'Batas & Administrasi Wilayah',
    opd: 'Bagian Tata Pemerintahan & Setda',
    categories: ['Batas Kabupaten', 'Batas 17 Kecamatan', 'Batas 167 Desa/Kel', 'Kode Administrasi Kemendagri'],
    geom: 'MultiPolygon',
    color: '#38bdf8'
  },
  {
    title: 'Infrastruktur & Pekerjaan Umum',
    opd: 'Dinas PUPR & Dinas Perkim',
    categories: ['Jalan Poros Trans Sulawesi', 'Jaringan Irigasi Modoinding', 'Jembatan & Drainase', 'Air Bersih'],
    geom: 'LineString / Point',
    color: '#f59e0b'
  },
  {
    title: 'Pertanian & Ketahanan Pangan',
    opd: 'Dinas Pertanian & Dinas Perkebunan',
    categories: ['Sentra Holtikultura Sayur', 'Lahan Sawah Dilindungi (LSD)', 'Perkebunan Cengkih & Kelapa'],
    geom: 'Polygon',
    color: '#10b981'
  },
  {
    title: 'Tata Ruang (RTRW) & Lingkungan',
    opd: 'Bappelitbangda & Dinas Lingkungan Hidup',
    categories: ['Pola Ruang RTRW 2024-2044', 'Kawasan Lindung & Hutan', 'Ruang Terbuka Hijau (RTH)'],
    geom: 'Polygon',
    color: '#8b5cf6'
  },
  {
    title: 'Kebencanaan & Fasilitas Publik',
    opd: 'BPBD, Dinas Kesehatan & Dikbud',
    categories: ['Zona Bahaya Soputan', 'Rawan Longsor Ranoyapo', 'Puskesmas & Rumah Sakit', 'Sekolah'],
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
</script>

<template>
  <div class="landing-viewport">
    <!-- Sticky Fullscreen 3D Topographic Scene -->
    <MinselTerrainCanvas :scroll-progress="scrollProgress" :active-section="activeSection" />

    <!-- Subtle Vignette & Gradient Overlays for High Legibility -->
    <div class="viewport-backdrop"></div>

    <!-- Floating Top Header -->
    <header class="landing-header">
      <div class="brand-container" @click="scrollToSection(0)">
        <div class="brand-emblem">
          <img src="/favicon.svg" alt="Logo Minahasa Selatan" width="30" height="30" />
        </div>
        <div class="brand-info">
          <span class="brand-name">GEOMINSEL</span>
          <span class="brand-tag">Kabupaten Minahasa Selatan</span>
        </div>
      </div>

      <nav class="landing-nav">
        <button class="nav-link" :class="{ active: activeSection === 0 }" @click="scrollToSection(0)">Beranda</button>
        <button class="nav-link" :class="{ active: activeSection === 1 }" @click="scrollToSection(1)">17 Kecamatan</button>
        <button class="nav-link" :class="{ active: activeSection === 2 }" @click="scrollToSection(2)">Katalog Data</button>
        <button class="nav-link" :class="{ active: activeSection === 3 }" @click="scrollToSection(3)">Tata Kelola</button>
        <button class="nav-link" :class="{ active: activeSection === 4 }" @click="scrollToSection(4)">Akses Portal</button>
      </nav>

      <div class="header-actions">
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

    <!-- Scrollable Content Track (Scrollytelling Flow) -->
    <div ref="scrollContainer" class="scrolly-container">
      <!-- ============================================== -->
      <!-- SECTION 0: HERO (HIGH-ALTITUDE OVERVIEW)       -->
      <!-- ============================================== -->
      <section class="scrolly-section section-hero">
        <div class="hero-content">
          <div class="pill-badge">
            <span class="pill-dot"></span>
            <span>Geoportal Resmi Satu Data Spasial &bull; Standar WGS84 EPSG:4326</span>
          </div>

          <h1 class="hero-title">
            Infrastruktur Geospasial Resmi <br />
            <span class="text-gradient">Kabupaten Minahasa Selatan</span>
          </h1>

          <p class="hero-description">
            Platform terpadu pengelolaan data geospasial mandiri Pemerintah Kabupaten Minahasa Selatan. 
            Menghubungkan 17 Kecamatan, 167 Desa, dan 10 Kelurahan dalam satu standar referensi spasial 
            dengan kepemilikan data sektoral OPD dan tata kelola persetujuan terpusat.
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
              <span class="metric-lbl">Desa / Kelurahan</span>
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

        <button class="scroll-indicator" @click="scrollToSection(1)">
          <span>Gulir untuk eksplorasi wilayah</span>
          <ChevronDown :size="18" />
        </button>
      </section>

      <!-- ============================================== -->
      <!-- SECTION 1: HIERARKI 3 TINGKAT 17 KECAMATAN     -->
      <!-- ============================================== -->
      <section class="scrolly-section section-kecamatan">
        <div class="section-card-panel">
          <div class="panel-header">
            <span class="panel-kicker">Hierarki Administrasi Utama</span>
            <h2 class="panel-title">17 Wilayah Kecamatan Terhubung</h2>
            <p class="panel-sub">
              Struktur spasial berjenjang: <strong>Kabupaten Minahasa Selatan</strong> sebagai induk, 
              terbagi ke dalam <strong>17 Kecamatan</strong>, dan menaungi <strong>167 Desa & 10 Kelurahan</strong>.
              Setiap objek spasial sektoral wajib terikat pada kode wilayah hierarki resmi.
            </p>
          </div>

          <div class="kecamatan-grid">
            <div
              v-for="kec in kecamatanList"
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

          <div class="panel-footer-action">
            <button class="btn-subtle" @click="emit('navigate', 'geoportal')">
              <span>Eksplorasi batas polygon 17 kecamatan di peta interaktif</span>
              <ArrowRight :size="16" />
            </button>
          </div>
        </div>
      </section>

      <!-- ============================================== -->
      <!-- SECTION 2: 28 KATEGORI SATU DATA INDONESIA     -->
      <!-- ============================================== -->
      <section class="scrolly-section section-categories">
        <div class="section-card-panel">
          <div class="panel-header">
            <span class="panel-kicker">Standar Satu Data Indonesia</span>
            <h2 class="panel-title">5 Pilar Tematik & 28 Kategori Spasial</h2>
            <p class="panel-sub">
              Dari sentra hortikultura dataran tinggi Modoinding hingga pelabuhan pesisir Teluk Amurang, 
              seluruh data geospasial sektoral diklasifikasikan berdasarkan standar nasional dengan metadata SNI/ISO.
            </p>
          </div>

          <div class="pillars-grid">
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
      <!-- SECTION 3: TATA KELOLA 8 ROLE & ALUR PERSETUJUAN -->
      <!-- ============================================== -->
      <section class="scrolly-section section-governance">
        <div class="section-card-panel">
          <div class="panel-header">
            <span class="panel-kicker">Tata Kelola & Validasi Data</span>
            <h2 class="panel-title">Alur Persetujuan Terpusat (Central Approval)</h2>
            <p class="panel-sub">
              Tidak ada data yang langsung terbit ke publik tanpa verifikasi. SIG Minahasa Selatan menerapkan 
              prinsip <em>Separation of Duties</em> dan <em>Least Privilege</em> lintas 8 role resmi.
            </p>
          </div>

          <!-- 5-Step Visual Pipeline -->
          <div class="pipeline-track">
            <div class="pipeline-step">
              <div class="step-num">01</div>
              <div class="step-title">Penginputan Data</div>
              <div class="step-actor">Operator OPD</div>
              <p class="step-desc">Input objek spasial & koordinat WGS84 sesuai urusan sektoral dinas.</p>
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
              <p class="step-desc">Uji topologi geometri, bebas tumpang tindih, dan kesesuaian CRS EPSG:4326.</p>
            </div>
            <div class="pipeline-arrow"><ArrowRight :size="16" /></div>
            <div class="pipeline-step step-highlight">
              <div class="step-num">04</div>
              <div class="step-title">Persetujuan Terpusat</div>
              <div class="step-actor">Approver Data (Bappelitbangda)</div>
              <p class="step-desc">Penilaian keselarasan dengan Satu Data Indonesia dan kebijakan daerah.</p>
            </div>
            <div class="pipeline-arrow"><ArrowRight :size="16" /></div>
            <div class="pipeline-step">
              <div class="step-num">05</div>
              <div class="step-title">Publikasi Geoportal</div>
              <div class="step-actor">Admin GIS</div>
              <p class="step-desc">Penerbitan layer ke katalog publik geoportal sesuai klasifikasi data terbuka.</p>
            </div>
          </div>

          <!-- 8 Roles Grid -->
          <div class="roles-roster">
            <div v-for="r in officialRolesList" :key="r.role" class="role-pill-card">
              <div class="rpc-top">
                <span class="rpc-role">{{ r.role }}</span>
                <span class="rpc-opd">{{ r.opd }}</span>
              </div>
              <p class="rpc-desc">{{ r.desc }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- ============================================== -->
      <!-- SECTION 4: CALL TO ACTION & AKSES PORTAL       -->
      <!-- ============================================== -->
      <section class="scrolly-section section-cta">
        <div class="cta-card">
          <div class="cta-sparkle">
            <Sparkles :size="28" />
          </div>
          <h2 class="cta-title">Akses Geoportal & Pengelolaan Data Spasial</h2>
          <p class="cta-sub">
            Pilih jalur akses sesuai kebutuhan: Jelajahi data terbuka sebagai publik atau masuk ke dashboard 
            untuk pengelolaan dataset sektoral dan persetujuan data.
          </p>

          <div class="cta-cards-duo">
            <div class="access-box" @click="emit('navigate', 'geoportal')">
              <div class="box-icon icon-blue">
                <Compass :size="28" />
              </div>
              <h3 class="box-title">Geoportal Publik Interaktif</h3>
              <p class="box-desc">
                Akses bebas untuk seluruh masyarakat, akademisi, dan investor. Jelajahi peta tematik 17 kecamatan, 
                filter batas wilayah berjenjang, dan lihat informasi spasial terbuka.
              </p>
              <div class="box-action">
                <span>Buka Peta Interaktif</span>
                <ArrowRight :size="16" />
              </div>
            </div>

            <div class="access-box" @click="emit('open-login')">
              <div class="box-icon icon-amber">
                <ShieldCheck :size="28" />
              </div>
              <h3 class="box-title">Dashboard Pengguna & OPD</h3>
              <p class="box-desc">
                Portal terproteksi untuk 8 Role: Superadmin, Admin GIS, Admin OPD, Operator, Validator, 
                Approver Walidata, dan Pimpinan Daerah.
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
              bekerjasama dengan Bappelitbangda & Seluruh Perangkat Daerah se-Minahasa Selatan.
            </div>
            <div class="footer-meta">
              <span>Sistem Informasi Geografis Mandiri (GeoMinsel)</span>
              <span>&bull;</span>
              <span>Versi 0.4.1</span>
              <span>&bull;</span>
              <span>WGS84 EPSG:4326</span>
            </div>
          </footer>
        </div>
      </section>
    </div>
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
  background: radial-gradient(circle at 50% 30%, rgba(7, 13, 30, 0.25) 0%, rgba(7, 13, 30, 0.85) 100%);
}

/* Floating Header */
.landing-header {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 72px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  z-index: 20;
  background: linear-gradient(180deg, rgba(7, 13, 30, 0.85) 0%, rgba(7, 13, 30, 0) 100%);
  backdrop-filter: blur(8px);
}

.brand-container {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.brand-emblem {
  width: 42px;
  height: 42px;
  border-radius: 12px;
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
  gap: 8px;
  background: rgba(15, 23, 42, 0.65);
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
  background: rgba(56, 189, 248, 0.15);
  color: #38bdf8;
  font-weight: 600;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
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

/* Scrollytelling Track */
.scrolly-container {
  position: relative;
  width: 100%;
  height: 100%;
  overflow-y: auto;
  scroll-behavior: smooth;
  z-index: 10;
}

.scrolly-container::-webkit-scrollbar {
  width: 6px;
}
.scrolly-container::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 3px;
}

.scrolly-section {
  position: relative;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 96px 48px;
  box-sizing: border-box;
}

/* SECTION 0: HERO */
.section-hero {
  align-items: flex-start;
  justify-content: center;
  max-width: 900px;
  margin-left: 5vw;
}

.hero-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

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
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: #38bdf8;
  box-shadow: 0 0 6px #38bdf8;
}

.hero-title {
  font-size: 44px;
  font-weight: 800;
  line-height: 1.15;
  letter-spacing: -0.5px;
  color: #ffffff;
}

.text-gradient {
  background: linear-gradient(135deg, #38bdf8 0%, #60a5fa 50%, #facc15 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-description {
  font-size: 16px;
  line-height: 1.65;
  color: #cbd5e1;
  max-width: 720px;
}

.hero-cta-group {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 10px;
}

.btn-hero-primary {
  display: flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
  color: #ffffff;
  border: none;
  font-size: 15px;
  font-weight: 700;
  padding: 14px 28px;
  border-radius: 10px;
  cursor: pointer;
  box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4);
  transition: all 0.2s ease;
}

.btn-hero-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(37, 99, 235, 0.55);
}

.btn-hero-secondary {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: #f8fafc;
  font-size: 15px;
  font-weight: 600;
  padding: 14px 24px;
  border-radius: 10px;
  cursor: pointer;
  backdrop-filter: blur(8px);
  transition: all 0.2s ease;
}

.btn-hero-secondary:hover {
  background: rgba(255, 255, 255, 0.1);
}

.metrics-strip {
  display: flex;
  align-items: center;
  gap: 20px;
  margin-top: 24px;
  background: rgba(15, 23, 42, 0.65);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  padding: 16px 24px;
  width: fit-content;
  backdrop-filter: blur(12px);
}

.metric-item {
  display: flex;
  flex-direction: column;
}

.metric-num {
  font-size: 20px;
  font-weight: 800;
  color: #38bdf8;
  font-family: 'JetBrains Mono', monospace;
}

.metric-lbl {
  font-size: 11px;
  color: #94a3b8;
  margin-top: 2px;
}

.metric-div {
  width: 1px;
  height: 28px;
  background: rgba(255, 255, 255, 0.1);
}

.scroll-indicator {
  position: absolute;
  bottom: 28px;
  left: 5vw;
  display: flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  color: #94a3b8;
  font-size: 12px;
  cursor: pointer;
  animation: bounce 2.2s infinite ease-in-out;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(6px); }
}

/* SECTION CARDS COMMON LAYOUT */
.section-card-panel {
  max-width: 1180px;
  margin: 0 auto;
  width: 100%;
  background: rgba(11, 20, 42, 0.82);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(16px);
}

.panel-header {
  margin-bottom: 28px;
}

.panel-kicker {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: #38bdf8;
}

.panel-title {
  font-size: 28px;
  font-weight: 800;
  margin: 8px 0 10px 0;
  color: #ffffff;
}

.panel-sub {
  font-size: 14px;
  line-height: 1.6;
  color: #94a3b8;
  max-width: 820px;
}

/* SECTION 1: KECAMATAN GRID */
.kecamatan-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 14px;
  max-height: 480px;
  overflow-y: auto;
  padding-right: 6px;
}

.kecamatan-grid::-webkit-scrollbar {
  width: 4px;
}
.kecamatan-grid::-webkit-scrollbar-thumb {
  background: #334155;
  border-radius: 2px;
}

.kec-card {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 14px 16px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.kec-card:hover {
  background: rgba(30, 41, 59, 0.9);
  border-color: rgba(56, 189, 248, 0.4);
  transform: translateY(-2px);
}

.kec-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 6px;
}

.kec-name {
  font-size: 14px;
  font-weight: 700;
  color: #f8fafc;
}

.kec-type {
  font-size: 10px;
  color: #38bdf8;
  background: rgba(56, 189, 248, 0.12);
  padding: 2px 6px;
  border-radius: 4px;
}

.kec-trait {
  font-size: 12px;
  color: #cbd5e1;
  margin-bottom: 10px;
}

.kec-meta {
  font-size: 11px;
  color: #64748b;
  display: flex;
  gap: 6px;
}

.panel-footer-action {
  margin-top: 24px;
  display: flex;
  justify-content: flex-end;
}

.btn-subtle {
  display: flex;
  align-items: center;
  gap: 8px;
  background: transparent;
  border: none;
  color: #38bdf8;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

/* SECTION 2: PILLARS */
.pillars-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 16px;
}

.pillar-card {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.pillar-indicator {
  height: 4px;
  width: 100%;
}

.pillar-body {
  padding: 18px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.pillar-opd {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #94a3b8;
  margin-bottom: 8px;
}

.pillar-title {
  font-size: 15px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 12px;
  line-height: 1.3;
}

.pillar-cat-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 6px;
  margin-bottom: 16px;
  flex: 1;
}

.pillar-cat-list li {
  font-size: 12px;
  color: #cbd5e1;
  display: flex;
  align-items: center;
  gap: 8px;
}

.cat-bullet {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #38bdf8;
}

.pillar-geom {
  font-size: 11px;
  color: #64748b;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 10px;
}

/* SECTION 3: GOVERNANCE & PIPELINE */
.pipeline-track {
  display: flex;
  align-items: stretch;
  gap: 8px;
  margin-bottom: 32px;
  overflow-x: auto;
  padding-bottom: 8px;
}

.pipeline-step {
  flex: 1;
  min-width: 160px;
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.pipeline-step.step-highlight {
  border-color: rgba(245, 158, 11, 0.5);
  background: rgba(245, 158, 11, 0.08);
}

.step-num {
  font-size: 11px;
  font-weight: 800;
  color: #38bdf8;
  font-family: 'JetBrains Mono', monospace;
}

.step-title {
  font-size: 13px;
  font-weight: 700;
  color: #ffffff;
}

.step-actor {
  font-size: 11px;
  font-weight: 600;
  color: #f59e0b;
}

.step-desc {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.4;
}

.pipeline-arrow {
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
}

.roles-roster {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 12px;
}

.role-pill-card {
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  padding: 12px 14px;
}

.rpc-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 4px;
}

.rpc-role {
  font-size: 13px;
  font-weight: 700;
  color: #f8fafc;
}

.rpc-opd {
  font-size: 10px;
  color: #38bdf8;
}

.rpc-desc {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.4;
}

/* SECTION 4: CALL TO ACTION */
.section-cta {
  align-items: center;
  justify-content: center;
}

.cta-card {
  max-width: 1080px;
  width: 100%;
  background: rgba(11, 20, 42, 0.9);
  border: 1px solid rgba(56, 189, 248, 0.25);
  border-radius: 24px;
  padding: 56px 48px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(20px);
}

.cta-sparkle {
  color: #f59e0b;
  margin-bottom: 12px;
}

.cta-title {
  font-size: 36px;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 12px;
}

.cta-sub {
  font-size: 15px;
  color: #94a3b8;
  max-width: 720px;
  margin-bottom: 40px;
  line-height: 1.6;
}

.cta-cards-duo {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 24px;
  width: 100%;
  margin-bottom: 48px;
}

.access-box {
  background: rgba(15, 23, 42, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  padding: 32px 28px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-align: left;
  cursor: pointer;
  transition: all 0.25s ease;
}

.access-box:hover {
  border-color: rgba(56, 189, 248, 0.5);
  background: rgba(30, 41, 59, 0.85);
  transform: translateY(-4px);
  box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
}

.box-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.icon-blue {
  background: rgba(2, 132, 199, 0.2);
  color: #38bdf8;
  border: 1px solid rgba(2, 132, 199, 0.4);
}

.icon-amber {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.4);
}

.box-title {
  font-size: 18px;
  font-weight: 700;
  color: #ffffff;
  margin-bottom: 8px;
}

.box-desc {
  font-size: 13px;
  color: #94a3b8;
  line-height: 1.55;
  margin-bottom: 20px;
  flex: 1;
}

.box-action {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #38bdf8;
  font-size: 13px;
  font-weight: 600;
}

.landing-footer {
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  padding-top: 24px;
  width: 100%;
  display: flex;
  flex-direction: column;
  gap: 8px;
  font-size: 12px;
  color: #64748b;
}

.footer-copy strong {
  color: #94a3b8;
}

.footer-meta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

/* Responsiveness */
@media (max-width: 900px) {
  .hero-title {
    font-size: 32px;
  }
  .cta-cards-duo {
    grid-template-columns: 1fr;
  }
  .pipeline-track {
    flex-direction: column;
  }
  .pipeline-arrow {
    transform: rotate(90deg);
  }
  .landing-nav {
    display: none;
  }
}
</style>
