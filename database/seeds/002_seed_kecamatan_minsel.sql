-- ==============================================================================
-- Data Awal (Seed) Hierarki Administrasi Kabupaten Minahasa Selatan
-- SRS: EPSG:4326 (WGS 84)
-- Level: Kabupaten (Minahasa Selatan) -> 17 Kecamatan -> Desa/Kelurahan
-- ==============================================================================

-- 1. LEVEL 1: KABUPATEN MINAHASA SELATAN (Wilayah Induk)
INSERT INTO `ref_kabupaten` (`id`, `kode_kabupaten`, `nama_kabupaten`, `ibukota`, `luas_wilayah_km2`, `deskripsi`, `geom`) VALUES
(1, '71.05', 'Minahasa Selatan', 'Amurang', 1456.46, 'Kabupaten Minahasa Selatan, Provinsi Sulawesi Utara',
 ST_GeomFromText('MULTIPOLYGON(((124.30 0.80, 124.75 0.80, 124.75 1.35, 124.30 1.35, 124.30 0.80)))', 4326))
ON DUPLICATE KEY UPDATE `nama_kabupaten`=VALUES(`nama_kabupaten`), `luas_wilayah_km2`=VALUES(`luas_wilayah_km2`);

-- 2. LEVEL 2: 17 KECAMATAN MINAHASA SELATAN (Terhubung ke kabupaten_id = 1)
INSERT INTO `ref_kecamatan` (`id`, `kabupaten_id`, `kode_kecamatan`, `nama_kecamatan`, `ibukota_kecamatan`, `luas_wilayah_km2`, `geom`) VALUES
(1, 1, '71.05.01', 'Modoinding', 'Linelean', 53.61, ST_GeomFromText('MULTIPOLYGON(((124.41 0.81, 124.49 0.81, 124.49 0.90, 124.41 0.90, 124.41 0.81)))', 4326)),
(2, 1, '71.05.02', 'Tompaso Baru', 'Tompaso Baru Dua', 126.11, ST_GeomFromText('MULTIPOLYGON(((124.42 0.90, 124.52 0.90, 124.52 1.01, 124.42 1.01, 124.42 0.90)))', 4326)),
(3, 1, '71.05.03', 'Ranoyapo', 'Pontak', 93.71, ST_GeomFromText('MULTIPOLYGON(((124.48 0.95, 124.58 0.95, 124.58 1.05, 124.48 1.05, 124.48 0.95)))', 4326)),
(4, 1, '71.05.04', 'Motoling', 'Motoling', 26.68, ST_GeomFromText('MULTIPOLYGON(((124.50 1.02, 124.57 1.02, 124.57 1.08, 124.50 1.08, 124.50 1.02)))', 4326)),
(5, 1, '71.05.05', 'Sinonsayang', 'Ongkaw Dua', 133.40, ST_GeomFromText('MULTIPOLYGON(((124.30 1.00, 124.45 1.00, 124.45 1.15, 124.30 1.15, 124.30 1.00)))', 4326)),
(6, 1, '71.05.06', 'Tenga', 'Pakuweru', 114.77, ST_GeomFromText('MULTIPOLYGON(((124.40 1.08, 124.53 1.08, 124.53 1.20, 124.40 1.20, 124.40 1.08)))', 4326)),
(7, 1, '71.05.07', 'Amurang', 'Buyungon', 10.66, ST_GeomFromText('MULTIPOLYGON(((124.55 1.16, 124.60 1.16, 124.60 1.22, 124.55 1.22, 124.55 1.16)))', 4326)),
(8, 1, '71.05.08', 'Tumpaan', 'Tumpaan Satu', 73.16, ST_GeomFromText('MULTIPOLYGON(((124.60 1.20, 124.68 1.20, 124.68 1.28, 124.60 1.28, 124.60 1.20)))', 4326)),
(9, 1, '71.05.09', 'Tareran', 'Rumoong Atas', 58.74, ST_GeomFromText('MULTIPOLYGON(((124.65 1.23, 124.74 1.23, 124.74 1.30, 124.65 1.30, 124.65 1.23)))', 4326)),
(10, 1, '71.05.10', 'Kumelembuai', 'Kumelembuai', 45.47, ST_GeomFromText('MULTIPOLYGON(((124.54 1.07, 124.62 1.07, 124.62 1.14, 124.54 1.14, 124.54 1.07)))', 4326)),
(11, 1, '71.05.11', 'Maesaan', 'Tumani', 47.78, ST_GeomFromText('MULTIPOLYGON(((124.43 0.86, 124.51 0.86, 124.51 0.94, 124.43 0.94, 124.43 0.86)))', 4326)),
(12, 1, '71.05.12', 'Amurang Barat', 'Kawangkoan Bawah', 88.58, ST_GeomFromText('MULTIPOLYGON(((124.48 1.13, 124.56 1.13, 124.56 1.21, 124.48 1.21, 124.48 1.13)))', 4326)),
(13, 1, '71.05.13', 'Amurang Timur', 'Malenos Baru', 178.26, ST_GeomFromText('MULTIPOLYGON(((124.57 1.12, 124.68 1.12, 124.68 1.23, 124.57 1.23, 124.57 1.12)))', 4326)),
(14, 1, '71.05.14', 'Tatapaan', 'Paslaten', 65.48, ST_GeomFromText('MULTIPOLYGON(((124.58 1.25, 124.70 1.25, 124.70 1.33, 124.58 1.33, 124.58 1.25)))', 4326)),
(15, 1, '71.05.15', 'Suluun Tareran', 'Suluun Empat', 32.22, ST_GeomFromText('MULTIPOLYGON(((124.66 1.19, 124.73 1.19, 124.73 1.25, 124.66 1.25, 124.66 1.19)))', 4326)),
(16, 1, '71.05.16', 'Motoling Barat', 'Raanan Baru', 113.67, ST_GeomFromText('MULTIPOLYGON(((124.44 1.00, 124.52 1.00, 124.52 1.10, 124.44 1.10, 124.44 1.00)))', 4326)),
(17, 1, '71.05.17', 'Motoling Timur', 'Wanga', 55.45, ST_GeomFromText('MULTIPOLYGON(((124.53 1.01, 124.61 1.01, 124.61 1.09, 124.53 1.09, 124.53 1.01)))', 4326))
ON DUPLICATE KEY UPDATE `nama_kecamatan`=VALUES(`nama_kecamatan`), `luas_wilayah_km2`=VALUES(`luas_wilayah_km2`);

-- 3. LEVEL 3: SAMPEL DESA / KELURAHAN (Memiliki relasi eksplisit ke kecamatan_id DAN kabupaten_id = 1)
INSERT INTO `ref_desa` (`id`, `kabupaten_id`, `kecamatan_id`, `kode_desa`, `nama_desa`, `tipe`, `luas_wilayah_km2`, `geom`) VALUES
-- Kecamatan Amurang (id = 7, kabupaten_id = 1)
(1, 1, 7, '71.05.07.1001', 'Buyungon', 'KELURAHAN', 1.85, ST_GeomFromText('MULTIPOLYGON(((124.56 1.17, 124.59 1.17, 124.59 1.19, 124.56 1.19, 124.56 1.17)))', 4326)),
(2, 1, 7, '71.05.07.1002', 'Pondang', 'KELURAHAN', 2.10, ST_GeomFromText('MULTIPOLYGON(((124.57 1.17, 124.60 1.17, 124.60 1.20, 124.57 1.20, 124.57 1.17)))', 4326)),
(3, 1, 7, '71.05.07.1003', 'Ranomeida', 'KELURAHAN', 1.65, ST_GeomFromText('MULTIPOLYGON(((124.56 1.18, 124.58 1.18, 124.58 1.20, 124.56 1.20, 124.56 1.18)))', 4326)),
-- Kecamatan Tumpaan (id = 8, kabupaten_id = 1)
(4, 1, 8, '71.05.08.2001', 'Tumpaan Satu', 'DESA', 5.20, ST_GeomFromText('MULTIPOLYGON(((124.61 1.21, 124.65 1.21, 124.65 1.25, 124.61 1.25, 124.61 1.21)))', 4326)),
(5, 1, 8, '71.05.08.2002', 'Popontolen', 'DESA', 6.40, ST_GeomFromText('MULTIPOLYGON(((124.62 1.22, 124.67 1.22, 124.67 1.27, 124.62 1.27, 124.62 1.22)))', 4326)),
-- Kecamatan Modoinding (id = 1, kabupaten_id = 1)
(6, 1, 1, '71.05.01.2001', 'Linelean', 'DESA', 7.80, ST_GeomFromText('MULTIPOLYGON(((124.42 0.82, 124.46 0.82, 124.46 0.86, 124.42 0.86, 124.42 0.82)))', 4326))
ON DUPLICATE KEY UPDATE `nama_desa`=VALUES(`nama_desa`), `kabupaten_id`=VALUES(`kabupaten_id`);

-- 4. ORGANISASI PERANGKAT DAERAH (OPD)
INSERT INTO `ref_opd` (`id`, `kode_opd`, `nama_opd`, `email`, `is_active`) VALUES
(1, 'BAPELITBANGDA', 'Badan Perencanaan, Penelitian dan Pengembangan Daerah', 'bapelitbangda@minselkab.go.id', 1),
(2, 'DISKOMINFO', 'Dinas Komunikasi dan Informatika', 'diskominfo@minselkab.go.id', 1),
(3, 'PUPR', 'Dinas Pekerjaan Umum dan Penataan Ruang', 'pupr@minselkab.go.id', 1),
(4, 'DINKES', 'Dinas Kesehatan', 'dinkes@minselkab.go.id', 1),
(5, 'BPBD', 'Badan Penanggulangan Bencana Daerah', 'bpbd@minselkab.go.id', 1)
ON DUPLICATE KEY UPDATE `nama_opd`=VALUES(`nama_opd`);

-- 5. KATEGORI LAYER TEMATIK
INSERT INTO `kategori_layer` (`id`, `opd_id`, `kode`, `nama_kategori`, `deskripsi`, `icon`, `warna_default`, `is_active`) VALUES
(1, 1, 'admin_wilayah', 'Batas Administrasi', 'Batas wilayah Kabupaten, Kecamatan, dan Desa', 'border-style', '#2563eb', 1),
(2, 2, 'pemerintahan', 'Kantor Pemerintahan', 'Kantor Bupati, Dinas, dan Kantor Camat se-Minsel', 'account-balance', '#0284c7', 1),
(3, 4, 'kesehatan', 'Fasilitas Kesehatan', 'RSUD Amurang, Puskesmas, dan Poskesdes', 'local-hospital', '#dc2626', 1),
(4, 3, 'infrastruktur', 'Infrastruktur & Jaringan Jalan', 'Jaringan jalan kabupaten, jembatan, dan irigasi', 'road', '#475569', 1),
(5, 1, 'pariwisata', 'Pariwisata & Budaya', 'Destinasi wisata bahari, alam, dan budaya', 'landscape', '#16a34a', 1),
(6, 5, 'kebencanaan', 'Titik Rawan Bencana', 'Zona rawan longsor, banjir pasang, dan erupsi Soputan', 'warning', '#ea580c', 1)
ON DUPLICATE KEY UPDATE `nama_kategori`=VALUES(`nama_kategori`);

-- 6. OBJEK SPASIAL (POINT / LINE / POLYGON) TERIKAT HIERARKI KABUPATEN -> KECAMATAN -> DESA
INSERT INTO `objek_spasial` (`id`, `kategori_id`, `kabupaten_id`, `kecamatan_id`, `desa_id`, `opd_id`, `nama_objek`, `tipe_geometri`, `deskripsi`, `foto_url`, `atribut_tambahan`, `geom`, `is_active`) VALUES
(1, 2, 1, 7, 2, 2, 'Kantor Bupati Minahasa Selatan', 'POINT',
 'Pusat Pemerintahan Kabupaten Minahasa Selatan, Jl. Trans Sulawesi',
 'https://minselkab.go.id/kantor-bupati.jpg',
 '{"pj": "Sekretariat Daerah", "status": "Aktif", "tingkat": "Kabupaten"}',
 ST_GeomFromText('POINT(124.5772 1.1824)', 4326), 1),

(2, 3, 1, 7, 2, 4, 'RSUD Amurang', 'POINT',
 'Rumah Sakit Umum Daerah Kelas C Rujukan Kabupaten Minahasa Selatan',
 'https://minselkab.go.id/rsud-amurang.jpg',
 '{"tipe": "Kelas C", "kapasitas": "120 Tempat Tidur", "layanan": "IGD 24 Jam"}',
 ST_GeomFromText('POINT(124.5821 1.1795)', 4326), 1),

(3, 5, 1, 7, 1, 1, 'Pantai Alar Amurang', 'POINT',
 'Kawasan Wisata Bahari dan Kuliner Pesisir Teluk Amurang',
 'https://minselkab.go.id/pantai-alar.jpg',
 '{"jenis": "Wisata Bahari", "tiket": "Gratis"}',
 ST_GeomFromText('POINT(124.5684 1.1887)', 4326), 1),

(4, 2, 1, 8, 4, 2, 'Kantor Camat Tumpaan', 'POINT',
 'Pusat Pelayanan Administrasi Pemerintahan Kecamatan Tumpaan',
 'https://minselkab.go.id/camat-tumpaan.jpg',
 '{"camat": "Terry Lolowang, SE", "tingkat": "Kecamatan"}',
 ST_GeomFromText('POINT(124.6284 1.2312)', 4326), 1),

(5, 3, 1, 1, 6, 4, 'Puskesmas Modoinding', 'POINT',
 'Puskesmas Rawat Inap Wilayah Dataran Tinggi Modoinding',
 'https://minselkab.go.id/pkm-modoinding.jpg',
 '{"tipe": "Rawat Inap", "desa": "Linelean"}',
 ST_GeomFromText('POINT(124.4412 0.8385)', 4326), 1)
ON DUPLICATE KEY UPDATE `nama_objek`=VALUES(`nama_objek`), `kecamatan_id`=VALUES(`kecamatan_id`);
