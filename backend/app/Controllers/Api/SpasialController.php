<?php

namespace App\Controllers\Api;

use App\Models\ObjekSpasialModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller API Layer Spasial Tematik (Point, Line, Polygon)
 */
class SpasialController extends BaseApiController
{
    protected ObjekSpasialModel $objekModel;

    public function __construct()
    {
        $this->objekModel = new ObjekSpasialModel();
    }

    /**
     * GET /api/v1/spasial/kategori
     * Mengambil daftar seluruh kategori layer yang tersedia
     */
    public function kategori(): ResponseInterface
    {
        $db = \Config\Database::connect();
        try {
            $kategori = $db->table('kategori_layer k')
                ->select('k.id, k.kode, k.nama_kategori, k.deskripsi, k.icon, k.warna_default, k.opd_id, o.nama_opd')
                ->join('ref_opd o', 'o.id = k.opd_id', 'left')
                ->where('k.is_active', 1)
                ->orderBy('k.nama_kategori', 'ASC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memuat kategori layer: ' . $e->getMessage(), 500);
        }

        return $this->respondSuccess($kategori, 'Daftar kategori layer berhasil dimuat.');
    }

    /**
     * GET /api/v1/spasial/layer/:kategori
     * Mengambil layer fitur spasial berdasarkan kategori dengan filter hierarki administrasi dan BBOX
     */
    public function getLayer(string $kategoriKode): ResponseInterface
    {
        $filters = [
            'kecamatan_id' => $this->request->getGet('kecamatan_id'),
            'desa_id'      => $this->request->getGet('desa_id'),
            'opd_id'       => $this->request->getGet('opd_id'),
            'bbox'         => $this->request->getGet('bbox'),
        ];

        try {
            $rows = $this->objekModel->getFiltered($kategoriKode, $filters);
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memuat fitur layer spasial: ' . $e->getMessage(), 500);
        }

        $features = [];
        foreach ($rows as $row) {
            $geom = json_decode($row['geometry_geojson'] ?? '{}', true);
            $atribut = json_decode($row['atribut_tambahan'] ?? '{}', true);

            $features[] = [
                'type' => 'Feature',
                'id'   => (int)$row['id'],
                'geometry' => $geom,
                'properties' => [
                    'id'            => (int)$row['id'],
                    'nama'          => $row['nama_objek'],
                    'tipe_geometri' => $row['tipe_geometri'],
                    'kategori'      => $row['kategori_kode'],
                    'nama_kategori' => $row['nama_kategori'],
                    'kabupaten_id'  => (int)$row['kabupaten_id'],
                    'kecamatan_id'  => $row['kecamatan_id'] ? (int)$row['kecamatan_id'] : null,
                    'kecamatan'     => $row['nama_kecamatan'],
                    'desa_id'       => $row['desa_id'] ? (int)$row['desa_id'] : null,
                    'desa'          => $row['nama_desa'],
                    'opd_id'        => $row['opd_id'] ? (int)$row['opd_id'] : null,
                    'opd'           => $row['nama_opd'],
                    'deskripsi'     => $row['deskripsi'],
                    'foto'          => $row['foto_url'],
                    'atribut'       => $atribut ?: new \stdClass(),
                ]
            ];
        }

        return $this->respondGeoJson($features, [
            'kategori'     => $kategoriKode,
            'kecamatan_id' => $filters['kecamatan_id'] ?: 'all',
            'desa_id'      => $filters['desa_id'] ?: 'all',
            'bbox'         => $filters['bbox'] ?: 'full',
            'total'        => count($features),
        ]);
    }

    /**
     * GET /api/v1/spasial/objek/:num
     */
    public function getObjek($id): ResponseInterface
    {
        $db = \Config\Database::connect();
        $sql = "SELECT o.id, o.kategori_id, k.kode AS kategori_kode, k.nama_kategori,
                       o.kabupaten_id, o.kecamatan_id, kec.nama_kecamatan,
                       o.desa_id, des.nama_desa,
                       o.opd_id, opd.nama_opd,
                       o.nama_objek, o.tipe_geometri, o.deskripsi, o.foto_url, o.atribut_tambahan,
                       ST_AsGeoJSON(o.geom) AS geometry_geojson
                FROM objek_spasial o
                INNER JOIN kategori_layer k ON k.id = o.kategori_id
                LEFT JOIN ref_kecamatan kec ON kec.id = o.kecamatan_id
                LEFT JOIN ref_desa des ON des.id = o.desa_id
                LEFT JOIN ref_opd opd ON opd.id = o.opd_id
                WHERE o.id = ? AND o.is_active = 1
                LIMIT 1";

        $row = $db->query($sql, [$id])->getRowArray();

        if (!$row) {
            return $this->respondFailError("Objek spasial dengan ID {$id} tidak ditemukan.", 404);
        }

        $geom = json_decode($row['geometry_geojson'] ?? '{}', true);
        $atribut = json_decode($row['atribut_tambahan'] ?? '{}', true);

        return $this->respondSuccess([
            'type' => 'Feature',
            'id'   => (int)$row['id'],
            'geometry' => $geom,
            'properties' => [
                'id'            => (int)$row['id'],
                'nama'          => $row['nama_objek'],
                'tipe_geometri' => $row['tipe_geometri'],
                'kategori'      => $row['kategori_kode'],
                'nama_kategori' => $row['nama_kategori'],
                'kabupaten_id'  => (int)$row['kabupaten_id'],
                'kecamatan_id'  => $row['kecamatan_id'] ? (int)$row['kecamatan_id'] : null,
                'kecamatan'     => $row['nama_kecamatan'],
                'desa_id'       => $row['desa_id'] ? (int)$row['desa_id'] : null,
                'desa'          => $row['nama_desa'],
                'opd_id'        => $row['opd_id'] ? (int)$row['opd_id'] : null,
                'opd'           => $row['nama_opd'],
                'deskripsi'     => $row['deskripsi'],
                'foto'          => $row['foto_url'],
                'atribut'       => $atribut ?: new \stdClass(),
            ]
        ]);
    }
}
