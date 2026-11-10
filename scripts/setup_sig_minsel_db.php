<?php
$mysqli = new mysqli('127.0.0.1', 'root', '', '', 3306);
if ($mysqli->connect_error) {
    echo "Connection failed: " . $mysqli->connect_error . "\n";
    exit(1);
}

// 1. Create database sig_minsel if not exists
$sql = "CREATE DATABASE IF NOT EXISTS `sig_minsel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($mysqli->query($sql)) {
    echo "SUCCESS: Database `sig_minsel` ready!\n";
} else {
    echo "ERROR creating database: " . $mysqli->error . "\n";
    exit(1);
}

$mysqli->select_db('sig_minsel');
$mysqli->query("SET FOREIGN_KEY_CHECKS = 0;");

// Clear existing tables to ensure clean seed
$tablesToClear = ['objek_spasial', 'user_roles', 'users', 'kategori_layer', 'ref_opd', 'ref_desa', 'ref_kecamatan', 'ref_kabupaten', 'roles', 'permissions', 'role_permissions'];
foreach ($tablesToClear as $t) {
    $mysqli->query("TRUNCATE TABLE `$t`");
}

function executeSqlFile($mysqli, $filePath) {
    echo "Executing $filePath...\n";
    $content = file_get_contents($filePath);
    if (!$content) {
        echo "Error reading $filePath\n";
        return false;
    }
    
    if ($mysqli->multi_query($content)) {
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
        
        if ($mysqli->errno) {
            echo "   Warning/Error in $filePath: " . $mysqli->error . "\n";
            return false;
        } else {
            echo "   [OK] Completed $filePath successfully.\n";
            return true;
        }
    } else {
        echo "   [FAIL] multi_query failed: " . $mysqli->error . "\n";
        return false;
    }
}

// Execute in strict logical order
executeSqlFile($mysqli, 'database/migrations/001_create_spatial_tables.sql');
executeSqlFile($mysqli, 'database/migrations/002_fase2_roles_workflow_kategori.sql');
executeSqlFile($mysqli, 'database/seeds/003_seed_fase2_roles_and_categories.sql');
executeSqlFile($mysqli, 'database/seeds/002_seed_kecamatan_minsel.sql');

// Seed Objek Spasial sample
echo "Seeding objek_spasial samples...\n";
$objekSql = "INSERT INTO `objek_spasial` (`id`, `kategori_id`, `kabupaten_id`, `kecamatan_id`, `desa_id`, `opd_id`, `nama_objek`, `tipe_geometri`, `deskripsi`, `foto_url`, `atribut_tambahan`, `geom`, `is_active`) VALUES
(1, 2, 1, 7, 2, 24, 'Kantor Bupati Minahasa Selatan', 'POINT', 'Pusat Pemerintahan Kabupaten Minahasa Selatan, Jl. Trans Sulawesi', 'https://minselkab.go.id/kantor-bupati.jpg', '{\"pj\": \"Sekretariat Daerah\", \"status\": \"Aktif\", \"tingkat\": \"Kabupaten\"}', ST_GeomFromText('POINT(124.5772 1.1824)', 4326, 'axis-order=long-lat'), 1),
(2, 5, 1, 7, 2, 6, 'RSUD Amurang', 'POINT', 'Rumah Sakit Umum Daerah Kelas C Rujukan Kabupaten Minahasa Selatan', 'https://minselkab.go.id/rsud-amurang.jpg', '{\"tipe\": \"Kelas C\", \"kapasitas\": \"120 Tempat Tidur\", \"layanan\": \"IGD 24 Jam\"}', ST_GeomFromText('POINT(124.5821 1.1795)', 4326, 'axis-order=long-lat'), 1),
(3, 12, 1, 7, 1, 10, 'Pantai Alar Amurang', 'POINT', 'Kawasan Wisata Bahari dan Kuliner Pesisir Teluk Amurang', 'https://minselkab.go.id/pantai-alar.jpg', '{\"jenis\": \"Wisata Bahari\", \"tiket\": \"Gratis\"}', ST_GeomFromText('POINT(124.5684 1.1887)', 4326, 'axis-order=long-lat'), 1),
(4, 2, 1, 8, 4, 25, 'Kantor Camat Tumpaan', 'POINT', 'Pusat Pelayanan Administrasi Pemerintahan Kecamatan Tumpaan', 'https://minselkab.go.id/camat-tumpaan.jpg', '{\"camat\": \"Terry Lolowang, SE\", \"tingkat\": \"Kecamatan\"}', ST_GeomFromText('POINT(124.6284 1.2312)', 4326, 'axis-order=long-lat'), 1),
(5, 5, 1, 1, 6, 6, 'Puskesmas Modoinding', 'POINT', 'Puskesmas Rawat Inap Wilayah Dataran Tinggi Modoinding', 'https://minselkab.go.id/pkm-modoinding.jpg', '{\"tipe\": \"Rawat Inap\", \"desa\": \"Linelean\"}', ST_GeomFromText('POINT(124.4412 0.8385)', 4326, 'axis-order=long-lat'), 1)
ON DUPLICATE KEY UPDATE `nama_objek`=VALUES(`nama_objek`), `kecamatan_id`=VALUES(`kecamatan_id`);";

if ($mysqli->query($objekSql)) {
    echo "   [OK] Sample objek_spasial seeded successfully!\n";
} else {
    echo "   [WARN] objek_spasial query: " . $mysqli->error . "\n";
}

$mysqli->query("SET FOREIGN_KEY_CHECKS = 1;");

// Summary
$res = $mysqli->query("SHOW TABLES");
echo "\n=========================================\n";
echo "DATABASE `sig_minsel` STATUS REPORT:\n";
echo "=========================================\n";
while ($row = $res->fetch_array()) {
    $t = $row[0];
    $cRes = $mysqli->query("SELECT COUNT(*) FROM `$t`");
    $cnt = $cRes ? $cRes->fetch_row()[0] : 'err';
    echo sprintf(" - %-26s : %d baris data\n", $t, $cnt);
}
echo "=========================================\n";
