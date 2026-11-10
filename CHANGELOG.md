# Changelog

Semua perubahan penting pada proyek **SIG Minahasa Selatan** didokumentasikan di berkas ini.

Format berkas ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
dan proyek ini menerapkan [Semantic Versioning (SemVer)](https://semver.org/lang/id/).

---

## [Unreleased]

---

## [0.9.0] - 2026-11-10

### Keterangan Perubahan
- Inisialisasi basis data sig_minsel, perbaikan axis-order spasial MySQL 8, dan verifikasi sinkronisasi 8 role dashboard
- feat(backend): inisialisasi basis data sig_minsel, migrasi spasial MySQL 8 axis-order, dan verifikasi penuh API bertoken (3bf973e)

---

## [0.8.0] - 2026-11-10

### Keterangan Perubahan
- Implementasi Lambang Resmi Kabupaten Minahasa Selatan sebagai identitas visual logo dan icon kit aplikasi
- feat(branding): implementasikan lambang resmi Kabupaten Minahasa Selatan sebagai logo aplikasi dan icon kit (ecc7fbd)

---

## [0.7.0] - 2026-11-10

### Keterangan Perubahan
- Penyederhanaan: Menghapus landing page dan menjadikan peta interaktif MapLibre sebagai antarmuka utama
- feat(geoportal): hapus landing page dan fokus langsung ke peta interaktif MapLibre (23f1b6d)

---

## [0.6.0] - 2026-11-10

### Keterangan Perubahan
- Implementasi Story 01: Kebangkitan Burung Manguni (Shot 01-07) dengan Celepuk Sulawesi dan transformasi lambang resmi
- feat(story-01): implementasi sinematik 3D Story 01 Kebangkitan Burung Manguni (Shot 01-07) (6e9bdae)

---

## [0.5.9] - 2026-11-10

### Keterangan Perubahan
- Optimalisasi adaptif kamera multi-resolusi dan responsivitas mobile 3D Logo Perakitan
- fix(3d-logo): adaptif kamera multi-resolusi portrait/desktop dan HUD responsif pada layar ponsel (406d494)

---

## [0.5.8] - 2026-11-10

### Keterangan Perubahan
- 3D Kinetic Logo Assembler autentik berbasis lambang resmi Kabupaten Minahasa Selatan
- feat(3d-logo): perakitan 3D autentik berbasis Lambang_Kabupaten_Minahasa_Selatan.png dengan segmentasi layer heraldik resmi (a509622)

---

## [0.5.7] - 2026-11-10

### Keterangan Perubahan
- 3D Kinetic Logo Assembler lambang Minahasa Selatan tersinkronisasi horizontal scrollytelling
- feat(3d-logo): implementasi 3D Kinetic Logo Assembler lambang Minahasa Selatan tersinkronisasi horizontal scrollytelling (2a1fa8d)

---

## [0.5.6] - 2026-11-10

### Keterangan Perubahan
- 3D Topografi berbasis foto nyata Minahasa Selatan dengan Heightmap Displacement dan Tekstur Albedo PBR
- feat(3d-terrain): rekonstruksi 3D topografi realistis berbasis foto nyata minsel dengan PBR heightmap dan shader (6093067)

---

## [0.5.5] - 2026-11-10

### Keterangan Perubahan
- Adopsi 41 foto asli Minahasa Selatan dari images/minsel sebagai latar horizontal scrollytelling dengan metadata koordinat dan elevasi real-time
- feat(scenery): integrasi 41 foto asli Minahasa Selatan dari images/minsel ke dalam horizontal scrollytelling (7cbc750)

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



















