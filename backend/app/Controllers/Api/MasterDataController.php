<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller Manajemen Master Data: OPD & Kategori Spasial Tematik
 * Mendukung sinkronisasi basis data MySQL dengan fallback JSON persistence di WRITEPATH
 */
class MasterDataController extends BaseApiController
{
    /**
     * Master Referensi OPD Default Pemkab Minahasa Selatan
     */
    protected static array $defaultOpds = [
        [
            'id' => 1,
            'kode_opd' => 'BAPELITBANGDA',
            'nama_opd' => 'Badan Perencanaan, Penelitian dan Pengembangan Daerah',
            'singkatan' => 'Bappelitbangda',
            'email' => 'bapelitbangda@minselkab.go.id',
            'telepon' => '(0430) 21150',
            'alamat' => 'Kompleks Kantor Bupati Minsel, Jl. Trans Sulawesi, Pondang',
            'pic' => 'Kepala Badan Bappelitbangda',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 2,
            'kode_opd' => 'DISKOMINFO',
            'nama_opd' => 'Dinas Komunikasi dan Informatika',
            'singkatan' => 'Diskominfo',
            'email' => 'diskominfo@minselkab.go.id',
            'telepon' => '(0430) 21123',
            'alamat' => 'Kompleks Kantor Bupati Minsel, Jl. Trans Sulawesi, Pondang',
            'pic' => 'Kepala Dinas Kominfo',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 3,
            'kode_opd' => 'DPUPR',
            'nama_opd' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
            'singkatan' => 'PUPR',
            'email' => 'dpupr@minselkab.go.id',
            'telepon' => '(0430) 21245',
            'alamat' => 'Jl. Trans Sulawesi No. 45, Amurang',
            'pic' => 'Kepala Dinas PUPR',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 4,
            'kode_opd' => 'DISPERKIM',
            'nama_opd' => 'Dinas Perumahan dan Kawasan Permukiman',
            'singkatan' => 'Disperkim',
            'email' => 'disperkim@minselkab.go.id',
            'telepon' => '(0430) 21333',
            'alamat' => 'Jl. Siswa, Amurang Barat',
            'pic' => 'Kepala Dinas Perkim',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 5,
            'kode_opd' => 'DIKBUD',
            'nama_opd' => 'Dinas Pendidikan dan Kebudayaan',
            'singkatan' => 'Dikbud',
            'email' => 'dikbud@minselkab.go.id',
            'telepon' => '(0430) 21008',
            'alamat' => 'Jl. Trans Sulawesi, Buyungon, Amurang',
            'pic' => 'Kepala Dinas Dikbud',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 6,
            'kode_opd' => 'DINKES',
            'nama_opd' => 'Dinas Kesehatan',
            'singkatan' => 'Dinkes',
            'email' => 'dinkes@minselkab.go.id',
            'telepon' => '(0430) 22019',
            'alamat' => 'Jl. Trans Sulawesi, KM 55, Teep Trans, Amurang Barat',
            'pic' => 'Kepala Dinas Kesehatan',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 7,
            'kode_opd' => 'DISTAN',
            'nama_opd' => 'Dinas Pertanian',
            'singkatan' => 'Distan',
            'email' => 'distan@minselkab.go.id',
            'telepon' => '(0430) 21556',
            'alamat' => 'Jl. Trans Sulawesi, Pondang, Amurang Timur',
            'pic' => 'Kepala Dinas Pertanian',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 8,
            'kode_opd' => 'DISBUN',
            'nama_opd' => 'Dinas Perkebunan',
            'singkatan' => 'Disbun',
            'email' => 'disbun@minselkab.go.id',
            'telepon' => '(0430) 21789',
            'alamat' => 'Jl. Trans Sulawesi, Amurang Timur',
            'pic' => 'Kepala Dinas Perkebunan',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 9,
            'kode_opd' => 'DISKAN',
            'nama_opd' => 'Dinas Perikanan',
            'singkatan' => 'Diskan',
            'email' => 'diskan@minselkab.go.id',
            'telepon' => '(0430) 21455',
            'alamat' => 'Pesisir Pantai Teluk Amurang, Bitung Amurang',
            'pic' => 'Kepala Dinas Perikanan',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 10,
            'kode_opd' => 'DISPAR',
            'nama_opd' => 'Dinas Pariwisata',
            'singkatan' => 'Dispar',
            'email' => 'dispar@minselkab.go.id',
            'telepon' => '(0430) 21677',
            'alamat' => 'Kawasan Wisata Ilo-ilo, Wawalintouan, Tenga',
            'pic' => 'Kepala Dinas Pariwisata',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 11,
            'kode_opd' => 'DLH',
            'nama_opd' => 'Dinas Lingkungan Hidup',
            'singkatan' => 'DLH',
            'email' => 'dlh@minselkab.go.id',
            'telepon' => '(0430) 21890',
            'alamat' => 'Kompleks Kantor Bupati Minsel, Pondang',
            'pic' => 'Kepala Dinas Lingkungan Hidup',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 12,
            'kode_opd' => 'BPBD',
            'nama_opd' => 'Badan Penanggulangan Bencana Daerah',
            'singkatan' => 'BPBD',
            'email' => 'bpbd@minselkab.go.id',
            'telepon' => '(0430) 22119',
            'alamat' => 'Pusat Pengendalian Operasi BPBD, Jl. Trans Sulawesi',
            'pic' => 'Kepala Pelaksana BPBD',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 13,
            'kode_opd' => 'DISHUB',
            'nama_opd' => 'Dinas Perhubungan',
            'singkatan' => 'Dishub',
            'email' => 'dishub@minselkab.go.id',
            'telepon' => '(0430) 21999',
            'alamat' => 'Terminal Mobongo, Amurang Barat',
            'pic' => 'Kepala Dinas Perhubungan',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 14,
            'kode_opd' => 'DINSOS',
            'nama_opd' => 'Dinas Sosial',
            'singkatan' => 'Dinsos',
            'email' => 'dinsos@minselkab.go.id',
            'telepon' => '(0430) 21411',
            'alamat' => 'Jl. Siswa No. 12, Amurang Barat',
            'pic' => 'Kepala Dinas Sosial',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 15,
            'kode_opd' => 'DISDAG',
            'nama_opd' => 'Dinas Perdagangan',
            'singkatan' => 'Disdag',
            'email' => 'disdag@minselkab.go.id',
            'telepon' => '(0430) 21822',
            'alamat' => 'Kompleks Pasar 54 Amurang, Uwuran Satu',
            'pic' => 'Kepala Dinas Perdagangan',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 16,
            'kode_opd' => 'DINKOP_UKM',
            'nama_opd' => 'Dinas Koperasi dan Usaha Kecil Menengah',
            'singkatan' => 'Dinkop UKM',
            'email' => 'dinkop@minselkab.go.id',
            'telepon' => '(0430) 21655',
            'alamat' => 'Jl. Trans Sulawesi, Amurang Timur',
            'pic' => 'Kepala Dinas Koperasi & UKM',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 18,
            'kode_opd' => 'DPMPTSP',
            'nama_opd' => 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu',
            'singkatan' => 'DPMPTSP',
            'email' => 'dpmptsp@minselkab.go.id',
            'telepon' => '(0430) 22340',
            'alamat' => 'Gedung MPP Minahasa Selatan, Pondang',
            'pic' => 'Kepala DPMPTSP',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 19,
            'kode_opd' => 'DUKCAPIL',
            'nama_opd' => 'Dinas Kependudukan dan Pencatatan Sipil',
            'singkatan' => 'Dukcapil',
            'email' => 'dukcapil@minselkab.go.id',
            'telepon' => '(0430) 21015',
            'alamat' => 'Jl. Trans Sulawesi, Kompleks Kantor Bupati, Pondang',
            'pic' => 'Kepala Dinas Dukcapil',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 20,
            'kode_opd' => 'DISPORA',
            'nama_opd' => 'Dinas Pemuda dan Olahraga',
            'singkatan' => 'Dispora',
            'email' => 'dispora@minselkab.go.id',
            'telepon' => '(0430) 21766',
            'alamat' => 'Kompleks Stadion Kawangkoan Bawah, Amurang Barat',
            'pic' => 'Kepala Dinas Pemuda dan Olahraga',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 23,
            'kode_opd' => 'SATPOLPP_DAMKAR',
            'nama_opd' => 'Satuan Polisi Pamong Praja dan Pemadam Kebakaran',
            'singkatan' => 'Satpol PP & Damkar',
            'email' => 'satpolpp@minselkab.go.id',
            'telepon' => '(0430) 21113',
            'alamat' => 'Kompleks Kantor Bupati Minsel, Pondang',
            'pic' => 'Kasat Pol PP & Damkar',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 24,
            'kode_opd' => 'SETDA_PEM',
            'nama_opd' => 'Sekretariat Daerah - Bagian Tata Pemerintahan',
            'singkatan' => 'Setda Tapem',
            'email' => 'tapem@minselkab.go.id',
            'telepon' => '(0430) 21001',
            'alamat' => 'Kantor Bupati Minahasa Selatan Lantai 2, Pondang',
            'pic' => 'Kabag Tata Pemerintahan Setda',
            'is_active' => true,
            'created_at' => '2026-01-01 08:00:00'
        ]
    ];

    /**
     * Master Kategori Spasial Tematik Default (28 Kategori Resmi)
     */
    protected static array $defaultKategori = [
        [
            'id' => 1,
            'kode' => 'wilayah_administrasi',
            'nama_kategori' => 'Wilayah Administrasi',
            'deskripsi' => 'Batas kabupaten, batas kecamatan, desa/kelurahan se-Kabupaten Minahasa Selatan',
            'icon' => 'map',
            'warna_default' => '#2563eb',
            'opd_id' => 24,
            'nama_opd' => 'Sekretariat Daerah - Bagian Tata Pemerintahan',
            'is_active' => true
        ],
        [
            'id' => 2,
            'kode' => 'pemerintahan',
            'nama_kategori' => 'Pemerintahan',
            'deskripsi' => 'Kantor bupati, Setda, kantor OPD, kantor camat, kantor hukum tua dan balai desa',
            'icon' => 'landmark',
            'warna_default' => '#0284c7',
            'opd_id' => 24,
            'nama_opd' => 'Sekretariat Daerah - Bagian Tata Pemerintahan',
            'is_active' => true
        ],
        [
            'id' => 3,
            'kode' => 'kependudukan',
            'nama_kategori' => 'Kependudukan',
            'deskripsi' => 'Kepadatan penduduk agregat, persebaran KK, rasio jenis kelamin per desa',
            'icon' => 'users',
            'warna_default' => '#6366f1',
            'opd_id' => 19,
            'nama_opd' => 'Dinas Kependudukan dan Pencatatan Sipil',
            'is_active' => true
        ],
        [
            'id' => 4,
            'kode' => 'pendidikan',
            'nama_kategori' => 'Pendidikan',
            'deskripsi' => 'Sebaran PAUD, SD/MI, SMP/MTs, SMA/SMK, dan perguruan tinggi',
            'icon' => 'graduation-cap',
            'warna_default' => '#d97706',
            'opd_id' => 5,
            'nama_opd' => 'Dinas Pendidikan dan Kebudayaan',
            'is_active' => true
        ],
        [
            'id' => 5,
            'kode' => 'kesehatan',
            'nama_kategori' => 'Kesehatan',
            'deskripsi' => 'Rumah sakit, Puskesmas pembantu, Puskesmas rawat inap, posyandu, dan apotek',
            'icon' => 'heart-pulse',
            'warna_default' => '#dc2626',
            'opd_id' => 6,
            'nama_opd' => 'Dinas Kesehatan',
            'is_active' => true
        ],
        [
            'id' => 6,
            'kode' => 'infrastruktur_pu',
            'nama_kategori' => 'Pekerjaan Umum dan Infrastruktur',
            'deskripsi' => 'Jaringan jalan kabupaten, jembatan, irigasi, drainase perkotaan, SPAM air minum',
            'icon' => 'hard-hat',
            'warna_default' => '#475569',
            'opd_id' => 3,
            'nama_opd' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
            'is_active' => true
        ],
        [
            'id' => 7,
            'kode' => 'transportasi',
            'nama_kategori' => 'Perhubungan dan Transportasi',
            'deskripsi' => 'Terminal penumpang, pelabuhan penyeberangan Amurang, halte, trayek angkutan',
            'icon' => 'navigation',
            'warna_default' => '#0284c7',
            'opd_id' => 13,
            'nama_opd' => 'Dinas Perhubungan',
            'is_active' => true
        ],
        [
            'id' => 8,
            'kode' => 'pertanian',
            'nama_kategori' => 'Pertanian dan Tanaman Pangan',
            'deskripsi' => 'Lahan sawah baku, sentra komoditas jagung, padi sawah, hortikultura, kelompok tani',
            'icon' => 'wheat',
            'warna_default' => '#84cc16',
            'opd_id' => 7,
            'nama_opd' => 'Dinas Pertanian',
            'is_active' => true
        ],
        [
            'id' => 9,
            'kode' => 'perkebunan',
            'nama_kategori' => 'Perkebunan',
            'deskripsi' => 'Sentra perkebunan kelapa, cengkeh Minahasa Selatan, pala, kopi, vanili',
            'icon' => 'palmtree',
            'warna_default' => '#15803d',
            'opd_id' => 8,
            'nama_opd' => 'Dinas Perkebunan',
            'is_active' => true
        ],
        [
            'id' => 10,
            'kode' => 'perikanan',
            'nama_kategori' => 'Perikanan dan Kelautan',
            'deskripsi' => 'Pangkalan pendaratan ikan, sentra budidaya tambak, zona tangkap nelayan Teluk Amurang',
            'icon' => 'fish',
            'warna_default' => '#06b6d4',
            'opd_id' => 9,
            'nama_opd' => 'Dinas Perikanan',
            'is_active' => true
        ],
        [
            'id' => 11,
            'kode' => 'pariwisata_budaya',
            'nama_kategori' => 'Pariwisata dan Kebudayaan',
            'deskripsi' => 'Destinasi wisata bahari pesisir, wisata alam pegunungan, peninggalan situs budaya',
            'icon' => 'mountain-snow',
            'warna_default' => '#16a34a',
            'opd_id' => 10,
            'nama_opd' => 'Dinas Pariwisata',
            'is_active' => true
        ],
        [
            'id' => 12,
            'kode' => 'lingkungan_hidup',
            'nama_kategori' => 'Lingkungan Hidup dan Kehutanan',
            'deskripsi' => 'Kawasan hutan lindung, cagar alam, ruang terbuka hijau, lokasi TPA/TPS sampah',
            'icon' => 'trees',
            'warna_default' => '#059669',
            'opd_id' => 11,
            'nama_opd' => 'Dinas Lingkungan Hidup',
            'is_active' => true
        ],
        [
            'id' => 13,
            'kode' => 'kebencanaan',
            'nama_kategori' => 'Kebencanaan dan Mitigasi',
            'deskripsi' => 'Kawasan rawan tanah longsor, banjir DAS Ranoyapo, zona bahaya Gunung Soputan, jalur evakuasi',
            'icon' => 'alert-triangle',
            'warna_default' => '#ea580c',
            'opd_id' => 12,
            'nama_opd' => 'Badan Penanggulangan Bencana Daerah',
            'is_active' => true
        ],
        [
            'id' => 14,
            'kode' => 'perumahan_permukiman',
            'nama_kategori' => 'Perumahan dan Permukiman',
            'deskripsi' => 'Kawasan perumahan formal, sebaran RTLH, kawasan kumuh perkotaan, sanitasi komunal',
            'icon' => 'home',
            'warna_default' => '#78716c',
            'opd_id' => 4,
            'nama_opd' => 'Dinas Perumahan dan Kawasan Permukiman',
            'is_active' => true
        ],
        [
            'id' => 15,
            'kode' => 'perdagangan_industri',
            'nama_kategori' => 'Perdagangan dan Perindustrian',
            'deskripsi' => 'Pasar tradisional, pasar desa, kawasan industri, sentra IKM pengolahan kopra/cap tikus',
            'icon' => 'store',
            'warna_default' => '#b45309',
            'opd_id' => 15,
            'nama_opd' => 'Dinas Perdagangan',
            'is_active' => true
        ],
        [
            'id' => 16,
            'kode' => 'koperasi_umkm',
            'nama_kategori' => 'Koperasi dan UMKM',
            'deskripsi' => 'Sentra UMKM binaan, koperasi aktif, galeri oleh-oleh khas Minahasa Selatan',
            'icon' => 'briefcase',
            'warna_default' => '#d97706',
            'opd_id' => 16,
            'nama_opd' => 'Dinas Koperasi dan Usaha Kecil Menengah',
            'is_active' => true
        ],
        [
            'id' => 17,
            'kode' => 'sosial_kesejahteraan',
            'nama_kategori' => 'Sosial dan Kesejahteraan',
            'deskripsi' => 'Panti sosial, lumbung sosial bencana, keluarga penerima PKH agregat per kecamatan',
            'icon' => 'heart-handshake',
            'warna_default' => '#db2777',
            'opd_id' => 14,
            'nama_opd' => 'Dinas Sosial',
            'is_active' => true
        ],
        [
            'id' => 18,
            'kode' => 'pertanahan_tataruang',
            'nama_kategori' => 'Pertanahan dan Penataan Ruang',
            'deskripsi' => 'RTRW Minsel, pola ruang, struktur ruang, kawasan budidaya dan lindung resmi',
            'icon' => 'map-pin',
            'warna_default' => '#4338ca',
            'opd_id' => 3,
            'nama_opd' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
            'is_active' => true
        ],
        [
            'id' => 19,
            'kode' => 'energi_sdm',
            'nama_kategori' => 'Energi dan Sumber Daya Mineral',
            'deskripsi' => 'Gardu induk PLN, transmisi SUTT, PLTMH, potensi panas bumi dan energi terbarukan',
            'icon' => 'zap',
            'warna_default' => '#ca8a04',
            'opd_id' => 1,
            'nama_opd' => 'Badan Perencanaan, Penelitian dan Pengembangan Daerah',
            'is_active' => true
        ],
        [
            'id' => 20,
            'kode' => 'kominfo_tik',
            'nama_kategori' => 'Komunikasi dan Informatika',
            'deskripsi' => 'Menara telekomunikasi (BTS), jaringan serat optik intra-pemerintah, hotspot internet desa',
            'icon' => 'wifi',
            'warna_default' => '#0284c7',
            'opd_id' => 2,
            'nama_opd' => 'Dinas Komunikasi dan Informatika',
            'is_active' => true
        ],
        [
            'id' => 21,
            'kode' => 'keamanan_ketertiban',
            'nama_kategori' => 'Keamanan dan Ketertiban',
            'deskripsi' => 'Pos Satpol PP, posko pemadam kebakaran, pos pengamanan terpadu',
            'icon' => 'shield-alert',
            'warna_default' => '#991b1b',
            'opd_id' => 23,
            'nama_opd' => 'Satuan Polisi Pamong Praja dan Pemadam Kebakaran',
            'is_active' => true
        ],
        [
            'id' => 22,
            'kode' => 'geografis_dasar',
            'nama_kategori' => 'Data Geografis Dasar',
            'deskripsi' => 'Elevasi digital, kontur lereng, sungai utama DAS, garis pantai, curah hujan',
            'icon' => 'mountain',
            'warna_default' => '#334155',
            'opd_id' => 1,
            'nama_opd' => 'Badan Perencanaan, Penelitian dan Pengembangan Daerah',
            'is_active' => true
        ],
        [
            'id' => 23,
            'kode' => 'investasi_potensi',
            'nama_kategori' => 'Investasi dan Potensi Daerah',
            'deskripsi' => 'Peta peluang investasi terverifikasi, kawasan agrowisata prospektif, pelabuhan industri',
            'icon' => 'trending-up',
            'warna_default' => '#059669',
            'opd_id' => 18,
            'nama_opd' => 'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu',
            'is_active' => true
        ]
    ];

    /**
     * Storage Helper untuk OPD
     */
    protected static function getStorageOpds(): array
    {
        $file = WRITEPATH . 'ref_opd.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data) && !empty($data)) {
                return $data;
            }
        }

        // Coba sinkronisasi dari basis data MySQL jika tabel tersedia
        try {
            $db = \Config\Database::connect();
            $rows = $db->table('ref_opd')->get()->getResultArray();
            if (!empty($rows)) {
                $mapped = [];
                foreach ($rows as $r) {
                    $mapped[] = [
                        'id' => (int)$r['id'],
                        'kode_opd' => $r['kode_opd'],
                        'nama_opd' => $r['nama_opd'],
                        'singkatan' => $r['singkatan'] ?? $r['kode_opd'],
                        'email' => $r['email'] ?? '',
                        'telepon' => $r['telepon'] ?? '',
                        'alamat' => $r['alamat'] ?? 'Kabupaten Minahasa Selatan',
                        'pic' => $r['pic'] ?? ('Kepala ' . $r['nama_opd']),
                        'is_active' => (bool)($r['is_active'] ?? 1),
                        'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s')
                    ];
                }
                file_put_contents($file, json_encode($mapped, JSON_PRETTY_PRINT));
                return $mapped;
            }
        } catch (\Throwable) {}

        file_put_contents($file, json_encode(self::$defaultOpds, JSON_PRETTY_PRINT));
        return self::$defaultOpds;
    }

    protected static function saveStorageOpds(array $opds): void
    {
        $file = WRITEPATH . 'ref_opd.json';
        file_put_contents($file, json_encode($opds, JSON_PRETTY_PRINT));
    }

    /**
     * Storage Helper untuk Kategori Spasial
     */
    protected static function getStorageKategori(): array
    {
        $file = WRITEPATH . 'kategori_layer.json';

        // 1. Prioritaskan query real database MySQL kategori_layer jika tersedia
        try {
            $db = \Config\Database::connect();
            $builder = $db->table('kategori_layer k')
                ->select('k.*, o.nama_opd, o.kode_opd')
                ->join('ref_opd o', 'k.opd_id = o.id', 'left')
                ->orderBy('k.id', 'ASC');
            $rows = $builder->get()->getResultArray();
            if (!empty($rows)) {
                $mapped = [];
                foreach ($rows as $r) {
                    $mapped[] = [
                        'id' => (int)$r['id'],
                        'kode' => $r['kode'],
                        'nama_kategori' => $r['nama_kategori'],
                        'deskripsi' => $r['deskripsi'] ?? 'Tema spasial tematik resmi Kabupaten Minahasa Selatan.',
                        'icon' => $r['icon'] ?? 'layers',
                        'warna_default' => $r['warna_default'] ?? '#2563eb',
                        'opd_id' => (int)($r['opd_id'] ?? 2),
                        'nama_opd' => $r['nama_opd'] ?? 'Pemerintah Kabupaten Minahasa Selatan',
                        'is_active' => (bool)($r['is_active'] ?? 1),
                        'created_at' => $r['created_at'] ?? date('Y-m-d H:i:s')
                    ];
                }
                file_put_contents($file, json_encode($mapped, JSON_PRETTY_PRINT));
                return $mapped;
            }
        } catch (\Throwable) {}

        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data) && !empty($data)) {
                return $data;
            }
        }

        file_put_contents($file, json_encode(self::$defaultKategori, JSON_PRETTY_PRINT));
        return self::$defaultKategori;
    }

    protected static function saveStorageKategori(array $kategori): void
    {
        $file = WRITEPATH . 'kategori_layer.json';
        file_put_contents($file, json_encode($kategori, JSON_PRETTY_PRINT));
    }

    // =========================================================================
    // CRUD OPD (ORGANISASI PERANGKAT DAERAH)
    // =========================================================================

    /**
     * GET /spasials/admin/opd
     * Mengambil daftar seluruh OPD beserta agregasi dataset dan kategori
     */
    public function getOpds(): ResponseInterface
    {
        $opds = self::getStorageOpds();
        $datasets = WorkflowController::getStorageDatasets();
        $kategoris = self::getStorageKategori();

        foreach ($opds as &$opd) {
            $opd['total_datasets'] = count(array_filter($datasets, fn($d) => ($d['kode_opd'] ?? '') === $opd['kode_opd']));
            $opd['total_kategori'] = count(array_filter($kategoris, fn($k) => (int)($k['opd_id'] ?? 0) === (int)$opd['id']));
        }

        return $this->respondSuccess($opds, 'Daftar OPD Pemkab Minahasa Selatan berhasil dimuat.');
    }

    /**
     * POST /spasials/admin/opd/create
     * Menambahkan OPD baru ke dalam sistem
     */
    public function createOpd(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $kodeOpd = strtoupper(trim($input['kode_opd'] ?? ''));
        $namaOpd = trim($input['nama_opd'] ?? '');
        $singkatan = trim($input['singkatan'] ?? $kodeOpd);
        $email = trim($input['email'] ?? '');
        $telepon = trim($input['telepon'] ?? '');
        $alamat = trim($input['alamat'] ?? '');
        $pic = trim($input['pic'] ?? ('Kepala ' . $namaOpd));

        if (empty($kodeOpd) || empty($namaOpd)) {
            return $this->respondError('Kode OPD dan Nama OPD wajib diisi.', 400);
        }

        $opds = self::getStorageOpds();
        foreach ($opds as $o) {
            if ($o['kode_opd'] === $kodeOpd) {
                return $this->respondError("Kode OPD '{$kodeOpd}' sudah digunakan.", 400);
            }
        }

        $maxId = 0;
        foreach ($opds as $o) {
            if ($o['id'] > $maxId) {
                $maxId = $o['id'];
            }
        }
        $newId = $maxId + 1;

        $newOpd = [
            'id' => $newId,
            'kode_opd' => $kodeOpd,
            'nama_opd' => $namaOpd,
            'singkatan' => $singkatan ?: $kodeOpd,
            'email' => $email ?: (strtolower($kodeOpd) . '@minselkab.go.id'),
            'telepon' => $telepon ?: '(0430) 21000',
            'alamat' => $alamat ?: 'Kabupaten Minahasa Selatan',
            'pic' => $pic,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $opds[] = $newOpd;
        self::saveStorageOpds($opds);

        AuditLogController::record([
            'kategori'      => 'OPD',
            'aksi'          => 'CREATE_OPD',
            'aktor'         => 'Superadmin Diskominfo',
            'role'          => 'superadmin',
            'opd'           => $namaOpd,
            'objek'         => "{$namaOpd} ({$kodeOpd})",
            'keterangan'    => 'Penambahan data master Organisasi Perangkat Daerah baru',
            'versi_terkait' => '-'
        ]);

        return $this->respondSuccess($newOpd, "OPD '{$namaOpd}' berhasil ditambahkan ke sistem.");
    }

    /**
     * POST /spasials/admin/opd/update
     * Memperbarui informasi OPD
     */
    public function updateOpd(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $id = (int)($input['id'] ?? 0);
        $namaOpd = trim($input['nama_opd'] ?? '');
        $singkatan = trim($input['singkatan'] ?? '');
        $email = trim($input['email'] ?? '');
        $telepon = trim($input['telepon'] ?? '');
        $alamat = trim($input['alamat'] ?? '');
        $pic = trim($input['pic'] ?? '');
        $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : null;

        $opds = self::getStorageOpds();
        foreach ($opds as &$o) {
            if ($o['id'] === $id) {
                if (!empty($namaOpd)) $o['nama_opd'] = $namaOpd;
                if (!empty($singkatan)) $o['singkatan'] = $singkatan;
                if (!empty($email)) $o['email'] = $email;
                if (!empty($telepon)) $o['telepon'] = $telepon;
                if (!empty($alamat)) $o['alamat'] = $alamat;
                if (!empty($pic)) $o['pic'] = $pic;
                if ($isActive !== null) $o['is_active'] = $isActive;

                self::saveStorageOpds($opds);

                AuditLogController::record([
                    'kategori'      => 'OPD',
                    'aksi'          => 'UPDATE_OPD',
                    'aktor'         => 'Superadmin Diskominfo',
                    'role'          => 'superadmin',
                    'opd'           => $o['nama_opd'],
                    'objek'         => "{$o['nama_opd']} ({$o['kode_opd']})",
                    'keterangan'    => 'Pembaruan data kontak/informasi master OPD',
                    'versi_terkait' => '-'
                ]);

                return $this->respondSuccess($o, "Data OPD '{$o['nama_opd']}' berhasil diperbarui.");
            }
        }

        return $this->respondError('Data OPD tidak ditemukan.', 404);
    }

    /**
     * POST /spasials/admin/opd/delete
     * Menghapus atau menonaktifkan OPD
     */
    public function deleteOpd(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $id = (int)($input['id'] ?? 0);
        $opds = self::getStorageOpds();
        $targetKey = null;

        foreach ($opds as $key => $o) {
            if ($o['id'] === $id) {
                // Lindungi OPD Inti Sistem
                if (in_array($o['kode_opd'], ['DISKOMINFO', 'BAPELITBANGDA', 'DPUPR'])) {
                    return $this->respondError("OPD Inti '{$o['nama_opd']}' dilindungi dan tidak dapat dihapus.", 403);
                }
                $targetKey = $key;
                break;
            }
        }

        if ($targetKey === null) {
            return $this->respondError('OPD tidak ditemukan.', 404);
        }

        $deletedOpd = $opds[$targetKey];
        $deletedName = $deletedOpd['nama_opd'];
        unset($opds[$targetKey]);
        $opds = array_values($opds);
        self::saveStorageOpds($opds);

        AuditLogController::record([
            'kategori'      => 'OPD',
            'aksi'          => 'DELETE_OPD',
            'aktor'         => 'Superadmin Diskominfo',
            'role'          => 'superadmin',
            'opd'           => $deletedName,
            'objek'         => "{$deletedName} ({$deletedOpd['kode_opd']})",
            'keterangan'    => 'Penghapusan OPD dari sistem data terpadu',
            'versi_terkait' => '-'
        ]);

        return $this->respondSuccess(['id' => $id], "OPD '{$deletedName}' berhasil dihapus dari sistem.");
    }

    // =========================================================================
    // CRUD KATEGORI SPASIAL TEMATIK
    // =========================================================================

    /**
     * GET /spasials/admin/kategori
     * Mengambil daftar seluruh kategori layer spasial (aktif & nonaktif)
     */
    public function getKategori(): ResponseInterface
    {
        $kategoris = self::getStorageKategori();
        $datasets = WorkflowController::getStorageDatasets();

        foreach ($kategoris as &$k) {
            $k['total_datasets'] = count(array_filter($datasets, fn($d) => strtolower($d['kategori'] ?? '') === strtolower($k['kode'])));
        }

        return $this->respondSuccess($kategoris, 'Daftar kategori spasial tematik berhasil dimuat.');
    }

    /**
     * POST /spasials/admin/kategori/create
     * Menambahkan kategori spasial baru
     */
    public function createKategori(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $kode = strtolower(trim($input['kode'] ?? ''));
        $namaKategori = trim($input['nama_kategori'] ?? '');
        $deskripsi = trim($input['deskripsi'] ?? '');
        $icon = trim($input['icon'] ?? 'layers');
        $warna = trim($input['warna_default'] ?? '#2563eb');
        $opdId = (int)($input['opd_id'] ?? 2);
        $namaOpd = trim($input['nama_opd'] ?? '');

        if (empty($namaKategori)) {
            return $this->respondError('Nama kategori spasial wajib diisi.', 400);
        }

        if (empty($kode)) {
            $kode = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $namaKategori));
        }

        $kategoris = self::getStorageKategori();
        foreach ($kategoris as $k) {
            if ($k['kode'] === $kode) {
                return $this->respondError("Kode kategori '{$kode}' sudah ada dalam sistem.", 400);
            }
        }

        // Cari nama OPD jika belum disertakan
        if (empty($namaOpd)) {
            $opds = self::getStorageOpds();
            foreach ($opds as $o) {
                if ($o['id'] === $opdId) {
                    $namaOpd = $o['nama_opd'];
                    break;
                }
            }
        }

        $maxId = 0;
        foreach ($kategoris as $k) {
            if ($k['id'] > $maxId) {
                $maxId = $k['id'];
            }
        }
        $newId = $maxId + 1;

        $newKategori = [
            'id' => $newId,
            'kode' => $kode,
            'nama_kategori' => $namaKategori,
            'deskripsi' => $deskripsi,
            'icon' => $icon ?: 'layers',
            'warna_default' => $warna ?: '#2563eb',
            'opd_id' => $opdId,
            'nama_opd' => $namaOpd ?: 'Pemerintah Kabupaten Minahasa Selatan',
            'is_active' => true
        ];

        $kategoris[] = $newKategori;
        self::saveStorageKategori($kategoris);

        AuditLogController::record([
            'kategori'      => 'KATEGORI',
            'aksi'          => 'CREATE_KATEGORI',
            'aktor'         => 'Superadmin Diskominfo',
            'role'          => 'superadmin',
            'opd'           => $namaOpd ?: 'Pemkab Minahasa Selatan',
            'objek'         => "{$namaKategori} ({$kode})",
            'keterangan'    => 'Penambahan kategori spasial tematik baru',
            'versi_terkait' => '-'
        ]);

        return $this->respondSuccess($newKategori, "Kategori spasial '{$namaKategori}' berhasil ditambahkan.");
    }

    /**
     * POST /spasials/admin/kategori/update
     * Memperbarui kategori spasial tematik
     */
    public function updateKategori(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $id = (int)($input['id'] ?? 0);
        $namaKategori = trim($input['nama_kategori'] ?? '');
        $deskripsi = trim($input['deskripsi'] ?? '');
        $icon = trim($input['icon'] ?? '');
        $warna = trim($input['warna_default'] ?? '');
        $opdId = isset($input['opd_id']) ? (int)$input['opd_id'] : null;
        $namaOpd = trim($input['nama_opd'] ?? '');
        $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : null;

        $kategoris = self::getStorageKategori();
        foreach ($kategoris as &$k) {
            if ($k['id'] === $id) {
                if (!empty($namaKategori)) $k['nama_kategori'] = $namaKategori;
                if ($deskripsi !== '') $k['deskripsi'] = $deskripsi;
                if (!empty($icon)) $k['icon'] = $icon;
                if (!empty($warna)) $k['warna_default'] = $warna;
                if ($opdId !== null) $k['opd_id'] = $opdId;
                if (!empty($namaOpd)) $k['nama_opd'] = $namaOpd;
                if ($isActive !== null) $k['is_active'] = $isActive;

                self::saveStorageKategori($kategoris);

                AuditLogController::record([
                    'kategori'      => 'KATEGORI',
                    'aksi'          => 'UPDATE_KATEGORI',
                    'aktor'         => 'Superadmin Diskominfo',
                    'role'          => 'superadmin',
                    'opd'           => $k['nama_opd'] ?? 'Pemkab Minahasa Selatan',
                    'objek'         => "{$k['nama_kategori']} ({$k['kode']})",
                    'keterangan'    => 'Pembaruan informasi dan metadata kategori spasial',
                    'versi_terkait' => '-'
                ]);

                return $this->respondSuccess($k, "Kategori spasial '{$k['nama_kategori']}' berhasil diperbarui.");
            }
        }

        return $this->respondError('Kategori spasial tidak ditemukan.', 404);
    }

    /**
     * POST /spasials/admin/kategori/delete
     * Menghapus kategori spasial tematik
     */
    public function deleteKategori(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $id = (int)($input['id'] ?? 0);
        $kategoris = self::getStorageKategori();
        $targetKey = null;

        foreach ($kategoris as $key => $k) {
            if ($k['id'] === $id) {
                // Lindungi Kategori Geografis Dasar dan Wilayah Administrasi
                if (in_array($k['kode'], ['wilayah_administrasi', 'pemerintahan', 'geografis_dasar'])) {
                    return $this->respondError("Kategori Spasial '{$k['nama_kategori']}' merupakan fondasi sistem dan tidak dapat dihapus.", 403);
                }
                $targetKey = $key;
                break;
            }
        }

        if ($targetKey === null) {
            return $this->respondError('Kategori spasial tidak ditemukan.', 404);
        }

        $deletedKat = $kategoris[$targetKey];
        $deletedName = $deletedKat['nama_kategori'];
        unset($kategoris[$targetKey]);
        $kategoris = array_values($kategoris);
        self::saveStorageKategori($kategoris);

        AuditLogController::record([
            'kategori'      => 'KATEGORI',
            'aksi'          => 'DELETE_KATEGORI',
            'aktor'         => 'Superadmin Diskominfo',
            'role'          => 'superadmin',
            'opd'           => $deletedKat['nama_opd'] ?? 'Pemkab Minahasa Selatan',
            'objek'         => "{$deletedName} ({$deletedKat['kode']})",
            'keterangan'    => 'Penghapusan kategori spasial tematik',
            'versi_terkait' => '-'
        ]);

        return $this->respondSuccess(['id' => $id], "Kategori spasial '{$deletedName}' berhasil dihapus.");
    }
}

