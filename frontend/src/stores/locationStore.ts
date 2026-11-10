import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useLocationStore = defineStore('location', () => {
  const isLocationActive = ref(false);
  const currentCoords = ref<{ lng: number; lat: number; accuracy: number } | null>(null);
  const locationError = ref<string | null>(null);
  const isLocating = ref(false);
  const centerRequestTrigger = ref(0);
  let watchId: number | null = null;

  function toggleLocation() {
    if (isLocationActive.value) {
      stopLocation();
    } else {
      startLocation();
    }
  }

  function startLocation() {
    locationError.value = null;

    if (!('geolocation' in navigator)) {
      locationError.value = 'Perangkat / browser Anda tidak mendukung fitur geolokasi GPS.';
      isLocationActive.value = false;
      return;
    }

    isLocating.value = true;
    isLocationActive.value = true;

    // Ambil posisi awal segera
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        currentCoords.value = {
          lng: pos.coords.longitude,
          lat: pos.coords.latitude,
          accuracy: Math.round(pos.coords.accuracy)
        };
        isLocating.value = false;
        centerRequestTrigger.value++;
      },
      (err) => {
        handleGeoError(err);
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      }
    );

    // Pantau pembaruan posisi secara langsung (real-time watch)
    watchId = navigator.geolocation.watchPosition(
      (pos) => {
        currentCoords.value = {
          lng: pos.coords.longitude,
          lat: pos.coords.latitude,
          accuracy: Math.round(pos.coords.accuracy)
        };
        isLocating.value = false;
      },
      (err) => {
        handleGeoError(err);
      },
      {
        enableHighAccuracy: true,
        timeout: 15000,
        maximumAge: 3000
      }
    );
  }

  function stopLocation() {
    if (watchId !== null) {
      navigator.geolocation.clearWatch(watchId);
      watchId = null;
    }
    isLocationActive.value = false;
    currentCoords.value = null;
    isLocating.value = false;
    locationError.value = null;
  }

  function handleGeoError(err: GeolocationPositionError) {
    isLocating.value = false;
    switch (err.code) {
      case err.PERMISSION_DENIED:
        locationError.value = 'Izin lokasi tidak diberikan. Harap aktifkan izin lokasi di browser Anda.';
        stopLocation();
        break;
      case err.POSITION_UNAVAILABLE:
        locationError.value = 'Informasi sinyal GPS / lokasi saat ini tidak tersedia.';
        break;
      case err.TIMEOUT:
        locationError.value = 'Waktu permintaan lokasi GPS habis. Mencoba kembali...';
        break;
      default:
        locationError.value = 'Terjadi kesalahan saat mendeteksi lokasi.';
    }
  }

  function triggerCenter() {
    if (isLocationActive.value && currentCoords.value) {
      centerRequestTrigger.value++;
    }
  }

  return {
    isLocationActive,
    currentCoords,
    locationError,
    isLocating,
    centerRequestTrigger,
    toggleLocation,
    startLocation,
    stopLocation,
    triggerCenter
  };
});

