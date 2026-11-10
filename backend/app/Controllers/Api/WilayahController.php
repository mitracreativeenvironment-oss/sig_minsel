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
}
