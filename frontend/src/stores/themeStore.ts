import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export type AppTheme = 'light' | 'dark';

export const useThemeStore = defineStore('theme', () => {
  // Ambil preferensi tersimpan di localStorage, atau default ke 'dark' (Noir Luxe Elegan)
  const savedTheme = (typeof window !== 'undefined' ? localStorage.getItem('geominsel_theme') : null) as AppTheme | null;
  const initialTheme: AppTheme = savedTheme === 'light' || savedTheme === 'dark' ? savedTheme : 'dark';

  const theme = ref<AppTheme>(initialTheme);

  const isDark = computed(() => theme.value === 'dark');
  const isLight = computed(() => theme.value === 'light');
  const themeLabel = computed(() => (theme.value === 'dark' ? 'Mode Gelap (Noir)' : 'Mode Terang (Minimalis)'));

  function applyTheme(newTheme: AppTheme) {
    theme.value = newTheme;
    if (typeof window !== 'undefined') {
      localStorage.setItem('geominsel_theme', newTheme);
      document.documentElement.setAttribute('data-theme', newTheme);
      document.documentElement.classList.remove('dark', 'light');
      document.documentElement.classList.add(newTheme);

      // Sinkronisasi meta theme-color pada browser mobile
      const metaThemeColor = document.querySelector('meta[name="theme-color"]');
      if (metaThemeColor) {
        metaThemeColor.setAttribute('content', newTheme === 'dark' ? '#09090b' : '#ffffff');
      }
    }
  }

  function toggleTheme() {
    const next = theme.value === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    return next;
  }

  function setTheme(t: AppTheme) {
    applyTheme(t);
  }

  function init() {
    applyTheme(theme.value);
  }

  return {
    theme,
    isDark,
    isLight,
    themeLabel,
    toggleTheme,
    setTheme,
    init
  };
});
