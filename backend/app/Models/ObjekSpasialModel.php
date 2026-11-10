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
     * Ambil objek spasial berdasarkan kode kategori dan filter Bounding Box (BBOX)
     *
     * @param string $kategoriKode
     * @param string|null $bboxFormat minX,minY,maxX,maxY (minLng,minLat,maxLng,maxLat)
     * @return array
     */
    public function getByKategori(string $kategoriKode, ?string $bboxFormat = null): array
    {
        $params = [$kategoriKode];

        $sql = "SELECT o.id, o.kategori_id, k.kode AS kategori_kode, k.nama_kategori,
                       o.nama_objek, o.tipe_geometri, o.deskripsi, o.foto_url, o.atribut_tambahan,
                       ST_AsGeoJSON(o.geom) AS geometry_geojson
                FROM {$this->table} o
                INNER JOIN kategori_layer k ON k.id = o.kategori_id
                WHERE k.kode = ? AND o.is_active = 1";

        // Filter efisien menggunakan MBRIntersects dan SPATIAL KEY index
        if (!empty($bboxFormat)) {
            $coords = explode(',', $bboxFormat);
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
