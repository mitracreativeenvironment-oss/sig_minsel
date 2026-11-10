-- ==============================================================================
-- Skema Basis Data Spasial & Hierarki Administrasi SIG Minahasa Selatan
-- SRS: EPSG:4326 (WGS 84) | Engine: InnoDB MySQL 8.0+ / MariaDB 10.3+
-- Hierarki: Kabupaten (Induk) -> Kecamatan -> Desa/Kelurahan
-- ==============================================================================

-- 1. HIERARKI LEVEL 1: KABUPATEN (Wilayah Induk)
CREATE TABLE IF NOT EXISTS `ref_kabupaten` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_kabupaten` VARCHAR(10) NOT NULL UNIQUE,       -- 71.05 (Kemendagri)
  `nama_kabupaten` VARCHAR(100) NOT NULL DEFAULT 'Minahasa Selatan',
  `ibukota` VARCHAR(100) NOT NULL DEFAULT 'Amurang',
  `luas_wilayah_km2` DECIMAL(10,2) NULL,
  `deskripsi` TEXT NULL,
  `geom` MULTIPOLYGON NOT NULL /*!80003 SRID 4326 */,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  SPATIAL KEY `sp_idx_kabupaten_geom` (`geom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. HIERARKI LEVEL 2: KECAMATAN (Bagian dari Kabupaten)
CREATE TABLE IF NOT EXISTS `ref_kecamatan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kabupaten_id` INT NOT NULL DEFAULT 1,
  `kode_kecamatan` VARCHAR(10) NOT NULL UNIQUE,       -- 71.05.xx
  `nama_kecamatan` VARCHAR(100) NOT NULL,
  `ibukota_kecamatan` VARCHAR(100) NULL,
  `luas_wilayah_km2` DECIMAL(10,2) NULL,
  `geom` MULTIPOLYGON NOT NULL /*!80003 SRID 4326 */,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  SPATIAL KEY `sp_idx_kecamatan_geom` (`geom`),
  INDEX `idx_kecamatan_kab` (`kabupaten_id`),
  CONSTRAINT `fk_kecamatan_kabupaten` FOREIGN KEY (`kabupaten_id`) REFERENCES `ref_kabupaten` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. HIERARKI LEVEL 3: DESA / KELURAHAN (Memiliki Relasi Wajib ke Kecamatan DAN Kabupaten)
CREATE TABLE IF NOT EXISTS `ref_desa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kabupaten_id` INT NOT NULL DEFAULT 1,             -- Relasi langsung ke Kabupaten Induk (Minahasa Selatan)
  `kecamatan_id` INT NOT NULL,                       -- Relasi ke Kecamatan Induk
  `kode_desa` VARCHAR(15) NOT NULL UNIQUE,           -- 71.05.xx.xxxx
  `nama_desa` VARCHAR(100) NOT NULL,
  `tipe` ENUM('DESA', 'KELURAHAN') NOT NULL DEFAULT 'DESA',
  `luas_wilayah_km2` DECIMAL(10,2) NULL,
  `geom` MULTIPOLYGON NOT NULL /*!80003 SRID 4326 */,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  SPATIAL KEY `sp_idx_desa_geom` (`geom`),
  INDEX `idx_desa_hierarki` (`kabupaten_id`, `kecamatan_id`),
  CONSTRAINT `fk_desa_kabupaten` FOREIGN KEY (`kabupaten_id`) REFERENCES `ref_kabupaten` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_desa_kecamatan` FOREIGN KEY (`kecamatan_id`) REFERENCES `ref_kecamatan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABEL ORGANISASI PERANGKAT DAERAH (OPD) & KEPEMILIKAN DATASET
CREATE TABLE IF NOT EXISTS `ref_opd` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_opd` VARCHAR(50) NOT NULL UNIQUE,            -- MISAL: DINKES, PUPR, BPBD, DISKOMINFO
  `nama_opd` VARCHAR(150) NOT NULL,
  `email` VARCHAR(100) NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABEL PENGGUNA & SCOPING HAK AKSES BERJENJANG (RBAC)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `opd_id` INT NULL,                                 -- Scope OPD jika role OPD
  `kecamatan_id` INT NULL,                           -- Scope Kecamatan jika operator tingkat kecamatan
  `desa_id` INT NULL,                                -- Scope Desa jika operator tingkat desa
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('SUPERADMIN', 'ADMIN_OPD', 'OPERATOR_KECAMATAN', 'OPERATOR_DESA') NOT NULL DEFAULT 'ADMIN_OPD',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_user_opd` FOREIGN KEY (`opd_id`) REFERENCES `ref_opd` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_kec` FOREIGN KEY (`kecamatan_id`) REFERENCES `ref_kecamatan` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_desa` FOREIGN KEY (`desa_id`) REFERENCES `ref_desa` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABEL KATEGORI LAYER TEMATIK
CREATE TABLE IF NOT EXISTS `kategori_layer` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `opd_id` INT NULL,                                 -- OPD Penanggung Jawab Layer
  `kode` VARCHAR(50) NOT NULL UNIQUE,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT NULL,
  `icon` VARCHAR(50) DEFAULT 'pin-drop',
  `warna_default` VARCHAR(7) DEFAULT '#2563eb',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_kategori_opd` FOREIGN KEY (`opd_id`) REFERENCES `ref_opd` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABEL DATASET OBJEK SPASIAL (POINT / LINE / POLYGON) TERIKAT HIERARKI
CREATE TABLE IF NOT EXISTS `objek_spasial` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `kategori_id` INT NOT NULL,
  `kabupaten_id` INT NOT NULL DEFAULT 1,             -- Wajib terhubung ke Kabupaten Minahasa Selatan
  `kecamatan_id` INT NULL,                           -- Terhubung ke Kecamatan induk
  `desa_id` INT NULL,                                -- Terhubung ke Desa/Kelurahan induk (opsional/detail)
  `opd_id` INT NULL,                                 -- OPD pemilik data
  `nama_objek` VARCHAR(255) NOT NULL,
  `tipe_geometri` ENUM('POINT', 'LINESTRING', 'POLYGON', 'MULTIPOLYGON') NOT NULL,
  `deskripsi` TEXT NULL,
  `foto_url` VARCHAR(255) NULL,
  `atribut_tambahan` JSON NULL COMMENT 'Data fleksibel key-value spesifik OPD',
  `geom` GEOMETRY NOT NULL /*!80003 SRID 4326 */,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  SPATIAL KEY `sp_idx_objek_geom` (`geom`),
  INDEX `idx_objek_hierarki` (`kabupaten_id`, `kecamatan_id`, `desa_id`),
  INDEX `idx_objek_kategori` (`kategori_id`),
  INDEX `idx_objek_opd` (`opd_id`),
  CONSTRAINT `fk_objek_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_layer` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_objek_kab` FOREIGN KEY (`kabupaten_id`) REFERENCES `ref_kabupaten` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_objek_kec` FOREIGN KEY (`kecamatan_id`) REFERENCES `ref_kecamatan` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_objek_desa` FOREIGN KEY (`desa_id`) REFERENCES `ref_desa` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_objek_opd` FOREIGN KEY (`opd_id`) REFERENCES `ref_opd` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
