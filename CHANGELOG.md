# Changelog

Semua perubahan penting pada proyek **SIG Minahasa Selatan** didokumentasikan di berkas ini.

Format berkas ini mengacu pada [Keep a Changelog](https://keepachangelog.com/id/1.1.0/),
dan proyek ini menerapkan [Semantic Versioning (SemVer)](https://semver.org/lang/id/).

---

## [Unreleased]

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





