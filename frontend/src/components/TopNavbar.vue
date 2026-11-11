<script setup lang="ts">
import { useWilayahStore } from '../stores/wilayahStore';
import { useAuthStore } from '../stores/authStore';
import { useThemeStore } from '../stores/themeStore';
import { ShieldCheck, Compass, LayoutDashboard, BarChart3, Sun, Moon } from 'lucide-vue-next';

const emit = defineEmits<{
  (e: 'navigate', view: 'geoportal' | 'dashboard'): void;
  (e: 'open-login'): void;
  (e: 'open-stats'): void;
}>();

const wilayahStore = useWilayahStore();
const authStore = useAuthStore();
const themeStore = useThemeStore();
</script>

<template>
  <header class="top-nav">
    <div class="nav-brand" @click="emit('navigate', 'geoportal')">
      <div class="brand-icon">
        <img
          src="/images/Lambang_Kabupaten_Minahasa_Selatan.png"
          alt="Lambang Resmi Kabupaten Minahasa Selatan"
          class="brand-img"
          width="30"
          height="38"
        />
      </div>
      <div class="brand-text">
        <h1 class="brand-title">GEOMINSEL</h1>
        <p class="brand-subtitle">Pemerintah Kabupaten Minahasa Selatan &bull; Geoportal & Pengelolaan Data</p>
      </div>
    </div>

    <!-- Navigation Tab Buttons -->
    <div class="nav-middle">
      <button class="nav-tab-btn active" @click="emit('navigate', 'geoportal')">
        <Compass :size="15" />
        <span>Peta Interaktif</span>
      </button>
      <button class="nav-tab-btn" @click="emit('open-stats')" title="Buka Ringkasan Statistik Spasial Wilayah">
        <BarChart3 :size="15" />
        <span>Statistik Wilayah</span>
      </button>
      <button
        v-if="authStore.isAuthenticated"
        class="nav-tab-btn"
        @click="emit('navigate', 'dashboard')"
      >
        <LayoutDashboard :size="15" />
        <span>Dashboard ({{ authStore.roleLabel }})</span>
      </button>
    </div>

    <div class="nav-status">
      <!-- Active Hierarchy Badge -->
      <div class="badge-scope">
        <span class="badge-dot"></span>
        <span class="badge-text">{{ wilayahStore.currentScopeLabel }}</span>
      </div>

      <!-- Token Security Badge -->
      <div class="badge-auth" title="Akses terproteksi token resmi frontend">
        <ShieldCheck :size="16" class="icon-shield" />
        <span>API Token Valid</span>
      </div>

      <!-- Theme Switcher (Hitam Putih Elegan: Light / Dark Toggle) -->
      <button
        type="button"
        class="btn-theme-toggle"
        @click="themeStore.toggleTheme"
        :title="themeStore.isDark ? 'Ganti ke Mode Terang (Light Mode)' : 'Ganti ke Mode Gelap (Dark Mode)'"
      >
        <Sun v-if="themeStore.isDark" :size="15" class="icon-sun" />
        <Moon v-else :size="15" class="icon-moon" />
        <span class="theme-text">{{ themeStore.isDark ? 'Gelap' : 'Terang' }}</span>
      </button>

      <!-- Auth Action Button -->
      <button
        v-if="!authStore.isAuthenticated"
        class="btn-login-nav"
        @click="emit('open-login')"
      >
        <ShieldCheck :size="15" />
        <span>Masuk OPD</span>
      </button>
      <button
        v-else
        class="btn-dash-nav"
        @click="emit('navigate', 'dashboard')"
      >
        <LayoutDashboard :size="15" />
        <span>Buka Dashboard</span>
      </button>
    </div>
  </header>
</template>

<style scoped>
.top-nav {
  height: 64px;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  z-index: 20;
}

.nav-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.brand-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  border: 1px solid rgba(245, 158, 11, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
  padding: 3px;
  flex-shrink: 0;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.nav-brand:hover .brand-icon {
  transform: scale(1.04);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
}

.brand-img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.35));
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.brand-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  letter-spacing: 0.5px;
  color: #0f172a;
}

.brand-subtitle {
  margin: 0;
  font-size: 11px;
  color: #64748b;
}

.nav-middle {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #f1f5f9;
  padding: 4px;
  border-radius: 8px;
}

.nav-tab-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: none;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.nav-tab-btn:hover {
  color: #0f172a;
}

.nav-tab-btn.active {
  background: #ffffff;
  color: #2563eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.nav-status {
  display: flex;
  align-items: center;
  gap: 12px;
}

.badge-scope {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  color: #334155;
}

.badge-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #2563eb;
}

.badge-auth {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  color: #065f46;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.icon-shield {
  color: #059669;
}

.btn-login-nav, .btn-dash-nav {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #2563eb;
  color: #ffffff;
  border: none;
  font-size: 12px;
  font-weight: 600;
  padding: 6px 14px;
  border-radius: 6px;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
  transition: all 0.15s ease;
}

.btn-login-nav:hover, .btn-dash-nav:hover {
  background: #1d4ed8;
}

@media (max-width: 900px) {
  .nav-middle, .badge-scope {
    display: none;
  }
}
</style>
