<?php

namespace App\Models;

use CodeIgniter\Model;

class DesaModel extends Model
{
    protected $table            = 'ref_desa';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'kabupaten_id',
        'kecamatan_id',
        'kode_desa',
        'nama_desa',
        'tipe',
        'luas_wilayah_km2',
        'geom',
    ];
    protected $useTimestamps = true;

    /**
     * Ambil data spasial desa dengan relasi lengkap ke Kecamatan dan Kabupaten
     *
     * @param int|null $kecamatanId
     * @param int|null $kabupatenId
     * @return array
     */
    public function getFiltered(?int $kecamatanId = null, ?int $kabupatenId = 1): array
    {
        $params = [];
        $sql = "SELECT d.id, d.kabupaten_id, b.nama_kabupaten,
                       d.kecamatan_id, k.nama_kecamatan,
                       d.kode_desa, d.nama_desa, d.tipe, d.luas_wilayah_km2,
                       ST_AsGeoJSON(d.geom) AS geometry_geojson,
                       ST_X(ST_Centroid(ST_SRID(d.geom, 0))) AS center_lng,
                       ST_Y(ST_Centroid(ST_SRID(d.geom, 0))) AS center_lat
                FROM {$this->table} d
                INNER JOIN ref_kecamatan k ON k.id = d.kecamatan_id
                INNER JOIN ref_kabupaten b ON b.id = d.kabupaten_id
                WHERE 1=1";

        if ($kabupatenId !== null) {
            $sql .= " AND d.kabupaten_id = ?";
            $params[] = $kabupatenId;
        }

        if ($kecamatanId !== null) {
            $sql .= " AND d.kecamatan_id = ?";
            $params[] = $kecamatanId;
        }

        $sql .= " ORDER BY k.nama_kecamatan ASC, d.nama_desa ASC";

        return $this->db->query($sql, $params)->getResultArray();
    }
}
