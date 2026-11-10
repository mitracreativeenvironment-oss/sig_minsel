<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller Sistem Audit Trail & Logging Terintegrasi GeoMinsel
 * Merekam setiap riwayat transaksi geospasial, alur persetujuan, manajemen master data, dan autentikasi
 */
class AuditLogController extends BaseApiController
{
    /**
     * Data Log Default & Awal Pemkab Minahasa Selatan
     */
    protected static array $defaultLogs = [
        [
            'id' => 1,
            'timestamp' => '2026-11-10 10:45:12',
            'kategori' => 'DATASET',
            'aksi' => 'PUBLISH',
            'aktor' => 'Administrator GIS Pemkab Minsel',
            'role' => 'admin_gis',
            'opd' => 'Dinas Komunikasi dan Informatika',
            'objek' => 'Sebaran Lahan Pertanian Berkelanjutan (LP2B)',
            'keterangan' => 'Penerbitan resmi dataset ke geoportal publik setelah disetujui Walidata.',
            'ip_address' => '192.168.1.10',
            'versi_terkait' => 'v2.0'
        ],
        [
            'id' => 2,
            'timestamp' => '2026-11-10 10:15:30',
            'kategori' => 'PERSETUJUAN',
            'aksi' => 'APPROVE',
            'aktor' => 'Approver Data Bappelitbangda (Walidata)',
            'role' => 'approver_data',
            'opd' => 'Badan Perencanaan, Penelitian dan Pengembangan Daerah',
            'objek' => 'Peta Titik Rawan Banjir DAS Ranoyapo',
            'keterangan' => 'Penerbitan surat rekomendasi persetujuan Walidata No: 045/SDI-MINSEL/2026.',
            'ip_address' => '192.168.1.15',
            'versi_terkait' => 'v1.2'
        ],
        [
            'id' => 3,
            'timestamp' => '2026-11-10 09:30:18',
            'kategori' => 'VALIDASI',
            'aksi' => 'VALIDATE',
            'aktor' => 'Validator Teknis Geospasial',
            'role' => 'validator_gis',
            'opd' => 'Tim Teknis GIS Pemkab Minsel',
            'objek' => 'Peta Titik Rawan Banjir DAS Ranoyapo',
            'keterangan' => 'Topologi poligon dan sistem referensi koordinat SRID EPSG:4326 terverifikasi valid.',
            'ip_address' => '192.168.1.18',
            'versi_terkait' => 'v1.1'
        ],
        [
            'id' => 4,
            'timestamp' => '2026-11-10 09:00:05',
            'kategori' => 'DATASET',
            'aksi' => 'SUBMIT',
            'aktor' => 'Operator Data Dinas Pertanian',
            'role' => 'operator_opd',
            'opd' => 'Dinas Pertanian',
            'objek' => 'Sebaran Lahan Pertanian Berkelanjutan (LP2B)',
            'keterangan' => 'Pengajuan draft dataset ke antrean validasi teknis GIS.',
            'ip_address' => '192.168.1.25',
            'versi_terkait' => 'v1.0'
        ],
        [
            'id' => 5,
            'timestamp' => '2026-11-10 08:30:00',
            'kategori' => 'SHP_WILAYAH',
            'aksi' => 'ACTIVATE',
            'aktor' => 'Superadmin Diskominfo Minsel',
            'role' => 'superadmin',
            'opd' => 'Dinas Komunikasi dan Informatika',
            'objek' => 'Batas Kabupaten Minahasa Selatan (Versi 2026.1-RESMI)',
            'keterangan' => 'Aktivasi versi batas administrasi rujukan resmi rujukan Satu Data Indonesia.',
            'ip_address' => '192.168.1.5',
            'versi_terkait' => '2026.1'
        ],
        [
            'id' => 6,
            'timestamp' => '2026-11-10 08:00:22',
            'kategori' => 'AUTENTIKASI',
            'aksi' => 'LOGIN',
            'aktor' => 'Superadmin Diskominfo Minsel',
            'role' => 'superadmin',
            'opd' => 'Dinas Komunikasi dan Informatika',
            'objek' => 'Sesi Dashboard Admin',
            'keterangan' => 'Otorisasi sesi berhasil menggunakan kredensial token resmi.',
            'ip_address' => '192.168.1.5',
            'versi_terkait' => 'system'
        ]
    ];

    /**
     * Storage Helper untuk Audit Logs
     */
    public static function getStorageLogs(): array
    {
        $file = WRITEPATH . 'audit_logs.json';
        if (file_exists($file)) {
            $data = json_decode((string)file_get_contents($file), true);
            if (is_array($data) && !empty($data)) {
                return $data;
            }
        }
        file_put_contents($file, json_encode(self::$defaultLogs, JSON_PRETTY_PRINT));
        return self::$defaultLogs;
    }

    public static function saveStorageLogs(array $logs): void
    {
        $file = WRITEPATH . 'audit_logs.json';
        file_put_contents($file, json_encode($logs, JSON_PRETTY_PRINT));
    }

    /**
     * Helper Static Terintegrasi: Catat Aktivitas Sistem
     */
    public static function record(array $data): array
    {
        $logs = self::getStorageLogs();
        $maxId = 0;
        foreach ($logs as $l) {
            if ($l['id'] > $maxId) {
                $maxId = $l['id'];
            }
        }

        $newLog = [
            'id'            => $maxId + 1,
            'timestamp'     => date('Y-m-d H:i:s'),
            'kategori'      => strtoupper(trim($data['kategori'] ?? 'SISTEM')),
            'aksi'          => strtoupper(trim($data['aksi'] ?? 'INFO')),
            'aktor'         => trim($data['aktor'] ?? 'Sistem GeoMinsel'),
            'role'          => trim($data['role'] ?? 'system'),
            'opd'           => trim($data['opd'] ?? 'Pemkab Minahasa Selatan'),
            'objek'         => trim($data['objek'] ?? '-'),
            'keterangan'    => trim($data['keterangan'] ?? ''),
            'ip_address'    => trim($data['ip_address'] ?? '127.0.0.1'),
            'versi_terkait' => trim($data['versi_terkait'] ?? '-')
        ];

        // Tambahkan di urutan paling depan (terbaru)
        array_unshift($logs, $newLog);

        // Batasi riwayat maksimum 500 entri untuk efisiensi
        if (count($logs) > 500) {
            $logs = array_slice($logs, 0, 500);
        }

        self::saveStorageLogs($logs);
        return $newLog;
    }

    /**
     * GET /spasials/admin/logs
     * Mengambil daftar log audit dengan dukungan filter dan pencarian
     */
    public function getLogs(): ResponseInterface
    {
        $kategoriFilter = strtoupper(trim($this->request->getGet('kategori') ?? ''));
        $aksiFilter = strtoupper(trim($this->request->getGet('aksi') ?? ''));
        $search = strtolower(trim($this->request->getGet('search') ?? ''));

        $logs = self::getStorageLogs();

        if (!empty($kategoriFilter)) {
            $logs = array_values(array_filter($logs, fn($l) => $l['kategori'] === $kategoriFilter));
        }

        if (!empty($aksiFilter)) {
            $logs = array_values(array_filter($logs, fn($l) => $l['aksi'] === $aksiFilter));
        }

        if (!empty($search)) {
            $logs = array_values(array_filter($logs, fn($l) => 
                str_contains(strtolower($l['objek']), $search) ||
                str_contains(strtolower($l['aktor']), $search) ||
                str_contains(strtolower($l['opd']), $search) ||
                str_contains(strtolower($l['keterangan']), $search)
            ));
        }

        return $this->respondSuccess([
            'total' => count($logs),
            'logs'  => $logs,
            'kategori_list' => [
                'DATASET', 'VALIDASI', 'PERSETUJUAN', 'SHP_WILAYAH', 
                'MASTER_OPD', 'KATEGORI', 'PENGGUNA', 'AUTENTIKASI'
            ],
            'aksi_list' => [
                'CREATE', 'UPDATE', 'DELETE', 'SUBMIT', 'VALIDATE', 
                'APPROVE', 'REJECT', 'PUBLISH', 'ACTIVATE', 'LOGIN'
            ]
        ], 'Daftar audit log berhasil dimuat.');
    }

    /**
     * POST /spasials/admin/logs/create
     * Endpoint API pencatatan log manual jika diperlukan
     */
    public function createLog(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        if (empty($input['objek']) || empty($input['keterangan'])) {
            return $this->respondError('Objek dan keterangan log wajib disertakan.', 400);
        }

        $input['ip_address'] = $this->request->getIPAddress();
        $recorded = self::record($input);

        return $this->respondSuccess($recorded, 'Log audit berhasil dicatat.');
    }
}
