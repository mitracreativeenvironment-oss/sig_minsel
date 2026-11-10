-- ==============================================================================
-- Seed Fase 2: 8 Role Utama, Permissions, 28 Kategori Geospasial, dan OPD
-- Nama Sistem: GeoMinsel (Sistem Informasi Geografis Kabupaten Minahasa Selatan)
-- ==============================================================================

-- 1. SEED 8 ROLE UTAMA GEOMINSEL
INSERT INTO `roles` (`id`, `name`, `label`, `deskripsi`, `is_system`) VALUES
(1, 'superadmin', 'Superadmin', 'Pengelola keseluruhan sistem, konfigurasi, user, OPD, dan modul data dasar geospasial (SHP)', 1),
(2, 'admin_gis', 'Admin GIS', 'Pengelola teknis operasional geospasial, katalog layer, styling simbologi, dan publikasi', 1),
(3, 'admin_opd', 'Admin OPD', 'Pengelola akun operator dan pekerjaan data dalam OPD yang ditugaskan', 1),
(4, 'operator_opd', 'Operator OPD', 'Petugas penginputan, pengunggahan dataset sektoral, dan pengisian metadata', 1),
(5, 'validator_gis', 'Validator GIS', 'Pemeriksa kualitas dan kelayakan teknis geometri, CRS, atribut, dan metadata', 1),
(6, 'approver_data', 'Approver Data', 'Pejabat/petugas pemberi persetujuan dataset terpusat sebelum dipublikasikan', 1),
(7, 'pimpinan', 'Pimpinan / Executive Viewer', 'Akses pemantauan dashboard strategis, peta tematik pimpinan, dan laporan eksekutif (read-only)', 1),
(8, 'publik', 'Publik', 'Pengguna masyarakat umum untuk mengakses informasi dan dataset terbuka tanpa login', 1)
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`), `deskripsi`=VALUES(`deskripsi`);

-- 2. SEED PERMISSIONS GRANULAR
INSERT INTO `permissions` (`id`, `name`, `modul`, `label`, `deskripsi`) VALUES
-- Modul User & OPD
(1, 'user.view', 'user', 'Lihat Pengguna', 'Melihat daftar pengguna dalam lingkup kewenangannya'),
(2, 'user.create', 'user', 'Tambah Pengguna', 'Membuat akun pengguna baru (Superadmin atau Admin OPD)'),
(3, 'user.update', 'user', 'Ubah Pengguna', 'Mengubah profil, status, atau kata sandi pengguna'),
(4, 'user.disable', 'user', 'Nonaktifkan Pengguna', 'Menonaktifkan akses akun pengguna'),
(5, 'user.assign_role', 'user', 'Atur Role Pengguna', 'Menetapkan role kepada pengguna'),
(6, 'opd.view', 'opd', 'Lihat Data OPD', 'Melihat daftar organisasi perangkat daerah'),
(7, 'opd.manage', 'opd', 'Kelola Master OPD', 'Menambah dan mengubah master OPD (Superadmin)'),

-- Modul Data Dasar Geospasial (Khusus Superadmin)
(8, 'data_dasar.upload_shp', 'data_dasar', 'Upload SHP Batas Wilayah', 'Mengunggah file ZIP SHP batas administrasi Kabupaten, Kecamatan, Desa'),
(9, 'data_dasar.manage_version', 'data_dasar', 'Kelola Versi Data Dasar', 'Menyimpan, memperbarui, dan mengarsipkan versi data batas wilayah'),
(10, 'data_dasar.activate', 'data_dasar', 'Aktivasi Data Dasar', 'Mengaktifkan versi batas administrasi resmi rujukan sistem'),

-- Modul Dataset Sektoral & Workflow
(11, 'dataset.view', 'dataset', 'Lihat Dataset', 'Melihat dataset dalam lingkup yang diizinkan'),
(12, 'dataset.create', 'dataset', 'Buat Dataset', 'Membuat metadata dataset baru'),
(13, 'dataset.upload', 'dataset', 'Unggah Data Spasial', 'Mengunggah fitur spasial ke dataset'),
(14, 'dataset.update', 'dataset', 'Ubah Data Spasial', 'Mengubah data spasial draft'),
(15, 'dataset.submit', 'dataset', 'Ajukan Validasi', 'Mengajukan dataset draft ke tahap validasi'),
(16, 'dataset.review_opd', 'dataset', 'Review Internal OPD', 'Pemeriksaan awal oleh Admin OPD sebelum ke pusat'),
(17, 'dataset.validate', 'dataset', 'Validasi Teknis GIS', 'Memeriksa geometri, CRS, atribut, dan menyatakan lolos/tidak'),
(18, 'dataset.return_revision', 'dataset', 'Kembalikan Perbaikan', 'Mengembalikan dataset dengan catatan koreksi ke OPD'),
(19, 'dataset.approve', 'dataset', 'Setujui Dataset Terpusat', 'Memberikan persetujuan publikasi secara terpusat (Approver Data)'),
(20, 'dataset.reject', 'dataset', 'Tolak Dataset', 'Menolak pengajuan dataset dengan alasan resmi'),
(21, 'dataset.publish', 'dataset', 'Publikasi Layer', 'Menerbitkan dataset yang telah disetujui ke geoportal (Admin GIS)'),
(22, 'dataset.unpublish', 'dataset', 'Cabut Publikasi', 'Mencabut status terbit dataset dari geoportal'),
(23, 'dataset.archive', 'dataset', 'Arsipkan Dataset', 'Mengarsipkan dataset lama'),
(24, 'dataset.export', 'dataset', 'Unduh / Ekspor Dataset', 'Mengunduh data spasial GeoJSON/Shapefile/CSV'),

-- Modul Layer & GIS Service
(25, 'layer.view', 'layer', 'Lihat Katalog Layer', 'Melihat katalog layer geospasial'),
(26, 'layer.manage', 'layer', 'Kelola Konfigurasi Layer', 'Mengatur simbologi, legenda, dan konfigurasi tile'),
(27, 'metadata.manage', 'layer', 'Kelola Metadata', 'Mengelola standar metadata SNI/ISO geospasial'),

-- Modul Audit & Sistem
(28, 'audit.view', 'audit', 'Lihat Audit Log', 'Melihat jejak aktivitas dan riwayat perubahan'),
(29, 'report.view', 'report', 'Lihat Laporan & Statistik', 'Melihat dashboard eksekutif dan statistik capaian data'),
(30, 'system.manage', 'system', 'Kelola Konfigurasi Sistem', 'Mengatur konfigurasi global aplikasi')
ON DUPLICATE KEY UPDATE `label`=VALUES(`label`);

-- 3. SEED MASTER OPD RESMI PEMKAB MINAHASA SELATAN
INSERT INTO `ref_opd` (`id`, `kode_opd`, `nama_opd`, `email`, `is_active`) VALUES
(1, 'BAPELITBANGDA', 'Badan Perencanaan, Penelitian dan Pengembangan Daerah', 'bapelitbangda@minselkab.go.id', 1),
(2, 'DISKOMINFO', 'Dinas Komunikasi dan Informatika', 'diskominfo@minselkab.go.id', 1),
(3, 'DPUPR', 'Dinas Pekerjaan Umum dan Penataan Ruang', 'dpupr@minselkab.go.id', 1),
(4, 'DISPERKIM', 'Dinas Perumahan dan Kawasan Permukiman', 'disperkim@minselkab.go.id', 1),
(5, 'DIKBUD', 'Dinas Pendidikan dan Kebudayaan', 'dikbud@minselkab.go.id', 1),
(6, 'DINKES', 'Dinas Kesehatan', 'dinkes@minselkab.go.id', 1),
(7, 'DISTAN', 'Dinas Pertanian', 'distan@minselkab.go.id', 1),
(8, 'DISBUN', 'Dinas Perkebunan', 'disbun@minselkab.go.id', 1),
(9, 'DISKAN', 'Dinas Perikanan', 'diskan@minselkab.go.id', 1),
(10, 'DISPAR', 'Dinas Pariwisata', 'dispar@minselkab.go.id', 1),
(11, 'DLH', 'Dinas Lingkungan Hidup', 'dlh@minselkab.go.id', 1),
(12, 'BPBD', 'Badan Penanggulangan Bencana Daerah', 'bpbd@minselkab.go.id', 1),
(13, 'DISHUB', 'Dinas Perhubungan', 'dishub@minselkab.go.id', 1),
(14, 'DINSOS', 'Dinas Sosial', 'dinsos@minselkab.go.id', 1),
(15, 'DISDAG', 'Dinas Perdagangan', 'disdag@minselkab.go.id', 1),
(16, 'DINKOP_UKM', 'Dinas Koperasi dan Usaha Kecil Menengah', 'dinkop@minselkab.go.id', 1),
(17, 'DISPERINDAG', 'Dinas Perindustrian', 'disperindag@minselkab.go.id', 1),
(18, 'DPMPTSP', 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu', 'dpmptsp@minselkab.go.id', 1),
(19, 'DISDUKCAPIL', 'Dinas Kependudukan dan Pencatatan Sipil', 'disdukcapil@minselkab.go.id', 1),
(20, 'DISPORA', 'Dinas Pemuda dan Olahraga', 'dispora@minselkab.go.id', 1),
(21, 'DPMD', 'Dinas Pemberdayaan Masyarakat dan Desa', 'dpmd@minselkab.go.id', 1),
(22, 'DP3A', 'Dinas Pemberdayaan Perempuan dan Perlindungan Anak', 'dp3a@minselkab.go.id', 1),
(23, 'SATPOL_PP', 'Satuan Polisi Pamong Praja dan Pemadam Kebakaran', 'satpolpp@minselkab.go.id', 1),
(24, 'SETDA_PEM', 'Sekretariat Daerah - Bagian Tata Pemerintahan', 'pem@minselkab.go.id', 1),
(25, 'KECAMATAN', 'Pemerintah Kecamatan se-Minahasa Selatan', 'kecamatan@minselkab.go.id', 1),
(26, 'DESA_KEL', 'Pemerintah Desa dan Kelurahan se-Minahasa Selatan', 'desa@minselkab.go.id', 1)
ON DUPLICATE KEY UPDATE `nama_opd`=VALUES(`nama_opd`);

-- 4. SEED MASTER 28 KATEGORI GEOSPASIAL GEOMINSEL
INSERT INTO `kategori_layer` (`id`, `opd_id`, `kode`, `nama_kategori`, `deskripsi`, `icon`, `warna_default`, `is_active`) VALUES
(1, 24, 'wilayah_administrasi', 'Wilayah Administrasi', 'Batas kabupaten, kecamatan, desa/kelurahan, dusun/lingkungan, kode wilayah', 'border-style', '#2563eb', 1),
(2, 24, 'pemerintahan', 'Pemerintahan', 'Kantor bupati, wakil bupati, Setda, OPD, kantor camat, kantor desa, layanan publik', 'account-balance', '#0284c7', 1),
(3, 19, 'kependudukan', 'Kependudukan', 'Jumlah, persebaran, kepadatan penduduk, kelompok umur, KK per desa', 'users', '#6366f1', 1),
(4, 5, 'pendidikan', 'Pendidikan', 'PAUD, TK, SD, SMP, SMA/SMK, perguruan tinggi, pesantren', 'school', '#d97706', 1),
(5, 6, 'kesehatan', 'Kesehatan', 'Rumah sakit, puskesmas, pustu, posyandu, klinik, apotek, nakes', 'local-hospital', '#dc2626', 1),
(6, 3, 'infrastruktur_pu', 'Pekerjaan Umum dan Infrastruktur', 'Jalan, jembatan, drainase, irigasi, bendungan, air bersih, bangunan pemerintah', 'construction', '#475569', 1),
(7, 13, 'transportasi', 'Perhubungan dan Transportasi', 'Terminal, pelabuhan, dermaga, halte, rute angkutan, titik kemacetan', 'navigation', '#0284c7', 1),
(8, 7, 'pertanian', 'Pertanian dan Tanaman Pangan', 'Lahan sawah, komoditas pangan, luas tanam, panen, kelompok tani', 'wheat', '#84cc16', 1),
(9, 8, 'perkebunan', 'Perkebunan', 'Lahan perkebunan, komoditas kelapa/cengkeh/pala, produksi perkebunan', 'tree-palm', '#15803d', 1),
(10, 7, 'peternakan', 'Peternakan', 'Populasi ternak, sentra peternakan, RPH, poskeswan', 'paw', '#a16207', 1),
(11, 9, 'perikanan', 'Perikanan dan Kelautan', 'Tambak, kolam, keramba, TPI, pelabuhan perikanan, sentra nelayan', 'fish', '#06b6d4', 1),
(12, 10, 'pariwisata_budaya', 'Pariwisata dan Kebudayaan', 'Objek wisata alam, pantai, budaya, situs sejarah, event budaya Minsel', 'landscape', '#16a34a', 1),
(13, 11, 'lingkungan_hidup', 'Lingkungan Hidup dan Kehutanan', 'Kawasan hutan, tutupan lahan, RTH, TPA, TPS, titik pencemaran', 'trees', '#059669', 1),
(14, 12, 'kebencanaan', 'Kebencanaan', 'Daerah rawan longsor, banjir, tsunami, erupsi Soputan, posko, evakuasi', 'warning', '#ea580c', 1),
(15, 4, 'perumahan_permukiman', 'Perumahan dan Permukiman', 'Kawasan permukiman, RTLH, kawasan kumuh, sanitasi, fasum perumahan', 'home', '#78716c', 1),
(16, 15, 'perdagangan_industri', 'Perdagangan dan Perindustrian', 'Pasar tradisional, pusat perdagangan, industri kecil/menengah, sentra produksi', 'store', '#b45309', 1),
(17, 16, 'koperasi_umkm', 'Koperasi dan UMKM', 'Lokasi koperasi aktif, UMKM, sentra produk lokal Minahasa Selatan', 'briefcase', '#d97706', 1),
(18, 14, 'sosial_kesejahteraan', 'Sosial dan Kesejahteraan', 'Panti sosial, fasilitas rehabilitasi, penerima bantuan sosial agregat', 'heart-handshake', '#db2777', 1),
(19, 14, 'ketenagakerjaan', 'Ketenagakerjaan dan Transmigrasi', 'Balai Latihan Kerja (BLK), persebaran tenaga kerja agregat', 'hard-hat', '#e11d48', 1),
(20, 3, 'pertanahan_tataruang', 'Pertanahan dan Penataan Ruang', 'RTRW, RDTR, pola ruang, struktur ruang, peruntukan lahan Minsel', 'map-pin', '#4338ca', 1),
(21, 1, 'energi_sdm', 'Energi dan Sumber Daya Mineral', 'Jaringan transmisi listrik, gardu, potensi energi baru terbarukan', 'zap', '#ca8a04', 1),
(22, 2, 'kominfo_tik', 'Komunikasi dan Informatika', 'Menara telekomunikasi (BTS), titik blank spot, jaringan FO, wifi publik', 'wifi', '#0284c7', 1),
(23, 24, 'keagamaan', 'Keagamaan', 'Gereja, masjid, rumah ibadah, lembaga keagamaan se-Minsel', 'church', '#4f46e5', 1),
(24, 20, 'olahraga_pemuda', 'Olahraga dan Kepemudaan', 'Stadion, GOR, lapangan olahraga, pusat kegiatan kepemudaan', 'trophy', '#0891b2', 1),
(25, 22, 'perlindungan_anak', 'Perlindungan Perempuan dan Anak', 'Pusat layanan pengaduan dan perlindungan perempuan/anak', 'shield', '#be185d', 1),
(26, 23, 'keamanan_ketertiban', 'Keamanan dan Ketertiban', 'Pos Pol PP, pos damkar, pos kamling, pos terpadu', 'shield-alert', '#991b1b', 1),
(27, 1, 'geografis_dasar', 'Data Geografis Dasar', 'Elevasi, kontur lereng, sungai utama DAS, garis pantai, curah hujan', 'mountain', '#334155', 1),
(28, 18, 'investasi_potensi', 'Investasi dan Potensi Daerah', 'Peta potensi investasi unggulan, lahan prospektif, sentra ekonomi', 'trending-up', '#059669', 1)
ON DUPLICATE KEY UPDATE `nama_kategori`=VALUES(`nama_kategori`), `opd_id`=VALUES(`opd_id`);

-- 5. SEED PENGGUNA AWAL DENGAN ROLE MASING-MASING (PASSWORD DEFAULT: Password@2026!)
-- Hash BCrypt untuk 'Password@2026!'
INSERT INTO `users` (`id`, `opd_id`, `username`, `password_hash`, `nama_lengkap`, `role`, `is_active`) VALUES
(1, 2, 'superadmin', '$2y$10$wK1.VjN44wN92X1l12tQkeT5e6U7E49t3.x1WqZ0J9z0M3w6yY0aO', 'Superadmin Diskominfo GeoMinsel', 'SUPERADMIN', 1),
(2, 2, 'admin_gis', '$2y$10$wK1.VjN44wN92X1l12tQkeT5e6U7E49t3.x1WqZ0J9z0M3w6yY0aO', 'Administrator GIS Pemkab Minsel', 'ADMIN_OPD', 1),
(3, 6, 'admin_dinkes', '$2y$10$wK1.VjN44wN92X1l12tQkeT5e6U7E49t3.x1WqZ0J9z0M3w6yY0aO', 'Admin OPD Dinas Kesehatan', 'ADMIN_OPD', 1),
(4, 6, 'operator_dinkes', '$2y$10$wK1.VjN44wN92X1l12tQkeT5e6U7E49t3.x1WqZ0J9z0M3w6yY0aO', 'Operator Data Fasilitas Kesehatan', 'OPERATOR_KECAMATAN', 1),
(5, 2, 'validator_gis', '$2y$10$wK1.VjN44wN92X1l12tQkeT5e6U7E49t3.x1WqZ0J9z0M3w6yY0aO', 'Validator Teknis Spasial', 'SUPERADMIN', 1),
(6, 1, 'approver_pusat', '$2y$10$wK1.VjN44wN92X1l12tQkeT5e6U7E49t3.x1WqZ0J9z0M3w6yY0aO', 'Approver Data Bappeda Minsel', 'SUPERADMIN', 1),
(7, 24, 'pimpinan_bupati', '$2y$10$wK1.VjN44wN92X1l12tQkeT5e6U7E49t3.x1WqZ0J9z0M3w6yY0aO', 'Bupati / Pimpinan Daerah', 'SUPERADMIN', 1)
ON DUPLICATE KEY UPDATE `nama_lengkap`=VALUES(`nama_lengkap`);

-- 6. SEED RELASI MULTI-ROLE
INSERT INTO `user_roles` (`user_id`, `role_id`) VALUES
(1, 1), -- Superadmin -> Superadmin
(2, 2), -- Admin GIS -> Admin GIS
(3, 3), -- Admin Dinkes -> Admin OPD
(4, 4), -- Operator Dinkes -> Operator OPD
(5, 5), -- Validator GIS -> Validator GIS
(6, 6), -- Approver Pusat -> Approver Data
(7, 7)  -- Pimpinan -> Pimpinan / Executive Viewer
ON DUPLICATE KEY UPDATE `role_id`=VALUES(`role_id`);

