<?php

namespace App\Models;

use CodeIgniter\Model;

class KecamatanModel extends Model
{
    protected $table            = 'ref_kecamatan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'kode_kecamatan',
        'nama_kecamatan',
        'ibukota_kecamatan',
        'luas_wilayah_km2',
        'geom',
    ];
    protected $useTimestamps = true;

    /**
     * Ambil semua data kecamatan beserta geometri GeoJSON
     */
    public function getWithGeoJson(): array
    {
        $sql = "SELECT id, kode_kecamatan, nama_kecamatan, ibukota_kecamatan, luas_wilayah_km2,
                       ST_AsGeoJSON(geom) AS geometry_geojson,
                       ST_X(ST_Centroid(geom)) AS center_lng,
                       ST_Y(ST_Centroid(geom)) AS center_lat
                FROM {$this->table}
                ORDER BY nama_kecamatan ASC";

        return $this->db->query($sql)->getResultArray();
    }

    /**
     * Ambil data satu kecamatan berdasarkan ID atau kode
     */
    public function getDetailWithGeoJson($idOrKode): ?array
    {
        $sql = "SELECT id, kode_kecamatan, nama_kecamatan, ibukota_kecamatan, luas_wilayah_km2,
                       ST_AsGeoJSON(geom) AS geometry_geojson,
                       ST_X(ST_Centroid(geom)) AS center_lng,
                       ST_Y(ST_Centroid(geom)) AS center_lat
                FROM {$this->table}
                WHERE id = ? OR kode_kecamatan = ?
                LIMIT 1";

        $result = $this->db->query($sql, [$idOrKode, $idOrKode])->getRowArray();
        return $result ?: null;
    }
}
