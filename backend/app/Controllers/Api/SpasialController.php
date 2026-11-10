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
            $kategori = $db->table('kategori_layer')
                ->where('is_active', 1)
                ->orderBy('nama_kategori', 'ASC')
                ->get()
                ->getResultArray();
        } catch (\Throwable $e) {
            return $this->respondFailError('Gagal memuat kategori layer: ' . $e->getMessage(), 500);
        }

        return $this->respondSuccess($kategori, 'Daftar kategori layer berhasil dimuat.');
    }

    /**
     * GET /api/v1/spasial/layer/:kategori
     * Mengambil layer fitur spasial berdasarkan kategori dengan opsional filter ?bbox=minX,minY,maxX,maxY
     */
    public function getLayer(string $kategoriKode): ResponseInterface
    {
        $bbox = $this->request->getGet('bbox');

        try {
            $rows = $this->objekModel->getByKategori($kategoriKode, $bbox);
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
                    'deskripsi'     => $row['deskripsi'],
                    'foto'          => $row['foto_url'],
                    'atribut'       => $atribut ?: new \stdClass(),
                ]
            ];
        }

        return $this->respondGeoJson($features, [
            'kategori' => $kategoriKode,
            'bbox'     => $bbox ?: 'full',
            'total'    => count($features),
        ]);
    }

    /**
     * GET /api/v1/spasial/objek/:num
     */
    public function getObjek($id): ResponseInterface
    {
        $db = \Config\Database::connect();
        $sql = "SELECT o.id, o.kategori_id, k.kode AS kategori_kode, k.nama_kategori,
                       o.nama_objek, o.tipe_geometri, o.deskripsi, o.foto_url, o.atribut_tambahan,
                       ST_AsGeoJSON(o.geom) AS geometry_geojson
                FROM objek_spasial o
                INNER JOIN kategori_layer k ON k.id = o.kategori_id
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
                'deskripsi'     => $row['deskripsi'],
                'foto'          => $row['foto_url'],
                'atribut'       => $atribut ?: new \stdClass(),
            ]
        ]);
    }
}
