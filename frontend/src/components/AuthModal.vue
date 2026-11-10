<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '../stores/authStore';
import type { RoleName } from '../types';
import {
  ShieldAlert,
  MapPin,
  Building2,
  Layers,
  CheckCheck,
  FileCheck2,
  Crown,
  Globe,
  X,
  Lock,
  User,
  ArrowRight
} from 'lucide-vue-next';

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'success'): void;
}>();

const authStore = useAuthStore();

const selectedRole = ref<RoleName>('superadmin');
const username = ref('superadmin_minsel');
const password = ref('••••••••');
const isSubmitting = ref(false);

const rolesConfig: { role: RoleName; label: string; opd: string; icon: any; color: string }[] = [
  { role: 'superadmin', label: 'Superadmin', opd: 'Diskominfo Minsel', icon: ShieldAlert, color: '#ef4444' },
  { role: 'admin_gis', label: 'Admin GIS', opd: 'Diskominfo Minsel', icon: MapPin, color: '#3b82f6' },
  { role: 'admin_opd', label: 'Admin OPD', opd: 'Dinas PUPR', icon: Building2, color: '#0ea5e9' },
  { role: 'operator_opd', label: 'Operator OPD', opd: 'Dinas Pertanian', icon: Layers, color: '#10b981' },
  { role: 'validator_gis', label: 'Validator GIS', opd: 'Tim Teknis GIS', icon: CheckCheck, color: '#8b5cf6' },
  { role: 'approver_data', label: 'Approver Data', opd: 'Bappelitbangda', icon: FileCheck2, color: '#f59e0b' },
  { role: 'pimpinan', label: 'Pimpinan', opd: 'Bupati / Sekda', icon: Crown, color: '#ec4899' },
  { role: 'publik', label: 'Publik', opd: 'Masyarakat', icon: Globe, color: '#64748b' }
];

function selectRole(r: RoleName) {
  selectedRole.value = r;
  const preset = authStore.personaPresets[r];
  if (preset) {
    username.value = preset.username;
    password.value = '••••••••';
  }
}

async function handleLogin() {
  isSubmitting.value = true;
  try {
    await authStore.login(selectedRole.value, username.value, password.value);
    emit('success');
  } catch (e) {
    console.error(e);
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <div class="auth-modal-overlay" @click.self="emit('close')">
    <div class="auth-card">
      <button class="modal-close-btn" @click="emit('close')">
        <X :size="20" />
      </button>

      <div class="auth-header">
        <div class="auth-emblem">
          <img src="/favicon.svg" alt="Logo Minahasa Selatan" width="32" height="32" />
        </div>
        <h2 class="auth-title">Autentikasi Pengguna GeoMinsel</h2>
        <p class="auth-subtitle">
          Sistem Informasi Geografis Kabupaten Minahasa Selatan &bull; Fase 2 Role-Based Access
        </p>
      </div>

      <!-- Quick Persona Switcher for the 8 Roles -->
      <div class="persona-section">
        <span class="persona-label">Pilih Persona Role Resmi (Fase 2):</span>
        <div class="persona-grid">
          <button
            v-for="r in rolesConfig"
            :key="r.role"
            class="persona-btn"
            :class="{ active: selectedRole === r.role }"
            @click="selectRole(r.role)"
            type="button"
          >
            <component :is="r.icon" :size="16" :style="{ color: r.color }" />
            <div class="persona-info">
              <span class="p-name">{{ r.label }}</span>
              <span class="p-opd">{{ r.opd }}</span>
            </div>
          </button>
        </div>
      </div>

      <!-- Active Role Description Badge -->
      <div class="role-desc-box">
        <div class="desc-badge">
          <span>Role Aktif: <strong>{{ rolesConfig.find(r => r.role === selectedRole)?.label }}</strong></span>
          <span>&bull;</span>
          <span>{{ rolesConfig.find(r => r.role === selectedRole)?.opd }}</span>
        </div>
      </div>

      <!-- Form Inputs -->
      <form class="auth-form" @submit.prevent="handleLogin">
        <div class="form-group">
          <label class="form-label">Username / Akun Resmi</label>
          <div class="input-wrap">
            <User :size="16" class="input-icon" />
            <input
              v-model="username"
              type="text"
              class="form-input"
              placeholder="Masukkan username OPD..."
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Kata Sandi / Kredensial</label>
          <div class="input-wrap">
            <Lock :size="16" class="input-icon" />
            <input
              v-model="password"
              type="password"
              class="form-input"
              placeholder="Masukkan kata sandi..."
              required
            />
          </div>
        </div>

        <div class="auth-actions">
          <button type="submit" class="btn-submit" :disabled="isSubmitting">
            <span>{{ isSubmitting ? 'Memverifikasi...' : 'Masuk Dashboard' }}</span>
            <ArrowRight :size="16" />
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.auth-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(7, 13, 30, 0.85);
  backdrop-filter: blur(12px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
  box-sizing: border-box;
}

.auth-card {
  position: relative;
  width: 100%;
  max-width: 640px;
  background: #0f172a;
  border: 1px solid rgba(56, 189, 248, 0.3);
  border-radius: 20px;
  padding: 36px 32px;
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.7);
  color: #f8fafc;
}

.modal-close-btn {
  position: absolute;
  top: 20px;
  right: 20px;
  background: rgba(255, 255, 255, 0.08);
  border: none;
  color: #94a3b8;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.modal-close-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  color: #ffffff;
}

.auth-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-bottom: 24px;
}

.auth-emblem {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: rgba(2, 132, 199, 0.2);
  border: 1px solid rgba(2, 132, 199, 0.4);
  color: #38bdf8;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 12px;
}

.auth-title {
  font-size: 20px;
  font-weight: 800;
  color: #ffffff;
  margin-bottom: 6px;
}

.auth-subtitle {
  font-size: 12px;
  color: #94a3b8;
}

.persona-section {
  margin-bottom: 20px;
}

.persona-label {
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: block;
  margin-bottom: 10px;
}

.persona-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 8px;
}

.persona-btn {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  padding: 8px 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  text-align: left;
  transition: all 0.15s ease;
}

.persona-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(56, 189, 248, 0.3);
}

.persona-btn.active {
  background: rgba(56, 189, 248, 0.15);
  border-color: #38bdf8;
}

.persona-info {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.p-name {
  font-size: 11px;
  font-weight: 700;
  color: #f8fafc;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.p-opd {
  font-size: 9px;
  color: #94a3b8;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.role-desc-box {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  padding: 8px 14px;
  margin-bottom: 20px;
}

.desc-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #38bdf8;
}

.desc-badge strong {
  color: #ffffff;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-label {
  font-size: 12px;
  font-weight: 600;
  color: #cbd5e1;
}

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 12px;
  color: #64748b;
  pointer-events: none;
}

.form-input {
  width: 100%;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 8px;
  padding: 10px 12px 10px 38px;
  color: #f8fafc;
  font-size: 13px;
  outline: none;
  transition: all 0.2s;
}

.form-input:focus {
  border-color: #38bdf8;
  background: rgba(255, 255, 255, 0.08);
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.2);
}

.btn-submit {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
  color: #ffffff;
  border: none;
  font-size: 14px;
  font-weight: 700;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
  transition: all 0.2s;
  margin-top: 8px;
}

.btn-submit:hover:not(:disabled) {
  filter: brightness(1.1);
  box-shadow: 0 6px 18px rgba(37, 99, 235, 0.5);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 600px) {
  .persona-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
