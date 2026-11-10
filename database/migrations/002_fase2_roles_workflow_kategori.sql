-- ==============================================================================
-- Migrasi Fase 2: Role, Kewenangan, Alur Persetujuan Terpusat & Master Kategori
-- Nama Sistem: GeoMinsel (Sistem Informasi Geografis Kabupaten Minahasa Selatan)
-- ==============================================================================

-- 1. TABEL MASTER ROLE (8 Role Utama)
CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL UNIQUE,                -- superadmin, admin_gis, admin_opd, operator_opd, validator_gis, approver_data, pimpinan, publik
  `label` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT NULL,
  `is_system` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL MASTER GRANULAR PERMISSION
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,               -- Contoh: dataset.create, dataset.validate, dataset.approve, dataset.publish
  `modul` VARCHAR(50) NOT NULL,                      -- user, dataset, layer, gis_service, system, audit, data_dasar
  `label` VARCHAR(150) NOT NULL,
  `deskripsi` TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABEL RELASI ROLE - PERMISSION (RBAC Granular)
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` INT NOT NULL,
  `permission_id` INT NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABEL MULTI-ROLE PENGGUNA (User Dapat Memiliki Lebih dari 1 Role)
CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `role_id` INT NOT NULL,
  `assigned_by` INT NULL,
  `valid_until` DATETIME NULL COMMENT 'Masa berlaku jika penugasan sementara',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_user_role` (`user_id`, `role_id`),
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABEL SCOPING PENUGASAN OPD DAN DATASET
CREATE TABLE IF NOT EXISTS `user_opd_access` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `opd_id` INT NOT NULL,
  `is_primary` TINYINT(1) DEFAULT 1,
  `assigned_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_uoa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uoa_opd` FOREIGN KEY (`opd_id`) REFERENCES `ref_opd` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABEL SUBKATEGORI LAYER TEMATIK
CREATE TABLE IF NOT EXISTS `subkategori_layer` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kategori_id` INT NOT NULL,
  `kode_subkategori` VARCHAR(50) NOT NULL UNIQUE,
  `nama_subkategori` VARCHAR(150) NOT NULL,
  `tipe_geometri_default` ENUM('POINT', 'LINE', 'POLYGON', 'RASTER', 'TABEL') NOT NULL,
  `deskripsi` TEXT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  CONSTRAINT `fk_subkat_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori_layer` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABEL MODUL KHUSUS SUPERADMIN: DATA DASAR GEOSPASIAL (UPLOAD SHP BATAS WILAYAH)
CREATE TABLE IF NOT EXISTS `data_dasar_geospasial` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipe_data` ENUM('KABUPATEN', 'KECAMATAN', 'DESA', 'JARINGAN_JALAN', 'SUNGAI', 'GARIS_PANTAI', 'KONTUR') NOT NULL,
  `versi` VARCHAR(20) NOT NULL,                      -- Misal: 2026.1, 2026.2
  `nama_file_zip` VARCHAR(255) NOT NULL,
  `storage_path` VARCHAR(255) NOT NULL,
  `sumber_resmi` VARCHAR(150) NOT NULL,              -- Misal: BIG, Kemendagri, BPN
  `tahun_referensi` INT NOT NULL,
  `crs_terdeteksi` VARCHAR(50) DEFAULT 'EPSG:4326',
  `jumlah_fitur` INT DEFAULT 0,
  `status` ENUM('DRAFT', 'AKTIF', 'DIGANTIKAN', 'DIARSIPKAN') NOT NULL DEFAULT 'DRAFT',
  `catatan_perubahan` TEXT NULL,
  `uploaded_by` INT NOT NULL,
  `activated_by` INT NULL,
  `activated_at` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ddg_uploaded` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TABEL WORKFLOW STATUS & RIWAYAT PERSETUJUAN TERPUSAT DATASET
CREATE TABLE IF NOT EXISTS `dataset_workflow_history` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `objek_id` BIGINT NOT NULL,
  `status_sebelumnya` VARCHAR(50) NULL,
  `status_baru` ENUM(
    'draft', 'submitted', 'screening', 'opd_review',
    'technical_validation', 'revision_required', 'pending_approval',
    'approved', 'rejected', 'publishing', 'published', 'archived'
  ) NOT NULL,
  `actor_user_id` INT NOT NULL,
  `role_used` VARCHAR(50) NOT NULL,
  `catatan` TEXT NULL,
  `hasil_pemeriksaan_json` JSON NULL COMMENT 'Laporan error geometri, CRS, atribut jika ditolak/validasi',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_dwh_objek` (`objek_id`),
  CONSTRAINT `fk_dwh_actor` FOREIGN KEY (`actor_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. TABEL KEPUTUSAN PERSETUJUAN TERPUSAT (APPROVER DATA)
CREATE TABLE IF NOT EXISTS `dataset_approvals` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `objek_id` BIGINT NOT NULL,
  `approver_user_id` INT NOT NULL,
  `keputusan` ENUM('APPROVED', 'REJECTED', 'REVISION_REQUIRED') NOT NULL,
  `alasan_keputusan` TEXT NOT NULL,
  `klasifikasi_publikasi` ENUM('PUBLIK', 'INTERNAL_PEMKAB', 'TERBATAS', 'DIKECUALIKAN') NOT NULL DEFAULT 'PUBLIK',
  `approved_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_appr_user` FOREIGN KEY (`approver_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. TABEL AUDIT LOG AKTIVITAS SISTEM
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `actor_user_id` INT NULL,
  `actor_username` VARCHAR(50) NULL,
  `role_used` VARCHAR(50) NULL,
  `opd_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,                     -- LOGIN, LOGOUT, CREATE_USER, UPLOAD_SHP, APPROVE_DATASET, PUBLISH_LAYER, dll
  `resource_type` VARCHAR(50) NOT NULL,               -- dataset, user, role, data_dasar, layer, config
  `resource_id` VARCHAR(50) NULL,
  `result` ENUM('SUCCESS', 'FAILED', 'DENIED') NOT NULL DEFAULT 'SUCCESS',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` TEXT NULL,
  `before_value` JSON NULL,
  `after_value` JSON NULL,
  `reason` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_audit_actor` (`actor_user_id`),
  INDEX `idx_audit_action` (`action`),
  INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
