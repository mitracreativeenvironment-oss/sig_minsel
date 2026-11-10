<?php

namespace App\Controllers\Api;

use App\Models\KabupatenModel;
use App\Models\KecamatanModel;
use App\Models\DesaModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller API Hierarki Administrasi Wilayah
 * Level 1: Kabupaten (Minahasa Selatan)
 * Level 2: Kecamatan
 * Level 3: Desa / Kelurahan
 * 
 * Setiap data desa terhubung secara eksplisit ke Kecamatan dan Kabupaten induk.
 */
class WilayahController extends BaseApiController
{
    protected KabupatenModel $kabupatenModel;
    protected KecamatanModel $kecamatanModel;
    protected DesaModel $desaModel;

    public function __construct()
    {
        $this->kabupatenModel = new KabupatenModel();
        $this->kecamatanModel = new KecamatanModel();
        $this->desaModel      = new DesaModel();
    }

    /**
     * GET /api/v1/wilayah/kabupaten
     * Data Wilayah Induk Kabupaten Minahasa Selatan
     */
    public function kabupaten(): ResponseInterface
    {
        try {
            $kab = $this->kabupatenModel->getKabupatenMinsel();
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memuat data kabupaten: ' . $e->getMessage(), 500);
        }

        if (!$kab) {
            return $this->respondFailError('Data Kabupaten Minahasa Selatan belum tersedia.', 404);
        }

        $geom = json_decode($kab['geometry_geojson'] ?? '{}', true);

        return $this->respondSuccess([
            'type' => 'Feature',
            'id'   => (int)$kab['id'],
            'geometry' => $geom,
            'properties' => [
                'id'               => (int)$kab['id'],
                'kode'             => $kab['kode_kabupaten'],
                'nama'             => $kab['nama_kabupaten'],
                'ibukota'          => $kab['ibukota'],
                'luas_km2'         => (float)$kab['luas_wilayah_km2'],
                'deskripsi'        => $kab['deskripsi'],
                'center'           => [
                    'lng' => (float)$kab['center_lng'],
                    'lat' => (float)$kab['center_lat']
                ],
                'jumlah_kecamatan' => 17,
            ]
        ], 'Data Kabupaten Minahasa Selatan berhasil dimuat.');
    }

    /**
     * GET /api/v1/wilayah/kecamatan
     * Mengembalikan batas seluruh kecamatan di Minsel dalam format GeoJSON FeatureCollection
     */
    public function kecamatan(): ResponseInterface
    {
        try {
            $rows = $this->kecamatanModel->getWithGeoJson();
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memuat data spasial kecamatan: ' . $e->getMessage(), 500);
        }

        $features = [];
        foreach ($rows as $row) {
            $geom = json_decode($row['geometry_geojson'] ?? '{}', true);

            $features[] = [
                'type' => 'Feature',
                'id'   => (int)$row['id'],
                'geometry' => $geom,
                'properties' => [
                    'id'               => (int)$row['id'],
                    'kabupaten_id'     => (int)$row['kabupaten_id'],
                    'kabupaten'        => $row['nama_kabupaten'],
                    'kode'             => $row['kode_kecamatan'],
                    'nama'             => $row['nama_kecamatan'],
                    'ibukota'          => $row['ibukota_kecamatan'],
                    'luas_km2'         => (float)$row['luas_wilayah_km2'],
                    'center'           => [
                        'lng' => (float)$row['center_lng'],
                        'lat' => (float)$row['center_lat']
                    ],
                ]
            ];
        }

        return $this->respondGeoJson($features, [
            'name'        => 'Batas Administrasi Kecamatan Minahasa Selatan',
            'crs'         => ['type' => 'name', 'properties' => ['name' => 'urn:ogc:def:crs:OGC:1.3:CRS84']],
            'total'       => count($features),
        ]);
    }

    /**
     * GET /api/v1/wilayah/desa
     * Mengembalikan daftar desa/kelurahan dengan relasi ke kecamatan dan kabupaten
     * Parameter filter: ?kecamatan_id=... & ?kabupaten_id=...
     */
    public function desa(): ResponseInterface
    {
        $kecamatanId = $this->request->getGet('kecamatan_id');
        $kabupatenId = $this->request->getGet('kabupaten_id') ?: 1;

        $kecId = $kecamatanId ? (int)$kecamatanId : null;
        $kabId = $kabupatenId ? (int)$kabupatenId : null;

        try {
            $rows = $this->desaModel->getFiltered($kecId, $kabId);
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memuat data spasial desa: ' . $e->getMessage(), 500);
        }

        $features = [];
        foreach ($rows as $row) {
            $geom = json_decode($row['geometry_geojson'] ?? '{}', true);

            $features[] = [
                'type' => 'Feature',
                'id'   => (int)$row['id'],
                'geometry' => $geom,
                'properties' => [
                    'id'           => (int)$row['id'],
                    'kabupaten_id' => (int)$row['kabupaten_id'],
                    'kabupaten'    => $row['nama_kabupaten'],
                    'kecamatan_id' => (int)$row['kecamatan_id'],
                    'kecamatan'    => $row['nama_kecamatan'],
                    'kode'         => $row['kode_desa'],
                    'nama'         => $row['nama_desa'],
                    'tipe'         => $row['tipe'],
                    'luas_km2'     => (float)$row['luas_wilayah_km2'],
                    'center'       => [
                        'lng' => (float)$row['center_lng'],
                        'lat' => (float)$row['center_lat']
                    ],
                ]
            ];
        }

        return $this->respondGeoJson($features, [
            'name'         => 'Batas Administrasi Desa / Kelurahan Minahasa Selatan',
            'kabupaten_id' => $kabId,
            'kecamatan_id' => $kecId ?: 'all',
            'total'        => count($features),
        ]);
    }

    /**
     * GET /api/v1/wilayah/hierarchy
     * Menghasilkan struktur pohon lengkap (Kabupaten -> Kecamatan -> Desa)
     * untuk efisiensi cascading dropdown di dashboard frontend
     */
    public function hierarchy(): ResponseInterface
    {
        $db = \Config\Database::connect();

        try {
            // Ambil kabupaten induk
            $kab = $db->table('ref_kabupaten')->where('id', 1)->get()->getRowArray();
            // Ambil kecamatan
            $kecamatans = $db->table('ref_kecamatan')
                ->select('id, kabupaten_id, kode_kecamatan, nama_kecamatan, ibukota_kecamatan')
                ->where('kabupaten_id', 1)
                ->orderBy('nama_kecamatan', 'ASC')
                ->get()
                ->getResultArray();
            // Ambil desa
            $desas = $db->table('ref_desa')
                ->select('id, kabupaten_id, kecamatan_id, kode_desa, nama_desa, tipe')
                ->orderBy('nama_desa', 'ASC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memuat struktur hierarki: ' . $e->getMessage(), 500);
        }

        // Map desa ke kecamatan induk
        $desaByKec = [];
        foreach ($desas as $d) {
            $desaByKec[$d['kecamatan_id']][] = [
                'id'           => (int)$d['id'],
                'kabupaten_id' => (int)$d['kabupaten_id'],
                'kecamatan_id' => (int)$d['kecamatan_id'],
                'kode'         => $d['kode_desa'],
                'nama'         => $d['nama_desa'],
                'tipe'         => $d['tipe'],
            ];
        }

        $kecamatanTree = [];
        foreach ($kecamatans as $k) {
            $kecId = (int)$k['id'];
            $kecamatanTree[] = [
                'id'           => $kecId,
                'kabupaten_id' => (int)$k['kabupaten_id'],
                'kode'         => $k['kode_kecamatan'],
                'nama'         => $k['nama_kecamatan'],
                'ibukota'      => $k['ibukota_kecamatan'],
                'jumlah_desa'  => count($desaByKec[$kecId] ?? []),
                'desa'         => $desaByKec[$kecId] ?? [],
            ];
        }

        return $this->respondSuccess([
            'kabupaten' => [
                'id'               => (int)($kab['id'] ?? 1),
                'kode'             => $kab['kode_kabupaten'] ?? '71.05',
                'nama'             => $kab['nama_kabupaten'] ?? 'Minahasa Selatan',
                'ibukota'          => $kab['ibukota'] ?? 'Amurang',
                'total_kecamatan'  => count($kecamatanTree),
                'total_desa'       => count($desas),
                'kecamatan'        => $kecamatanTree,
            ]
        ], 'Struktur hierarki administrasi berhasil dimuat.');
    }

    /**
     * GET /api/v1/wilayah/rekap
     * Agregasi statistik spasial berjenjang:
     * - Tingkat Kabupaten
     * - Tingkat Kecamatan
     * - Tingkat Desa
     */
    public function rekap(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $kecamatanId = $this->request->getGet('kecamatan_id');

        try {
            // Hitung agregasi objek spasial per kategori
            $builder = $db->table('objek_spasial o')
                ->select('k.nama_kategori, k.kode AS kategori_kode, COUNT(o.id) AS jumlah')
                ->join('kategori_layer k', 'k.id = o.kategori_id')
                ->where('o.is_active', 1);

            if ($kecamatanId) {
                $builder->where('o.kecamatan_id', (int)$kecamatanId);
            }

            $rekapKategori = $builder->groupBy('k.id')->get()->getResultArray();

            // Hitung total wilayah
            $totalKec = $db->table('ref_kecamatan')->countAllResults();
            $totalDesa = $db->table('ref_desa')->countAllResults();
            $totalObjek = $db->table('objek_spasial')->where('is_active', 1)->countAllResults();

        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memuat rekapitulasi wilayah: ' . $e->getMessage(), 500);
        }

        return $this->respondSuccess([
            'filter_aktif' => [
                'tingkat'      => $kecamatanId ? 'Kecamatan' : 'Kabupaten (Keseluruhan)',
                'kecamatan_id' => $kecamatanId ? (int)$kecamatanId : null,
            ],
            'ringkasan' => [
                'total_kecamatan' => $totalKec,
                'total_desa'      => $totalDesa,
                'total_objek'     => $totalObjek,
            ],
            'per_kategori' => $rekapKategori,
        ], 'Rekapitulasi data spasial berhasil dimuat.');
    }

    // =========================================================================
    // MODUL CRUD WILAYAH: KECAMATAN (SUPERADMIN / ADMIN)
    // =========================================================================

    /**
     * GET /spasials/admin/wilayah/kecamatan
     * Daftar seluruh kecamatan untuk manajemen administratif
     */
    public function getAdminKecamatan(): ResponseInterface
    {
        $db = \Config\Database::connect();
        try {
            $sql = "SELECT k.id, k.kabupaten_id, k.kode_kecamatan, k.nama_kecamatan, k.ibukota_kecamatan, k.luas_wilayah_km2,
                           ROUND(ST_X(ST_Centroid(ST_SRID(k.geom, 0))), 6) AS center_lng,
                           ROUND(ST_Y(ST_Centroid(ST_SRID(k.geom, 0))), 6) AS center_lat,
                           (SELECT COUNT(d.id) FROM ref_desa d WHERE d.kecamatan_id = k.id) AS total_desa
                    FROM ref_kecamatan k
                    ORDER BY k.nama_kecamatan ASC";
            $rows = $db->query($sql)->getResultArray();
            return $this->respondSuccess($rows, 'Daftar kecamatan berhasil dimuat.');
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memuat daftar kecamatan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /spasials/admin/wilayah/kecamatan/create
     * Menambahkan wilayah kecamatan baru
     */
    public function createKecamatan(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $nama = trim($input['nama_kecamatan'] ?? '');
        $kode = trim($input['kode_kecamatan'] ?? '');
        $ibukota = trim($input['ibukota_kecamatan'] ?? '');
        $luas = (float)($input['luas_wilayah_km2'] ?? 0);
        $lng = (float)($input['center_lng'] ?? 124.57);
        $lat = (float)($input['center_lat'] ?? 1.18);

        if (empty($nama) || empty($kode)) {
            return $this->respondFailError('Nama Kecamatan dan Kode Wilayah wajib diisi.', 400);
        }

        $db = \Config\Database::connect();
        try {
            // Cek keunikan kode
            $exist = $db->table('ref_kecamatan')->where('kode_kecamatan', $kode)->countAllResults();
            if ($exist > 0) {
                return $this->respondFailError("Kode kecamatan '{$kode}' sudah terdaftar.", 400);
            }

            $d = 0.02; // Kotak poligon batas aproksimasi (~2km)
            $wkt = sprintf(
                "MULTIPOLYGON(((%F %F, %F %F, %F %F, %F %F, %F %F)))",
                $lng - $d, $lat - $d,
                $lng + $d, $lat - $d,
                $lng + $d, $lat + $d,
                $lng - $d, $lat + $d,
                $lng - $d, $lat - $d
            );

            $sql = "INSERT INTO ref_kecamatan (kabupaten_id, kode_kecamatan, nama_kecamatan, ibukota_kecamatan, luas_wilayah_km2, geom, created_at, updated_at)
                    VALUES (1, ?, ?, ?, ?, ST_GeomFromText(?, 4326, 'axis-order=long-lat'), NOW(), NOW())";

            $db->query($sql, [$kode, $nama, $ibukota ?: $nama, $luas, $wkt]);
            $newId = $db->insertID();

            AuditLogController::record([
                'kategori'      => 'WILAYAH',
                'aksi'          => 'CREATE_KECAMATAN',
                'aktor'         => 'Superadmin Diskominfo',
                'role'          => 'superadmin',
                'opd'           => 'Bagian Tata Pemerintahan Setda',
                'objek'         => "Kecamatan {$nama} ({$kode})",
                'keterangan'    => "Penambahan wilayah kecamatan baru, ibukota: {$ibukota}, luas: {$luas} km²",
                'versi_terkait' => '-'
            ]);

            return $this->respondSuccess([
                'id' => $newId,
                'kode_kecamatan' => $kode,
                'nama_kecamatan' => $nama,
                'ibukota_kecamatan' => $ibukota,
                'luas_wilayah_km2' => $luas,
                'center_lng' => $lng,
                'center_lat' => $lat
            ], "Kecamatan '{$nama}' berhasil ditambahkan ke basis data spasial.");
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal menambahkan kecamatan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /spasials/admin/wilayah/kecamatan/update
     * Memperbarui informasi wilayah kecamatan
     */
    public function updateKecamatan(): ResponseInterface
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
        $nama = trim($input['nama_kecamatan'] ?? '');
        $kode = trim($input['kode_kecamatan'] ?? '');
        $ibukota = trim($input['ibukota_kecamatan'] ?? '');
        $luas = (float)($input['luas_wilayah_km2'] ?? 0);
        $lng = isset($input['center_lng']) ? (float)$input['center_lng'] : null;
        $lat = isset($input['center_lat']) ? (float)$input['center_lat'] : null;

        if ($id <= 0) {
            return $this->respondFailError('ID kecamatan tidak valid.', 400);
        }

        $db = \Config\Database::connect();
        try {
            $current = $db->table('ref_kecamatan')->where('id', $id)->get()->getRowArray();
            if (!$current) {
                return $this->respondFailError('Data kecamatan tidak ditemukan.', 404);
            }

            if ($lng !== null && $lat !== null) {
                $d = 0.02;
                $wkt = sprintf(
                    "MULTIPOLYGON(((%F %F, %F %F, %F %F, %F %F, %F %F)))",
                    $lng - $d, $lat - $d,
                    $lng + $d, $lat - $d,
                    $lng + $d, $lat + $d,
                    $lng - $d, $lat + $d,
                    $lng - $d, $lat - $d
                );
                $sql = "UPDATE ref_kecamatan 
                        SET nama_kecamatan = ?, kode_kecamatan = ?, ibukota_kecamatan = ?, luas_wilayah_km2 = ?, 
                            geom = ST_GeomFromText(?, 4326, 'axis-order=long-lat'), updated_at = NOW()
                        WHERE id = ?";
                $db->query($sql, [$nama ?: $current['nama_kecamatan'], $kode ?: $current['kode_kecamatan'], $ibukota, $luas, $wkt, $id]);
            } else {
                $sql = "UPDATE ref_kecamatan 
                        SET nama_kecamatan = ?, kode_kecamatan = ?, ibukota_kecamatan = ?, luas_wilayah_km2 = ?, updated_at = NOW()
                        WHERE id = ?";
                $db->query($sql, [$nama ?: $current['nama_kecamatan'], $kode ?: $current['kode_kecamatan'], $ibukota, $luas, $id]);
            }

            AuditLogController::record([
                'kategori'      => 'WILAYAH',
                'aksi'          => 'UPDATE_KECAMATAN',
                'aktor'         => 'Superadmin Diskominfo',
                'role'          => 'superadmin',
                'opd'           => 'Bagian Tata Pemerintahan Setda',
                'objek'         => "Kecamatan {$nama} ({$kode})",
                'keterangan'    => "Pembaruan atribut wilayah kecamatan (luas: {$luas} km², ibukota: {$ibukota})",
                'versi_terkait' => '-'
            ]);

            return $this->respondSuccess(['id' => $id], "Informasi Kecamatan '{$nama}' berhasil diperbarui.");
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memperbarui kecamatan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /spasials/admin/wilayah/kecamatan/delete
     * Menghapus kecamatan (dilindungi jika memiliki desa/kelurahan terhubung)
     */
    public function deleteKecamatan(): ResponseInterface
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
        if ($id <= 0) {
            return $this->respondFailError('ID kecamatan tidak valid.', 400);
        }

        $db = \Config\Database::connect();
        try {
            $target = $db->table('ref_kecamatan')->where('id', $id)->get()->getRowArray();
            if (!$target) {
                return $this->respondFailError('Data kecamatan tidak ditemukan.', 404);
            }

            // Validasi integritas relasi: periksa apakah ada desa terkait
            $childDesaCount = $db->table('ref_desa')->where('kecamatan_id', $id)->countAllResults();
            if ($childDesaCount > 0) {
                return $this->respondFailError("Kecamatan '{$target['nama_kecamatan']}' tidak dapat dihapus karena masih memiliki {$childDesaCount} desa/kelurahan terhubung. Pindahkan atau hapus desa terkait terlebih dahulu.", 400);
            }

            $db->table('ref_kecamatan')->where('id', $id)->delete();

            AuditLogController::record([
                'kategori'      => 'WILAYAH',
                'aksi'          => 'DELETE_KECAMATAN',
                'aktor'         => 'Superadmin Diskominfo',
                'role'          => 'superadmin',
                'opd'           => 'Bagian Tata Pemerintahan Setda',
                'objek'         => "Kecamatan {$target['nama_kecamatan']} ({$target['kode_kecamatan']})",
                'keterangan'    => 'Penghapusan kecamatan dari basis data spasial resmi',
                'versi_terkait' => '-'
            ]);

            return $this->respondSuccess(['id' => $id], "Kecamatan '{$target['nama_kecamatan']}' berhasil dihapus.");
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal menghapus kecamatan: ' . $e->getMessage(), 500);
        }
    }

    // =========================================================================
    // MODUL CRUD WILAYAH: KELURAHAN & DESA (SUPERADMIN / ADMIN)
    // =========================================================================

    /**
     * GET /spasials/admin/wilayah/desa
     * Daftar seluruh desa / kelurahan dengan opsi filter kecamatan dan tipe
     */
    public function getAdminDesa(): ResponseInterface
    {
        $db = \Config\Database::connect();
        $kecamatanId = $this->request->getGet('kecamatan_id');
        $tipe = strtoupper(trim($this->request->getGet('tipe') ?? ''));
        $search = trim($this->request->getGet('search') ?? '');

        try {
            $sql = "SELECT d.id, d.kabupaten_id, d.kecamatan_id, k.nama_kecamatan,
                           d.kode_desa, d.nama_desa, d.tipe, d.luas_wilayah_km2,
                           ROUND(ST_X(ST_Centroid(ST_SRID(d.geom, 0))), 6) AS center_lng,
                           ROUND(ST_Y(ST_Centroid(ST_SRID(d.geom, 0))), 6) AS center_lat
                    FROM ref_desa d
                    INNER JOIN ref_kecamatan k ON k.id = d.kecamatan_id
                    WHERE 1=1";
            $params = [];

            if (!empty($kecamatanId)) {
                $sql .= " AND d.kecamatan_id = ?";
                $params[] = (int)$kecamatanId;
            }

            if (in_array($tipe, ['DESA', 'KELURAHAN'])) {
                $sql .= " AND d.tipe = ?";
                $params[] = $tipe;
            }

            if (!empty($search)) {
                $sql .= " AND (d.nama_desa LIKE ? OR d.kode_desa LIKE ? OR k.nama_kecamatan LIKE ?)";
                $like = '%' . $search . '%';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }

            $sql .= " ORDER BY k.nama_kecamatan ASC, d.nama_desa ASC";

            $rows = $db->query($sql, $params)->getResultArray();
            return $this->respondSuccess($rows, 'Daftar desa & kelurahan berhasil dimuat.');
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memuat desa: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /spasials/admin/wilayah/desa/create
     * Menambahkan desa atau kelurahan baru
     */
    public function createDesa(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $nama = trim($input['nama_desa'] ?? '');
        $kode = trim($input['kode_desa'] ?? '');
        $tipe = strtoupper(trim($input['tipe'] ?? 'DESA')) === 'KELURAHAN' ? 'KELURAHAN' : 'DESA';
        $kecamatanId = (int)($input['kecamatan_id'] ?? 0);
        $luas = (float)($input['luas_wilayah_km2'] ?? 0);
        $lng = (float)($input['center_lng'] ?? 124.57);
        $lat = (float)($input['center_lat'] ?? 1.18);

        if (empty($nama) || $kecamatanId <= 0) {
            return $this->respondFailError('Nama Desa/Kelurahan dan Kecamatan Induk wajib diisi.', 400);
        }

        $db = \Config\Database::connect();
        try {
            // Periksa kecamatan induk
            $kec = $db->table('ref_kecamatan')->where('id', $kecamatanId)->get()->getRowArray();
            if (!$kec) {
                return $this->respondFailError('Kecamatan induk yang dipilih tidak valid.', 400);
            }

            if (empty($kode)) {
                $kode = $kec['kode_kecamatan'] . '.' . rand(1001, 9999);
            }

            // Cek keunikan kode desa
            $exist = $db->table('ref_desa')->where('kode_desa', $kode)->countAllResults();
            if ($exist > 0) {
                return $this->respondFailError("Kode desa '{$kode}' sudah digunakan.", 400);
            }

            $d = 0.01; // Kotak poligon batas aproksimasi desa (~1km)
            $wkt = sprintf(
                "MULTIPOLYGON(((%F %F, %F %F, %F %F, %F %F, %F %F)))",
                $lng - $d, $lat - $d,
                $lng + $d, $lat - $d,
                $lng + $d, $lat + $d,
                $lng - $d, $lat + $d,
                $lng - $d, $lat - $d
            );

            $sql = "INSERT INTO ref_desa (kabupaten_id, kecamatan_id, kode_desa, nama_desa, tipe, luas_wilayah_km2, geom, created_at, updated_at)
                    VALUES (1, ?, ?, ?, ?, ?, ST_GeomFromText(?, 4326, 'axis-order=long-lat'), NOW(), NOW())";

            $db->query($sql, [$kecamatanId, $kode, $nama, $tipe, $luas, $wkt]);
            $newId = $db->insertID();

            AuditLogController::record([
                'kategori'      => 'WILAYAH',
                'aksi'          => 'CREATE_DESA',
                'aktor'         => 'Superadmin Diskominfo',
                'role'          => 'superadmin',
                'opd'           => 'Dinas PMD & Bagian Tapem',
                'objek'         => "{$tipe} {$nama} ({$kode})",
                'keterangan'    => "Penambahan {$tipe} baru di Kecamatan {$kec['nama_kecamatan']}, luas: {$luas} km²",
                'versi_terkait' => '-'
            ]);

            return $this->respondSuccess([
                'id' => $newId,
                'kecamatan_id' => $kecamatanId,
                'nama_kecamatan' => $kec['nama_kecamatan'],
                'kode_desa' => $kode,
                'nama_desa' => $nama,
                'tipe' => $tipe,
                'luas_wilayah_km2' => $luas,
                'center_lng' => $lng,
                'center_lat' => $lat
            ], "{$tipe} '{$nama}' berhasil ditambahkan ke Kecamatan {$kec['nama_kecamatan']}.");
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal menambahkan desa: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /spasials/admin/wilayah/desa/update
     * Memperbarui informasi desa atau kelurahan
     */
    public function updateDesa(): ResponseInterface
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
        $nama = trim($input['nama_desa'] ?? '');
        $kode = trim($input['kode_desa'] ?? '');
        $tipe = isset($input['tipe']) ? (strtoupper(trim($input['tipe'])) === 'KELURAHAN' ? 'KELURAHAN' : 'DESA') : null;
        $kecamatanId = isset($input['kecamatan_id']) ? (int)$input['kecamatan_id'] : null;
        $luas = (float)($input['luas_wilayah_km2'] ?? 0);
        $lng = isset($input['center_lng']) ? (float)$input['center_lng'] : null;
        $lat = isset($input['center_lat']) ? (float)$input['center_lat'] : null;

        if ($id <= 0) {
            return $this->respondFailError('ID desa tidak valid.', 400);
        }

        $db = \Config\Database::connect();
        try {
            $current = $db->table('ref_desa')->where('id', $id)->get()->getRowArray();
            if (!$current) {
                return $this->respondFailError('Data desa tidak ditemukan.', 404);
            }

            if ($lng !== null && $lat !== null) {
                $d = 0.01;
                $wkt = sprintf(
                    "MULTIPOLYGON(((%F %F, %F %F, %F %F, %F %F, %F %F)))",
                    $lng - $d, $lat - $d,
                    $lng + $d, $lat - $d,
                    $lng + $d, $lat + $d,
                    $lng - $d, $lat + $d,
                    $lng - $d, $lat - $d
                );
                $sql = "UPDATE ref_desa 
                        SET nama_desa = ?, kode_desa = ?, tipe = ?, kecamatan_id = ?, luas_wilayah_km2 = ?, 
                            geom = ST_GeomFromText(?, 4326, 'axis-order=long-lat'), updated_at = NOW()
                        WHERE id = ?";
                $db->query($sql, [
                    $nama ?: $current['nama_desa'],
                    $kode ?: $current['kode_desa'],
                    $tipe ?: $current['tipe'],
                    $kecamatanId ?: $current['kecamatan_id'],
                    $luas,
                    $wkt,
                    $id
                ]);
            } else {
                $sql = "UPDATE ref_desa 
                        SET nama_desa = ?, kode_desa = ?, tipe = ?, kecamatan_id = ?, luas_wilayah_km2 = ?, updated_at = NOW()
                        WHERE id = ?";
                $db->query($sql, [
                    $nama ?: $current['nama_desa'],
                    $kode ?: $current['kode_desa'],
                    $tipe ?: $current['tipe'],
                    $kecamatanId ?: $current['kecamatan_id'],
                    $luas,
                    $id
                ]);
            }

            AuditLogController::record([
                'kategori'      => 'WILAYAH',
                'aksi'          => 'UPDATE_DESA',
                'aktor'         => 'Superadmin Diskominfo',
                'role'          => 'superadmin',
                'opd'           => 'Dinas PMD & Bagian Tapem',
                'objek'         => "{$current['tipe']} {$nama} ({$kode})",
                'keterangan'    => "Pembaruan informasi wilayah desa/kelurahan (luas: {$luas} km²)",
                'versi_terkait' => '-'
            ]);

            return $this->respondSuccess(['id' => $id], "Data '{$nama}' berhasil diperbarui.");
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memperbarui desa: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /spasials/admin/wilayah/desa/delete
     * Menghapus desa atau kelurahan
     */
    public function deleteDesa(): ResponseInterface
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
        if ($id <= 0) {
            return $this->respondFailError('ID desa tidak valid.', 400);
        }

        $db = \Config\Database::connect();
        try {
            $target = $db->table('ref_desa')->where('id', $id)->get()->getRowArray();
            if (!$target) {
                return $this->respondFailError('Data desa tidak ditemukan.', 404);
            }

            $db->table('ref_desa')->where('id', $id)->delete();

            AuditLogController::record([
                'kategori'      => 'WILAYAH',
                'aksi'          => 'DELETE_DESA',
                'aktor'         => 'Superadmin Diskominfo',
                'role'          => 'superadmin',
                'opd'           => 'Dinas PMD & Bagian Tapem',
                'objek'         => "{$target['tipe']} {$target['nama_desa']} ({$target['kode_desa']})",
                'keterangan'    => 'Penghapusan desa/kelurahan dari sistem spasial',
                'versi_terkait' => '-'
            ]);

            return $this->respondSuccess(['id' => $id], "{$target['tipe']} '{$target['nama_desa']}' berhasil dihapus.");
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal menghapus desa: ' . $e->getMessage(), 500);
        }
    }
}
