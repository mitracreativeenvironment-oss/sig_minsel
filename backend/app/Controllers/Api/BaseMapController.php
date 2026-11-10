<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller Manajemen Peta Dasar (Base Map) Superadmin
 * Mendukung persistensi JSON di WRITEPATH dan pencatatan audit log otomatis
 */
class BaseMapController extends BaseApiController
{
    protected static array $defaultBasemaps = [
        [
            'id' => 1,
            'name' => 'carto-positron',
            'label' => 'Carto Positron (Terang)',
            'desc' => 'Desain minimalis bersih untuk analisis tematik geospasial',
            'type' => 'vector',
            'tile_url' => 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
            'attribution' => '&copy; OpenStreetMap contributors, &copy; CARTO',
            'max_zoom' => 19,
            'min_zoom' => 0,
            'preview_color' => '#f8fafc',
            'is_active' => true,
            'is_default' => true,
            'is_system' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 2,
            'name' => 'carto-dark',
            'label' => 'Carto Dark (Gelap)',
            'desc' => 'Mode gelap elegan kontras tinggi untuk pemantauan malam hari',
            'type' => 'vector',
            'tile_url' => 'https://basemaps.cartocdn.com/gl/dark-matter-gl-style/style.json',
            'attribution' => '&copy; OpenStreetMap contributors, &copy; CARTO',
            'max_zoom' => 19,
            'min_zoom' => 0,
            'preview_color' => '#0f172a',
            'is_active' => true,
            'is_default' => false,
            'is_system' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 3,
            'name' => 'carto-voyager',
            'label' => 'Carto Voyager (Warna)',
            'desc' => 'Peta jalan & toponimi bangunan kaya warna',
            'type' => 'vector',
            'tile_url' => 'https://basemaps.cartocdn.com/gl/voyager-gl-style/style.json',
            'attribution' => '&copy; OpenStreetMap contributors, &copy; CARTO',
            'max_zoom' => 19,
            'min_zoom' => 0,
            'preview_color' => '#f1f5f9',
            'is_active' => true,
            'is_default' => false,
            'is_system' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 4,
            'name' => 'osm',
            'label' => 'OpenStreetMap Resmi',
            'desc' => 'Standar komunitas GIS global terlengkap untuk jalan & fasilitas',
            'type' => 'raster',
            'tile_url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attribution' => '&copy; OpenStreetMap contributors',
            'max_zoom' => 19,
            'min_zoom' => 0,
            'preview_color' => '#e2e8f0',
            'is_active' => true,
            'is_default' => false,
            'is_system' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 5,
            'name' => 'esri-satellite',
            'label' => 'Citra Satelit Resolusi Tinggi',
            'desc' => 'Foto udara satelit mutakhir tutupan lahan dan pesisir Minahasa Selatan',
            'type' => 'raster',
            'tile_url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            'attribution' => 'Tiles &copy; Esri, Earthstar Geographics',
            'max_zoom' => 18,
            'min_zoom' => 0,
            'preview_color' => '#1e293b',
            'is_active' => true,
            'is_default' => false,
            'is_system' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 6,
            'name' => 'esri-topo',
            'label' => 'ESRI Topografi & Kontur',
            'desc' => 'Peta fisik elevasi pegunungan, lereng, dan Daerah Aliran Sungai (DAS)',
            'type' => 'raster',
            'tile_url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
            'attribution' => 'Tiles &copy; Esri, FAO, NOAA',
            'max_zoom' => 19,
            'min_zoom' => 0,
            'preview_color' => '#e0f2fe',
            'is_active' => true,
            'is_default' => false,
            'is_system' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 7,
            'name' => 'esri-street',
            'label' => 'ESRI Street Map',
            'desc' => 'Navigasi jaringan jalan utama dan jalan alternatif antarkecamatan',
            'type' => 'raster',
            'tile_url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
            'attribution' => 'Tiles &copy; Esri, DeLorme, NAVTEQ',
            'max_zoom' => 19,
            'min_zoom' => 0,
            'preview_color' => '#fef3c7',
            'is_active' => true,
            'is_default' => false,
            'is_system' => true,
            'created_at' => '2026-01-01 08:00:00'
        ],
        [
            'id' => 8,
            'name' => 'opentopomap',
            'label' => 'OpenTopoMap Relief',
            'desc' => 'Topografi bayangan bukit dan garis kontur berdetail tinggi',
            'type' => 'raster',
            'tile_url' => 'https://tile.opentopomap.org/{z}/{x}/{y}.png',
            'attribution' => 'Kartendaten: &copy; OpenStreetMap-Mitwirkende, SRTM | Kartendarstellung: &copy; OpenTopoMap',
            'max_zoom' => 17,
            'min_zoom' => 0,
            'preview_color' => '#ecfdf5',
            'is_active' => true,
            'is_default' => false,
            'is_system' => true,
            'created_at' => '2026-01-01 08:00:00'
        ]
    ];

    public static function getStorageBasemaps(): array
    {
        $file = WRITEPATH . 'basemaps.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data) && !empty($data)) {
                return $data;
            }
        }
        file_put_contents($file, json_encode(self::$defaultBasemaps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return self::$defaultBasemaps;
    }

    public static function saveStorageBasemaps(array $basemaps): void
    {
        $file = WRITEPATH . 'basemaps.json';
        file_put_contents($file, json_encode($basemaps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * GET /spasials/basemaps
     * Daftar peta dasar aktif yang tersedia untuk publik di Geoportal
     */
    public function getPublicBasemaps(): ResponseInterface
    {
        $all = self::getStorageBasemaps();
        $active = array_values(array_filter($all, fn($b) => !empty($b['is_active'])));

        return $this->respondSuccess([
            'basemaps' => $active,
            'default' => array_values(array_filter($active, fn($b) => !empty($b['is_default'])))[0] ?? ($active[0] ?? null)
        ], 'Daftar peta dasar aktif berhasil dimuat.');
    }

    /**
     * GET /spasials/admin/basemaps
     * Daftar seluruh peta dasar (aktif & nonaktif) untuk Superadmin
     */
    public function getAdminBasemaps(): ResponseInterface
    {
        $all = self::getStorageBasemaps();
        return $this->respondSuccess($all, 'Daftar master peta dasar berhasil dimuat.');
    }

    /**
     * POST /spasials/admin/basemaps/create
     * Menambahkan provider peta dasar baru
     */
    public function createBasemap(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $label = trim($input['label'] ?? '');
        $name = strtolower(trim($input['name'] ?? ''));
        $desc = trim($input['desc'] ?? '');
        $type = strtolower(trim($input['type'] ?? 'raster')) === 'vector' ? 'vector' : 'raster';
        $tileUrl = trim($input['tile_url'] ?? '');
        $attribution = trim($input['attribution'] ?? 'Peta Dasar GeoMinsel');
        $maxZoom = (int)($input['max_zoom'] ?? 19);
        $minZoom = (int)($input['min_zoom'] ?? 0);
        $previewColor = trim($input['preview_color'] ?? '#334155');

        if (empty($label) || empty($tileUrl)) {
            return $this->respondError('Nama / Label Peta Dasar dan URL Tile Tile/Style wajib diisi.', 400);
        }

        if (empty($name)) {
            $name = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $label));
        }

        $basemaps = self::getStorageBasemaps();
        foreach ($basemaps as $b) {
            if ($b['name'] === $name) {
                return $this->respondError("Pengenal Base Map '{$name}' sudah digunakan.", 400);
            }
        }

        $maxId = 0;
        foreach ($basemaps as $b) {
            if ($b['id'] > $maxId) {
                $maxId = $b['id'];
            }
        }
        $newId = $maxId + 1;

        $newBasemap = [
            'id' => $newId,
            'name' => $name,
            'label' => $label,
            'desc' => $desc ?: ('Peta dasar ' . $label),
            'type' => $type,
            'tile_url' => $tileUrl,
            'attribution' => $attribution,
            'max_zoom' => $maxZoom ?: 19,
            'min_zoom' => $minZoom ?: 0,
            'preview_color' => $previewColor,
            'is_active' => true,
            'is_default' => false,
            'is_system' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $basemaps[] = $newBasemap;
        self::saveStorageBasemaps($basemaps);

        AuditLogController::record([
            'kategori'      => 'BASEMAP',
            'aksi'          => 'CREATE_BASEMAP',
            'aktor'         => 'Superadmin Diskominfo',
            'role'          => 'superadmin',
            'opd'           => 'Dinas Komunikasi dan Informatika',
            'objek'         => "{$label} ({$name})",
            'keterangan'    => "Penambahan layer peta dasar baru tipe {$type}",
            'versi_terkait' => '-'
        ]);

        return $this->respondSuccess($newBasemap, "Peta dasar '{$label}' berhasil ditambahkan ke sistem.");
    }

    /**
     * POST /spasials/admin/basemaps/update
     * Memperbarui konfigurasi peta dasar
     */
    public function updateBasemap(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $id = (int)($input['id'] ?? 0);
        $label = trim($input['label'] ?? '');
        $desc = trim($input['desc'] ?? '');
        $type = isset($input['type']) ? (strtolower(trim($input['type'])) === 'vector' ? 'vector' : 'raster') : null;
        $tileUrl = trim($input['tile_url'] ?? '');
        $attribution = trim($input['attribution'] ?? '');
        $maxZoom = isset($input['max_zoom']) ? (int)$input['max_zoom'] : null;
        $minZoom = isset($input['min_zoom']) ? (int)$input['min_zoom'] : null;
        $previewColor = trim($input['preview_color'] ?? '');

        $basemaps = self::getStorageBasemaps();
        foreach ($basemaps as &$b) {
            if ($b['id'] === $id) {
                if (!empty($label)) $b['label'] = $label;
                if ($desc !== '') $b['desc'] = $desc;
                if ($type !== null) $b['type'] = $type;
                if (!empty($tileUrl)) $b['tile_url'] = $tileUrl;
                if (!empty($attribution)) $b['attribution'] = $attribution;
                if ($maxZoom !== null) $b['max_zoom'] = $maxZoom;
                if ($minZoom !== null) $b['min_zoom'] = $minZoom;
                if (!empty($previewColor)) $b['preview_color'] = $previewColor;

                self::saveStorageBasemaps($basemaps);

                AuditLogController::record([
                    'kategori'      => 'BASEMAP',
                    'aksi'          => 'UPDATE_BASEMAP',
                    'aktor'         => 'Superadmin Diskominfo',
                    'role'          => 'superadmin',
                    'opd'           => 'Dinas Komunikasi dan Informatika',
                    'objek'         => "{$b['label']} ({$b['name']})",
                    'keterangan'    => 'Pembaruan parameter tile URL / atribusi peta dasar',
                    'versi_terkait' => '-'
                ]);

                return $this->respondSuccess($b, "Konfigurasi peta dasar '{$b['label']}' berhasil diperbarui.");
            }
        }

        return $this->respondError('Peta dasar tidak ditemukan.', 404);
    }

    /**
     * POST /spasials/admin/basemaps/toggle
     * Mengaktifkan atau menonaktifkan peta dasar
     */
    public function toggleBasemap(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $id = (int)($input['id'] ?? 0);
        $basemaps = self::getStorageBasemaps();

        foreach ($basemaps as &$b) {
            if ($b['id'] === $id) {
                if ($b['is_default'] && $b['is_active']) {
                    return $this->respondError("Peta dasar rujukan default ('{$b['label']}') tidak dapat dinonaktifkan.", 400);
                }

                $b['is_active'] = !$b['is_active'];
                self::saveStorageBasemaps($basemaps);

                $statusText = $b['is_active'] ? 'diaktifkan' : 'dinonaktifkan';

                AuditLogController::record([
                    'kategori'      => 'BASEMAP',
                    'aksi'          => $b['is_active'] ? 'AKTIFKAN_BASEMAP' : 'NONAKTIFKAN_BASEMAP',
                    'aktor'         => 'Superadmin Diskominfo',
                    'role'          => 'superadmin',
                    'opd'           => 'Dinas Komunikasi dan Informatika',
                    'objek'         => "{$b['label']} ({$b['name']})",
                    'keterangan'    => "Peta dasar berhasil {$statusText}",
                    'versi_terkait' => '-'
                ]);

                return $this->respondSuccess($b, "Peta dasar '{$b['label']}' berhasil {$statusText}.");
            }
        }

        return $this->respondError('Peta dasar tidak ditemukan.', 404);
    }

    /**
     * POST /spasials/admin/basemaps/set-default
     * Menetapkan peta dasar default saat pertama kali Geoportal dibuka
     */
    public function setDefault(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $id = (int)($input['id'] ?? 0);
        $basemaps = self::getStorageBasemaps();
        $target = null;

        foreach ($basemaps as &$b) {
            if ($b['id'] === $id) {
                $b['is_default'] = true;
                $b['is_active'] = true; // Otomatis aktifkan
                $target = $b;
            } else {
                $b['is_default'] = false;
            }
        }

        if (!$target) {
            return $this->respondError('Peta dasar tidak ditemukan.', 404);
        }

        self::saveStorageBasemaps($basemaps);

        AuditLogController::record([
            'kategori'      => 'BASEMAP',
            'aksi'          => 'SET_DEFAULT_BASEMAP',
            'aktor'         => 'Superadmin Diskominfo',
            'role'          => 'superadmin',
            'opd'           => 'Dinas Komunikasi dan Informatika',
            'objek'         => "{$target['label']} ({$target['name']})",
            'keterangan'    => 'Menetapkan sebagai peta dasar utama default Geoportal',
            'versi_terkait' => '-'
        ]);

        return $this->respondSuccess($target, "Peta dasar '{$target['label']}' ditetapkan sebagai rujukan default.");
    }

    /**
     * POST /spasials/admin/basemaps/delete
     * Menghapus konfigurasi peta dasar (khusus kustom non-sistem)
     */
    public function deleteBasemap(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $id = (int)($input['id'] ?? 0);
        $basemaps = self::getStorageBasemaps();
        $targetKey = null;

        foreach ($basemaps as $key => $b) {
            if ($b['id'] === $id) {
                if (!empty($b['is_system'])) {
                    return $this->respondError("Peta dasar bawaan sistem ('{$b['label']}') dilindungi dan tidak boleh dihapus. Anda dapat menonaktifkannya.", 403);
                }
                if (!empty($b['is_default'])) {
                    return $this->respondError("Peta dasar rujukan default tidak dapat dihapus.", 400);
                }
                $targetKey = $key;
                break;
            }
        }

        if ($targetKey === null) {
            return $this->respondError('Peta dasar tidak ditemukan.', 404);
        }

        $deletedLabel = $basemaps[$targetKey]['label'];
        unset($basemaps[$targetKey]);
        $basemaps = array_values($basemaps);
        self::saveStorageBasemaps($basemaps);

        AuditLogController::record([
            'kategori'      => 'BASEMAP',
            'aksi'          => 'DELETE_BASEMAP',
            'aktor'         => 'Superadmin Diskominfo',
            'role'          => 'superadmin',
            'opd'           => 'Dinas Komunikasi dan Informatika',
            'objek'         => $deletedLabel,
            'keterangan'    => 'Penghapusan konfigurasi peta dasar dari daftar',
            'versi_terkait' => '-'
        ]);

        return $this->respondSuccess(['id' => $id], "Peta dasar '{$deletedLabel}' berhasil dihapus.");
    }
}
