# Changelog

Semua perubahan penting pada proyek **SIG Minahasa Selatan** didokumentasikan di berkas ini.

Format berkas ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
dan proyek ini menerapkan [Semantic Versioning (SemVer)](https://semver.org/lang/id/).

---

## [Unreleased]

---

## [0.5.4] - 2026-11-10

### Keterangan Perubahan
- Latar animasi horizontal scrollytelling menggunakan foto nyata lanskap Minahasa Selatan dengan parallax dan crossfade dinamis
- feat(landing): integrasi latar animasi scrollytelling foto nyata lanskap Minahasa Selatan dengan parallax crossfade dan switcher mode (8c76aa9)

---

## [0.5.3] - 2026-11-10

### Keterangan Perubahan
- Transformasi Landing Page menjadi Horizontal Scrollytelling interaktif dengan kontrol timeline horizontal dan 3D fly-through
- feat(landing): transformasi landing page menjadi Horizontal Scrollytelling interaktif dengan integrasi GSAP dan Three.js (2a84cff)

---

## [0.5.2] - 2026-11-10

### Keterangan Perubahan
- Tampilan default fokus teritorial Minsel: blur & desaturasi wilayah luar, garis batas kabupaten tegas bercahaya
- feat(map): implementasi efek visual fokus Minsel dengan blur dan desaturasi wilayah luar serta batas kabupaten tegas (f45a1bb)

---

## [0.5.1] - 2026-11-10

### Keterangan Perubahan
- Ganti bar statistik bawah dengan toggle lokasi saya (GPS On/Off) & integrasi 8 basemap gratis lengkap
- feat(gis): add user location toggle (on/off) and 8 comprehensive free basemaps (41b550f)
- feat(branding): add Minahasa Selatan official logo concepts and web favicon kit (eade585)

---

## [0.5.0] - 2026-11-10

### Keterangan Perubahan
- Implementasi Landing Page Three.js Scrollytelling, Login Lengkap 8 Role & Dashboard Tata Kelola Spasial
- feat(ui): implement Three.js scrollytelling landing page, 8-role auth system & comprehensive dashboard (b3ea909)

---

## [0.4.1] - 2026-11-10

### Keterangan Perubahan
- Refaktor endpoint: gunakan kata kunci resmi 'spasials' menggantikan '/api/v1'

---

## [0.4.0] - 2026-11-10

### Keterangan Perubahan
- Fase 2: Implementasi 8 Role Utama, Workflow Persetujuan Terpusat, 28 Master Kategori Geospasial GeoMinsel, dan Modul Data Dasar SHP Superadmin

---

## [0.3.0] - 2026-11-10

### Keterangan Perubahan
- Implementasi hierarki administrasi 3 level (Kabupaten -> Kecamatan -> Desa) dan Frontend Vue 3 MapLibre GL JS

---

## [0.2.0] - 2026-11-10

### Keterangan Perubahan
- Implementasi Backend CI4 Full API dengan proteksi token resmi frontend dan auto-redirect non-API
- style(seed): rapikan baris akhir berkas seed spasial (618aa75)

### Planned
- Inisialisasi kerangka CodeIgniter 4 REST API
- Inisialisasi antarmuka peta Vue 3 + MapLibre GL JS
- Integrasi basemap eksternal & layer administrasi Minahasa Selatan

---

## [0.1.0] - 2026-11-10

### Added
- Inisialisasi repositori proyek WebGIS Kabupaten Minahasa Selatan.
- Definisi arsitektur teknologi:
  - Frontend: Vue.js 3 (Composition API)
  - Map Renderer: MapLibre GL JS
  - Backend: CodeIgniter 4 REST API
  - Database: MySQL Spatial (SRID 4326)
  - GIS Desktop: QGIS
  - Web Server: Apache / cPanel
- Sistem manajemen versi SemVer otomatis (`VERSION`, `release.ps1`, `release.sh`).
- Workflow otomasi CI/CD & GitHub Releases (`.github/workflows/release.yml`, `.github/workflows/ci.yml`).
- Skema dasar basis data spasial (tabel batas administrasi kecamatan/desa dan objek tematik).
- Konfigurasi `.gitignore` menyeluruh untuk CI4, Vue 3, GIS, dan cPanel.










