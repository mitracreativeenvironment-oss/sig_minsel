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
        'kabupaten_id',
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
        $sql = "SELECT k.id, k.kabupaten_id, b.nama_kabupaten, k.kode_kecamatan, k.nama_kecamatan, k.ibukota_kecamatan, k.luas_wilayah_km2,
                       ST_AsGeoJSON(k.geom) AS geometry_geojson,
                       ST_X(ST_Centroid(k.geom)) AS center_lng,
                       ST_Y(ST_Centroid(k.geom)) AS center_lat
                FROM {$this->table} k
                LEFT JOIN ref_kabupaten b ON b.id = k.kabupaten_id
                ORDER BY k.nama_kecamatan ASC";

        return $this->db->query($sql)->getResultArray();
    }

    /**
     * Ambil data satu kecamatan berdasarkan ID atau kode
     */
    public function getDetailWithGeoJson($idOrKode): ?array
    {
        $sql = "SELECT k.id, k.kabupaten_id, b.nama_kabupaten, k.kode_kecamatan, k.nama_kecamatan, k.ibukota_kecamatan, k.luas_wilayah_km2,
                       ST_AsGeoJSON(k.geom) AS geometry_geojson,
                       ST_X(ST_Centroid(k.geom)) AS center_lng,
                       ST_Y(ST_Centroid(k.geom)) AS center_lat
                FROM {$this->table} k
                LEFT JOIN ref_kabupaten b ON b.id = k.kabupaten_id
                WHERE k.id = ? OR k.kode_kecamatan = ?
                LIMIT 1";

        $result = $this->db->query($sql, [$idOrKode, $idOrKode])->getRowArray();
        return $result ?: null;
    }
}
