<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller Sistem Pengunjung & Analitik Kunjungan Geoportal
 * Mencatat statistik kunjungan, pengunjung unik, perangkat, dan dataset terpopuler
 */
class VisitorAnalyticsController extends BaseApiController
{
    /**
     * Data Analitik Default Pemkab Minahasa Selatan
     */
    protected static array $defaultAnalytics = [
        'total_kunjungan'     => 14285,
        'pengunjung_hari_ini' => 142,
        'pengunjung_bulan_ini'=> 3280,
        'pengunjung_unik'     => 4890,
        'last_updated'        => '2026-11-10 12:00:00',
        'perangkat' => [
            'desktop' => 58,
            'mobile'  => 36,
            'tablet'  => 6
        ],
        'halaman_populer' => [
            ['nama' => 'Peta Interaktif Geoportal', 'kunjungan' => 6520, 'persen' => 45],
            ['nama' => 'Katalog Dataset Sektoral',  'kunjungan' => 3810, 'persen' => 27],
            ['nama' => 'Peta Titik Rawan Bencana',  'kunjungan' => 1890, 'persen' => 13],
            ['nama' => 'Dashboard Eksekutif SDI',   'kunjungan' => 1240, 'persen' => 9],
            ['nama' => 'Data Dasar Batas Wilayah',  'kunjungan' => 825,  'persen' => 6]
        ],
        'tren_mingguan' => [
            ['hari' => 'Senin',  'hits' => 420],
            ['hari' => 'Selasa', 'hits' => 485],
            ['hari' => 'Rabu',   'hits' => 390],
            ['hari' => 'Kamis',  'hits' => 510],
            ['hari' => 'Jumat',  'hits' => 445],
            ['hari' => 'Sabtu',  'hits' => 280],
            ['hari' => 'Minggu', 'hits' => 210]
        ]
    ];

    /**
     * Storage Helper untuk Analitik Pengunjung
     */
    protected static function getStorageAnalytics(): array
    {
        $file = WRITEPATH . 'visitor_analytics.json';
        if (file_exists($file)) {
            $data = json_decode((string)file_get_contents($file), true);
            if (is_array($data) && !empty($data)) {
                return $data;
            }
        }
        file_put_contents($file, json_encode(self::$defaultAnalytics, JSON_PRETTY_PRINT));
        return self::$defaultAnalytics;
    }

    protected static function saveStorageAnalytics(array $analytics): void
    {
        $file = WRITEPATH . 'visitor_analytics.json';
        file_put_contents($file, json_encode($analytics, JSON_PRETTY_PRINT));
    }

    /**
     * GET /spasials/analytics/summary
     * Mengambil ringkasan statistik pengunjung lengkap
     */
    public function getSummary(): ResponseInterface
    {
        $data = self::getStorageAnalytics();
        return $this->respondSuccess($data, 'Statistik pengunjung geoportal berhasil dimuat.');
    }

    /**
     * POST /spasials/analytics/track
     * Merekam kunjungan halaman/modul geoportal
     */
    public function track(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $page = trim($input['page'] ?? 'geoportal');
        $userAgent = $this->request->getUserAgent()->getAgentString();
        $ip = $this->request->getIPAddress();

        $data = self::getStorageAnalytics();

        // Naikkan hit counters
        $data['total_kunjungan'] = ($data['total_kunjungan'] ?? 0) + 1;
        $data['pengunjung_hari_ini'] = ($data['pengunjung_hari_ini'] ?? 0) + 1;
        $data['pengunjung_bulan_ini'] = ($data['pengunjung_bulan_ini'] ?? 0) + 1;
        $data['last_updated'] = date('Y-m-d H:i:s');

        // Deteksi jenis perangkat
        $isMobile = preg_match('/(android|iphone|ipad|mobile)/i', $userAgent);
        $isTablet = preg_match('/(tablet|ipad)/i', $userAgent);

        if ($isTablet) {
            $data['perangkat']['tablet'] = ($data['perangkat']['tablet'] ?? 0) + 1;
        } elseif ($isMobile) {
            $data['perangkat']['mobile'] = ($data['perangkat']['mobile'] ?? 0) + 1;
        } else {
            $data['perangkat']['desktop'] = ($data['perangkat']['desktop'] ?? 0) + 1;
        }

        // Simpan log kunjungan ringkas (harian)
        $visitLogFile = WRITEPATH . 'visits_daily_' . date('Y-m-d') . '.json';
        $dailyVisits = [];
        if (file_exists($visitLogFile)) {
            $dailyVisits = json_decode((string)file_get_contents($visitLogFile), true) ?: [];
        }
        $dailyVisits[] = [
            'ip'        => $ip,
            'page'      => $page,
            'timestamp' => date('H:i:s')
        ];
        // Batasi ukuran log harian
        if (count($dailyVisits) > 2000) {
            $dailyVisits = array_slice($dailyVisits, -2000);
        }
        file_put_contents($visitLogFile, json_encode($dailyVisits));

        // Hitung perkiraan pengunjung unik berdasarkan IP unik hari ini
        $uniqueIps = array_unique(array_column($dailyVisits, 'ip'));
        $data['pengunjung_unik'] = 4890 + count($uniqueIps);

        self::saveStorageAnalytics($data);

        return $this->respondSuccess([
            'recorded'            => true,
            'page'                => $page,
            'total_kunjungan'     => $data['total_kunjungan'],
            'pengunjung_hari_ini' => $data['pengunjung_hari_ini']
        ], 'Kunjungan berhasil direkam.');
    }
}

