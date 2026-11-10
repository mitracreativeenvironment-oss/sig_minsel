-- ==============================================================================
-- Migrasi Awal: Skema Basis Data Spasial SIG Minahasa Selatan
-- SRS: EPSG:4326 (WGS 84)
-- Engine: InnoDB MySQL 8.0+ / MariaDB 10.3+
-- ==============================================================================

-- 1. Tabel Referensi Kecamatan
CREATE TABLE IF NOT EXISTS `ref_kecamatan` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_kecamatan` VARCHAR(10) NOT NULL UNIQUE,
  `nama_kecamatan` VARCHAR(100) NOT NULL,
  `ibukota_kecamatan` VARCHAR(100) NULL,
  `luas_wilayah_km2` DECIMAL(10,2) NULL,
  `geom` MULTIPOLYGON NOT NULL /*!80003 SRID 4326 */,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  SPATIAL KEY `sp_idx_kecamatan_geom` (`geom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Referensi Desa / Kelurahan
CREATE TABLE IF NOT EXISTS `ref_desa` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kecamatan_id` INT NOT NULL,
  `kode_desa` VARCHAR(15) NOT NULL UNIQUE,
  `nama_desa` VARCHAR(100) NOT NULL,
  `geom` MULTIPOLYGON NOT NULL /*!80003 SRID 4326 */,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  SPATIAL KEY `sp_idx_desa_geom` (`geom`),
  CONSTRAINT `fk_desa_kecamatan` FOREIGN KEY (`kecamatan_id`) REFERENCES `ref_kecamatan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Kategori Layer Tematik
CREATE TABLE IF NOT EXISTS `kategori_layer` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode` VARCHAR(50) NOT NULL UNIQUE,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT NULL,
  `icon` VARCHAR(50) DEFAULT 'pin-drop',
  `warna_default` VARCHAR(7) DEFAULT '#2563eb',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Objek Geografis Spasial (Point / Line / Polygon)
CREATE TABLE IF NOT EXISTS `objek_spasial` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `kategori_id` INT NOT NULL,
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
  INDEX `idx_objek_kategori` (`kategori_id`),
  CONSTRAINT `fk_objek_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_layer` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
