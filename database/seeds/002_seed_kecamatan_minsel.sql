-- ==============================================================================
-- Data Awal (Seed): 17 Kecamatan di Kabupaten Minahasa Selatan
-- SRS: EPSG:4326 (WGS 84)
-- Koordinat Centroid & Bounding Sample
-- ==============================================================================

-- 1. Insert Data 17 Kecamatan Minahasa Selatan
INSERT INTO `ref_kecamatan` (`kode_kecamatan`, `nama_kecamatan`, `ibukota_kecamatan`, `luas_wilayah_km2`, `geom`) VALUES
('71.05.01', 'Modoinding', 'Linelean', 53.61, ST_GeomFromText('MULTIPOLYGON(((124.41 0.81, 124.49 0.81, 124.49 0.90, 124.41 0.90, 124.41 0.81)))', 4326)),
('71.05.02', 'Tompaso Baru', 'Tompaso Baru Dua', 126.11, ST_GeomFromText('MULTIPOLYGON(((124.42 0.90, 124.52 0.90, 124.52 1.01, 124.42 1.01, 124.42 0.90)))', 4326)),
('71.05.03', 'Ranoyapo', 'Pontak', 93.71, ST_GeomFromText('MULTIPOLYGON(((124.48 0.95, 124.58 0.95, 124.58 1.05, 124.48 1.05, 124.48 0.95)))', 4326)),
('71.05.04', 'Motoling', 'Motoling', 26.68, ST_GeomFromText('MULTIPOLYGON(((124.50 1.02, 124.57 1.02, 124.57 1.08, 124.50 1.08, 124.50 1.02)))', 4326)),
('71.05.05', 'Sinonsayang', 'Ongkaw Dua', 133.40, ST_GeomFromText('MULTIPOLYGON(((124.30 1.00, 124.45 1.00, 124.45 1.15, 124.30 1.15, 124.30 1.00)))', 4326)),
('71.05.06', 'Tenga', 'Pakuweru', 114.77, ST_GeomFromText('MULTIPOLYGON(((124.40 1.08, 124.53 1.08, 124.53 1.20, 124.40 1.20, 124.40 1.08)))', 4326)),
('71.05.07', 'Amurang', 'Buyungon', 10.66, ST_GeomFromText('MULTIPOLYGON(((124.55 1.16, 124.60 1.16, 124.60 1.22, 124.55 1.22, 124.55 1.16)))', 4326)),
('71.05.08', 'Tumpaan', 'Tumpaan Satu', 73.16, ST_GeomFromText('MULTIPOLYGON(((124.60 1.20, 124.68 1.20, 124.68 1.28, 124.60 1.28, 124.60 1.20)))', 4326)),
('71.05.09', 'Tareran', 'Rumoong Atas', 58.74, ST_GeomFromText('MULTIPOLYGON(((124.65 1.23, 124.74 1.23, 124.74 1.30, 124.65 1.30, 124.65 1.23)))', 4326)),
('71.05.10', 'Kumelembuai', 'Kumelembuai', 45.47, ST_GeomFromText('MULTIPOLYGON(((124.54 1.07, 124.62 1.07, 124.62 1.14, 124.54 1.14, 124.54 1.07)))', 4326)),
('71.05.11', 'Maesaan', 'Tumani', 47.78, ST_GeomFromText('MULTIPOLYGON(((124.43 0.86, 124.51 0.86, 124.51 0.94, 124.43 0.94, 124.43 0.86)))', 4326)),
('71.05.12', 'Amurang Barat', 'Kawangkoan Bawah', 88.58, ST_GeomFromText('MULTIPOLYGON(((124.48 1.13, 124.56 1.13, 124.56 1.21, 124.48 1.21, 124.48 1.13)))', 4326)),
('71.05.13', 'Amurang Timur', 'Malenos Baru', 178.26, ST_GeomFromText('MULTIPOLYGON(((124.57 1.12, 124.68 1.12, 124.68 1.23, 124.57 1.23, 124.57 1.12)))', 4326)),
('71.05.14', 'Tatapaan', 'Paslaten', 65.48, ST_GeomFromText('MULTIPOLYGON(((124.58 1.25, 124.70 1.25, 124.70 1.33, 124.58 1.33, 124.58 1.25)))', 4326)),
('71.05.15', 'Suluun Tareran', 'Suluun Empat', 32.22, ST_GeomFromText('MULTIPOLYGON(((124.66 1.19, 124.73 1.19, 124.73 1.25, 124.66 1.25, 124.66 1.19)))', 4326)),
('71.05.16', 'Motoling Barat', 'Raanan Baru', 113.67, ST_GeomFromText('MULTIPOLYGON(((124.44 1.00, 124.52 1.00, 124.52 1.10, 124.44 1.10, 124.44 1.00)))', 4326)),
('71.05.17', 'Motoling Timur', 'Wanga', 55.45, ST_GeomFromText('MULTIPOLYGON(((124.53 1.01, 124.61 1.01, 124.61 1.09, 124.53 1.09, 124.53 1.01)))', 4326))
ON DUPLICATE KEY UPDATE `nama_kecamatan`=VALUES(`nama_kecamatan`), `luas_wilayah_km2`=VALUES(`luas_wilayah_km2`);

-- 2. Insert Kategori Layer Tematik
INSERT INTO `kategori_layer` (`kode`, `nama_kategori`, `deskripsi`, `icon`, `warna_default`, `is_active`) VALUES
('admin_wilayah', 'Batas Administrasi', 'Batas wilayah Kabupaten, Kecamatan, dan Desa', 'border-style', '#2563eb', 1),
('pemerintahan', 'Kantor Pemerintahan', 'Kantor Bupati, Bapelitbangda, Dinas, dan Kantor Camat', 'account-balance', '#0284c7', 1),
('kesehatan', 'Fasilitas Kesehatan', 'RSUD Amurang, Puskesmas, dan Poskesdes se-Minsel', 'local-hospital', '#dc2626', 1),
('pendidikan', 'Sarana Pendidikan', 'Sekolah Dasar, SMP, SMA/SMK di Minahasa Selatan', 'school', '#d97706', 1),
('pariwisata', 'Pariwisata & Budaya', 'Objek wisata pantai, air terjun, dan situs budaya', 'landscape', '#16a34a', 1),
('kebencanaan', 'Titik Rawan Bencana', 'Zona rawan longsor, banjir pasang, dan erupsi Soputan', 'warning', '#ea580c', 1)
ON DUPLICATE KEY UPDATE `nama_kategori`=VALUES(`nama_kategori`);

-- 3. Insert Contoh Objek Titik Spasial (Point) Fasilitas Penting di Minsel
INSERT INTO `objek_spasial` (`kategori_id`, `nama_objek`, `tipe_geometri`, `deskripsi`, `foto_url`, `atribut_tambahan`, `geom`, `is_active`) VALUES
(2, 'Kantor Bupati Minahasa Selatan', 'POINT', 'Pusat Pemerintahan Kabupaten Minahasa Selatan, Jl. Trans Sulawesi Kel. Pondang', 'https://minselkab.go.id/kantor-bupati.jpg', '{"pj": "Sekretariat Daerah", "status": "Aktif"}', ST_GeomFromText('POINT(124.5772 1.1824)', 4326), 1),
(3, 'RSUD Amurang', 'POINT', 'Rumah Sakit Umum Daerah Minahasa Selatan', 'https://minselkab.go.id/rsud-amurang.jpg', '{"tipe": "Kelas C", "kapasitas": "120 Tempat Tidur"}', ST_GeomFromText('POINT(124.5821 1.1795)', 4326), 1),
(5, 'Pantai Alar Amurang', 'POINT', 'Kawasan Wisata Bahari dan Kuliner Pesisir Teluk Amurang', 'https://minselkab.go.id/pantai-alar.jpg', '{"jenis": "Wisata Bahari", "tiket": "Gratis"}', ST_GeomFromText('POINT(124.5684 1.1887)', 4326), 1);
