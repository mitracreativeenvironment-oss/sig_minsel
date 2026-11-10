<?php

namespace App\Controllers\Api;

use App\Models\KecamatanModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller API Batas Administrasi Wilayah Kabupaten Minahasa Selatan
 */
class WilayahController extends BaseApiController
{
    protected KecamatanModel $kecamatanModel;

    public function __construct()
    {
        $this->kecamatanModel = new KecamatanModel();
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
            // Fallback jika database belum dimigrasikan: kembalikan sample list
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
     * GET /api/v1/wilayah/kecamatan/:segment
     * Detail satu kecamatan
     */
    public function detailKecamatan($idOrKode): ResponseInterface
    {
        $row = $this->kecamatanModel->getDetailWithGeoJson($idOrKode);

        if (!$row) {
            return $this->respondFailError("Kecamatan dengan ID/Kode '{$idOrKode}' tidak ditemukan.", 404);
        }

        $geom = json_decode($row['geometry_geojson'] ?? '{}', true);

        return $this->respondSuccess([
            'type' => 'Feature',
            'id'   => (int)$row['id'],
            'geometry' => $geom,
            'properties' => [
                'id'       => (int)$row['id'],
                'kode'     => $row['kode_kecamatan'],
                'nama'     => $row['nama_kecamatan'],
                'ibukota'  => $row['ibukota_kecamatan'],
                'luas_km2' => (float)$row['luas_wilayah_km2'],
                'center'   => [
                    'lng' => (float)$row['center_lng'],
                    'lat' => (float)$row['center_lat']
                ],
            ]
        ]);
    }
}
