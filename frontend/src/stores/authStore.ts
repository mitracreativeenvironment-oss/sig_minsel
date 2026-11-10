import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { sigApi } from '../services/api';
import type { UserProfile, RoleName, UserRoleInfo } from '../types';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<UserProfile | null>(null);
  const roles = ref<UserRoleInfo[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Default persona mock untuk kemudahan evaluasi 8 role
  const personaPresets: Record<RoleName, { username: string; label: string; opd: string; icon: string }> = {
    superadmin: {
      username: 'superadmin_minsel',
      label: 'Superadmin Diskominfo',
      opd: 'Dinas Komunikasi dan Informatika',
      icon: 'ShieldAlert'
    },
    admin_gis: {
      username: 'admingis_minsel',
      label: 'Admin GIS Utama',
      opd: 'Dinas Komunikasi dan Informatika',
      icon: 'MapPin'
    },
    admin_opd: {
      username: 'admin_pupr',
      label: 'Admin OPD PUPR',
      opd: 'Dinas Pekerjaan Umum dan Penataan Ruang',
      icon: 'Building2'
    },
    operator_opd: {
      username: 'operator_pertanian',
      label: 'Operator OPD Pertanian',
      opd: 'Dinas Pertanian',
      icon: 'Layers'
    },
    validator_gis: {
      username: 'validator_minsel',
      label: 'Validator GIS Teknis',
      opd: 'Tim Teknis GIS Minahasa Selatan',
      icon: 'CheckCheck'
    },
    approver_data: {
      username: 'approver_bappeda',
      label: 'Approver Data (Bappelitbangda)',
      opd: 'Badan Perencanaan, Penelitian dan Pengembangan Daerah',
      icon: 'FileCheck2'
    },
    pimpinan: {
      username: 'bupati_minsel',
      label: 'Pimpinan Daerah (Bupati/Sekda)',
      opd: 'Pemerintah Kabupaten Minahasa Selatan',
      icon: 'Crown'
    },
    publik: {
      username: 'publik_minsel',
      label: 'Masyarakat Umum / Peneliti',
      opd: 'Publik Terbuka',
      icon: 'Globe'
    }
  };

  const isAuthenticated = computed(() => !!user.value && user.value.role !== 'publik');
  const currentRole = computed<RoleName>(() => user.value?.role || 'publik');
  const roleLabel = computed(() => user.value?.role_label || 'Publik');
  const opdLabel = computed(() => user.value?.opd || 'Masyarakat Umum');

  async function loadRoles() {
    try {
      const res = await sigApi.getRoles();
      roles.value = res.data;
    } catch (e: any) {
      console.warn('Gagal memuat roles dari API, menggunakan fallback:', e);
    }
  }

  async function login(roleName: RoleName = 'superadmin', username?: string, password?: string) {
    loading.value = true;
    error.value = null;
    try {
      const res = await sigApi.login({
        role: roleName,
        username: username || roleName,
        password: password || 'rahasia'
      });
      user.value = res.data;
      localStorage.setItem('geominsel_user', JSON.stringify(res.data));
      return res.data;
    } catch (e: any) {
      error.value = e.message || 'Gagal login';
      // Local fallback jika backend offline
      const preset = personaPresets[roleName] || personaPresets.publik;
      const fallbackUser: UserProfile = {
        id: 1,
        username: preset.username,
        nama_lengkap: preset.label,
        email: `${roleName}@minselkab.go.id`,
        role: roleName,
        role_label: preset.label,
        opd: preset.opd,
        kode_opd: roleName.toUpperCase(),
        permissions: ['*'],
        token: `mock_token_${roleName}_${Date.now()}`
      };
      user.value = fallbackUser;
      localStorage.setItem('geominsel_user', JSON.stringify(fallbackUser));
      return fallbackUser;
    } finally {
      loading.value = false;
    }
  }

  function quickSwitch(roleName: RoleName) {
    return login(roleName);
  }

  function logout() {
    user.value = null;
    localStorage.removeItem('geominsel_user');
  }

  function initFromStorage() {
    try {
      const saved = localStorage.getItem('geominsel_user');
      if (saved) {
        user.value = JSON.parse(saved);
      }
    } catch (e) {
      console.warn('Gagal memulihkan session pengguna', e);
    }
  }

  function hasPermission(perm: string): boolean {
    if (!user.value) return false;
    if (user.value.role === 'superadmin') return true;
    if (user.value.permissions.includes('*')) return true;
    return user.value.permissions.includes(perm);
  }

  return {
    user,
    roles,
    loading,
    error,
    personaPresets,
    isAuthenticated,
    currentRole,
    roleLabel,
    opdLabel,
    loadRoles,
    login,
    quickSwitch,
    logout,
    initFromStorage,
    hasPermission
  };
});
