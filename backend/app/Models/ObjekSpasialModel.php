<?php

namespace App\Models;

use CodeIgniter\Model;

class ObjekSpasialModel extends Model
{
    protected $table            = 'objek_spasial';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'kategori_id',
        'kabupaten_id',
        'kecamatan_id',
        'desa_id',
        'opd_id',
        'nama_objek',
        'tipe_geometri',
        'deskripsi',
        'foto_url',
        'atribut_tambahan',
        'geom',
        'is_active',
    ];
    protected $useTimestamps = true;

    /**
     * Ambil objek spasial berdasarkan kategori dengan filter hierarki administrasi dan BBOX
     *
     * @param string $kategoriKode
     * @param array $filters ['kecamatan_id' => ..., 'desa_id' => ..., 'opd_id' => ..., 'bbox' => ...]
     * @return array
     */
    public function getFiltered(string $kategoriKode, array $filters = []): array
    {
        $params = [$kategoriKode];

        $sql = "SELECT o.id, o.kategori_id, k.kode AS kategori_kode, k.nama_kategori,
                       o.kabupaten_id, o.kecamatan_id, kec.nama_kecamatan,
                       o.desa_id, des.nama_desa,
                       o.opd_id, opd.nama_opd,
                       o.nama_objek, o.tipe_geometri, o.deskripsi, o.foto_url, o.atribut_tambahan,
                       ST_AsGeoJSON(o.geom) AS geometry_geojson
                FROM {$this->table} o
                INNER JOIN kategori_layer k ON k.id = o.kategori_id
                LEFT JOIN ref_kecamatan kec ON kec.id = o.kecamatan_id
                LEFT JOIN ref_desa des ON des.id = o.desa_id
                LEFT JOIN ref_opd opd ON opd.id = o.opd_id
                WHERE k.kode = ? AND o.is_active = 1";

        // 1. Filter Hierarki Kecamatan
        if (!empty($filters['kecamatan_id'])) {
            $sql .= " AND o.kecamatan_id = ?";
            $params[] = (int)$filters['kecamatan_id'];
        }

        // 2. Filter Hierarki Desa
        if (!empty($filters['desa_id'])) {
            $sql .= " AND o.desa_id = ?";
            $params[] = (int)$filters['desa_id'];
        }

        // 3. Filter OPD
        if (!empty($filters['opd_id'])) {
            $sql .= " AND o.opd_id = ?";
            $params[] = (int)$filters['opd_id'];
        }

        // 4. Filter Bounding Box (BBOX) Viewport
        if (!empty($filters['bbox'])) {
            $coords = explode(',', $filters['bbox']);
            if (count($coords) === 4) {
                $minX = (float)$coords[0];
                $minY = (float)$coords[1];
                $maxX = (float)$coords[2];
                $maxY = (float)$coords[3];

                $polygonWkt = sprintf(
                    'POLYGON((%f %f, %f %f, %f %f, %f %f, %f %f))',
                    $minX, $minY,
                    $maxX, $minY,
                    $maxX, $maxY,
                    $minX, $maxY,
                    $minX, $minY
                );

                $sql .= " AND MBRIntersects(o.geom, ST_GeomFromText(?, 4326))";
                $params[] = $polygonWkt;
            }
        }

        $sql .= " ORDER BY o.nama_objek ASC";

        return $this->db->query($sql, $params)->getResultArray();
    }
}
