# GeoMinsel &mdash; Sistem Informasi Geografis Kabupaten Minahasa Selatan

**GeoMinsel** adalah Geoportal dan sistem pengelolaan data geospasial mandiri (*standalone*) untuk Pemerintah Kabupaten Minahasa Selatan. Dirancang dengan pendekatan arsitektur ringan, performa tinggi, dan kompatibel dengan lingkungan *hosting* Apache/cPanel.

---

## Ringkasan Fase Pengembangan

- **Fase 1: Arsitektur Teknologi & Pondasi Data Dasar**: Vue 3, MapLibre GL JS, CodeIgniter 4 REST API, MySQL Spatial (SRID 4326), QGIS Desktop, dan Hierarki 3-Level (Kabupaten &rarr; Kecamatan &rarr; Desa).
- **Fase 2: Role dan Kewenangan Pengguna**:
  - **8 Role Utama**: Superadmin, Admin GIS, Admin OPD, Operator OPD, Validator GIS, Approver Data, Pimpinan/Executive Viewer, dan Publik.
  - **Alur Persetujuan Terpusat (*Central Approval*)**: Upload OPD &rarr; Validasi Teknis GIS &rarr; Persetujuan Pusat &rarr; Publikasi Layer.
  - **Master 28 Kategori Geospasial GeoMinsel** terbagi menurut OPD produsen data.
  - **Modul Khusus Superadmin**: Manajemen Data Dasar Geospasial (Upload SHP ZIP Batas Administrasi resmi).
  - Dokumen lengkap: [`docs/FASE_2_ROLE_DAN_KEWENANGAN.md`](file:///d:/sig_minsel/docs/FASE_2_ROLE_DAN_KEWENANGAN.md).

---

## 1. Arsitektur Teknologi

| Lapisan | Teknologi | Status / Keterangan |
|---|---|---|
| **Frontend** | Vue.js 3 (Composition API, Vite, Pinia) | Dipakai |
| **Map Renderer** | MapLibre GL JS | Dipakai (Hardware-accelerated WebGL) |
| **Backend** | CodeIgniter 4 REST Service | Dipakai (Endpoint resmi: `/spasials/...`) |
| **Database** | MySQL Spatial (SRID 4326 WGS84) | Dipakai (Spatial Index, `ST_AsGeoJSON`) |
| **GIS Desktop** | QGIS Desktop | Dipakai (Digitasi, validasi data spasial) |
| **Web Server** | Apache / cPanel | Mengikuti hosting |
| **Peta Dasar** | Tile Provider Eksternal (OSM, Carto, Esri) | Dipakai sesuai kebutuhan |
| **Penyajian Data**| GeoJSON dan Static MVT (`.pbf`) | Bertahap |
| **GIS Server** | GeoServer | *Tidak dipasang pada tahap awal* |
| **Reverse Proxy** | Nginx | *Tidak diperlukan; gunakan Apache bawaan* |

---

## 2. Struktur Direktori Proyek

```text
sig_minsel/
├── .github/
│   └── workflows/
│       └── release.yml        # Otomasi GitHub Release saat tag di-push
├── backend/                   # CodeIgniter 4 REST API
├── frontend/                  # Vue 3 SPA + MapLibre GL JS
├── database/
│   └── migrations/            # Skema DDL MySQL Spatial
├── scripts/
│   ├── release.ps1            # Skrip manajemen rilis & tag (PowerShell)
│   └── release.sh             # Skrip manajemen rilis & tag (Bash)
├── .gitignore
├── CHANGELOG.md               # Catatan riwayat perubahan (Keep a Changelog)
├── package.json               # Konfigurasi npm scripts untuk rilis
├── README.md
└── VERSION                    # Single source of truth versi aktif (SemVer)
```

---

## 3. Sistem Versi, Tagging, & Rilis

Proyek ini menerapkan standar **[Semantic Versioning (SemVer)](https://semver.org/)**:
- `MAJOR` (X.0.0): Perubahan arsitektur besar / *breaking changes*.
- `MINOR` (0.X.0): Penambahan fitur layer spasial baru, modul baru.
- `PATCH` (0.0.X): Perbaikan bug, optimasi query spasial, penyesuaian style.

### A. Melakukan Rilis Otomatis (PowerShell / Windows)

Gunakan skrip `scripts/release.ps1` yang sudah disediakan:

```powershell
# 1. Kenaikan PATCH (contoh: 0.1.0 -> 0.1.1)
.\scripts\release.ps1 -Type patch

# 2. Kenaikan MINOR (contoh: 0.1.0 -> 0.2.0)
.\scripts\release.ps1 -Type minor

# 3. Kenaikan MAJOR (contoh: 0.1.0 -> 1.0.0)
.\scripts\release.ps1 -Type major

# 4. Kenaikan versi langsung dengan auto-push ke GitHub
.\scripts\release.ps1 -Type patch -Push
.\scripts\release.ps1 -Type minor -Message "Penambahan layer batas kecamatan" -Push

# 5. Kenaikan versi custom
.\scripts\release.ps1 -Type custom -Version 1.0.0 -Push
```

### B. Melalui npm scripts
```bash
npm run release:patch
npm run release:minor
npm run release:major
```

### C. Alur Kerja Tag & GitHub Release
1. Skrip akan memvalidasi *working tree*.
2. Memperbarui berkas `VERSION`, `package.json`, dan menambahkan catatan di `CHANGELOG.md`.
3. Membuat commit otomatis: `chore(release): vX.Y.Z`.
4. Membuat Git Tag terannotasi: `vX.Y.Z`.
5. Saat di-push ke GitHub, workflow **GitHub Actions** (`release.yml`) akan secara otomatis mempublikasikan **GitHub Release** baru.

---

## 4. Konvensi Commit (Conventional Commits)

Format pesan commit yang dianjurkan:
- `feat: <deskripsi>`: Fitur baru (misal: penambahan layer fasilitas kesehatan).
- `fix: <deskripsi>`: Perbaikan bug (misal: perbaikan parsing GeoJSON).
- `docs: <deskripsi>`: Perubahan dokumentasi.
- `style: <deskripsi>`: Perubahan format tampilan / CSS / basemap style.
- `refactor: <deskripsi>`: Penataan ulang kode tanpa mengubah fungsionalitas.
- `chore: <deskripsi>`: Pemeliharaan dependensi, konfigurasi build, atau rilis.

---

## Lisensi & Kontribusi
Dikembangkan oleh **Mitra Creative Environment OSS** untuk inisiatif tata kelola geospasial terbuka.
Lisensi: MIT.

