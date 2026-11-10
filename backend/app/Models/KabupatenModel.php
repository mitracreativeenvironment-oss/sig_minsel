<?php

namespace App\Models;

use CodeIgniter\Model;

class KabupatenModel extends Model
{
    protected $table            = 'ref_kabupaten';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'kode_kabupaten',
        'nama_kabupaten',
        'ibukota',
        'luas_wilayah_km2',
        'deskripsi',
        'geom',
    ];
    protected $useTimestamps = true;

    /**
     * Ambil data wilayah induk Kabupaten Minahasa Selatan
     */
    public function getKabupatenMinsel(): ?array
    {
        $sql = "SELECT id, kode_kabupaten, nama_kabupaten, ibukota, luas_wilayah_km2, deskripsi,
                       ST_AsGeoJSON(geom) AS geometry_geojson,
                       ST_X(ST_Centroid(geom)) AS center_lng,
                       ST_Y(ST_Centroid(geom)) AS center_lat
                FROM {$this->table}
                WHERE id = 1 OR kode_kabupaten = '71.05'
                LIMIT 1";

        $result = $this->db->query($sql)->getRowArray();
        return $result ?: null;
    }
}

