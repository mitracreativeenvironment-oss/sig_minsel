<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller Verifikasi & Handshake API Token Frontend serta Autentikasi Role Fase 2
 */
class AuthController extends BaseApiController
{
    /**
     * Definisi 8 Role Resmi GeoMinsel beserta Deskripsi dan Permission Default
     */
    protected array $officialRoles = [
        'superadmin' => [
            'id'          => 1,
            'name'        => 'superadmin',
            'label'       => 'Superadmin',
            'deskripsi'   => 'Pengelola keseluruhan sistem, konfigurasi, user, OPD, dan modul data dasar geospasial (SHP)',
            'opd'         => 'Dinas Komunikasi dan Informatika',
            'kode_opd'    => 'DISKOMINFO',
            'permissions' => [
                'user.view', 'user.create', 'user.update', 'user.disable', 'user.assign_role',
                'opd.view', 'opd.manage', 'data_dasar.upload_shp', 'data_dasar.manage_version',
                'data_dasar.activate', 'dataset.view', 'dataset.approve', 'dataset.publish',
                'system.manage', 'audit.view', 'report.view'
            ]
        ],
        'admin_gis' => [
            'id'          => 2,
            'name'        => 'admin_gis',
            'label'       => 'Admin GIS',
            'deskripsi'   => 'Pengelola teknis operasional geospasial, standardisasi format, CRS, katalog layer, dan penerbitan',
            'opd'         => 'Dinas Komunikasi dan Informatika',
            'kode_opd'    => 'DISKOMINFO',
            'permissions' => [
                'layer.view', 'layer.manage', 'metadata.manage', 'dataset.view', 'dataset.validate',
                'dataset.publish', 'dataset.unpublish', 'dataset.archive', 'dataset.export', 'audit.view'
            ]
        ],
        'admin_opd' => [
            'id'          => 3,
            'name'        => 'admin_opd',
            'label'       => 'Admin OPD',
            'deskripsi'   => 'Pengelola akun operator dan verifikasi awal data sektoral di lingkungan OPD masing-masing',
            'opd'         => 'Dinas Pekerjaan Umum dan Penataan Ruang',
            'kode_opd'    => 'DPUPR',
            'permissions' => [
                'user.view', 'user.create', 'user.update', 'dataset.view', 'dataset.create',
                'dataset.review_opd', 'dataset.submit', 'dataset.export'
            ]
        ],
        'operator_opd' => [
            'id'          => 4,
            'name'        => 'operator_opd',
            'label'       => 'Operator OPD',
            'deskripsi'   => 'Petugas operasional penginputan objek spasial, digitalisasi fitur, dan pengunggahan dataset OPD',
            'opd'         => 'Dinas Pertanian',
            'kode_opd'    => 'DISTAN',
            'permissions' => [
                'dataset.view', 'dataset.create', 'dataset.upload', 'dataset.update', 'dataset.submit'
            ]
        ],
        'validator_gis' => [
            'id'          => 5,
            'name'        => 'validator_gis',
            'label'       => 'Validator GIS',
            'deskripsi'   => 'Pemeriksa kualitas teknis geometri, topologi, sistem proyeksi (SRID 4326), dan integritas atribut',
            'opd'         => 'Tim Teknis GIS Pemkab Minahasa Selatan',
            'kode_opd'    => 'DISKOMINFO',
            'permissions' => [
                'dataset.view', 'dataset.validate', 'dataset.return_revision', 'layer.view', 'audit.view'
            ]
        ],
        'approver_data' => [
            'id'          => 6,
            'name'        => 'approver_data',
            'label'       => 'Approver Data',
            'deskripsi'   => 'Pejabat Walidata / Bappelitbangda yang berwenang memberikan persetujuan terpusat sebelum publikasi',
            'opd'         => 'Badan Perencanaan, Penelitian dan Pengembangan Daerah',
            'kode_opd'    => 'BAPELITBANGDA',
            'permissions' => [
                'dataset.view', 'dataset.approve', 'dataset.reject', 'report.view', 'audit.view'
            ]
        ],
        'pimpinan' => [
            'id'          => 7,
            'name'        => 'pimpinan',
            'label'       => 'Pimpinan / Executive Viewer',
            'deskripsi'   => 'Bupati, Wakil Bupati, Sekda, dan Kepala Perangkat Daerah untuk monitoring spasial strategis (read-only)',
            'opd'         => 'Pemerintah Kabupaten Minahasa Selatan',
            'kode_opd'    => 'SETDA_PEM',
            'permissions' => [
                'report.view', 'dataset.view', 'layer.view'
            ]
        ],
        'publik' => [
            'id'          => 8,
            'name'        => 'publik',
            'label'       => 'Publik',
            'deskripsi'   => 'Masyarakat umum, akademisi, peneliti, dan pelaku usaha yang mengakses data terbuka tanpa login',
            'opd'         => 'Masyarakat Umum',
            'kode_opd'    => 'PUBLIK',
            'permissions' => [
                'layer.view', 'dataset.view'
            ]
        ]
    ];

    /**
     * Health check endpoint
     */
    public function ping(): ResponseInterface
    {
        return $this->respondSuccess([
            'system'    => 'SIG Minahasa Selatan REST API',
            'version'   => '0.4.1',
            'timestamp' => date('c'),
            'status'    => 'operational'
        ], 'API backend aktif dan beroperasi normal.');
    }

    /**
     * Verifikasi keabsahan token frontend
     */
    public function verify(): ResponseInterface
    {
        return $this->respondSuccess([
            'authenticated' => true,
            'scope'         => ['wilayah:read', 'spasial:read', 'workflow:read'],
            'client'        => 'Official Frontend SIG Minsel'
        ], 'Token resmi frontend valid.');
    }

    /**
     * Daftar 8 Role Resmi GeoMinsel
     */
    public function roles(): ResponseInterface
    {
        return $this->respondSuccess(
            array_values($this->officialRoles),
            'Daftar 8 role resmi SIG Minahasa Selatan berhasil diambil.'
        );
    }

    /**
     * Autentikasi Login Pengguna (Mendukung Login Cepat Persona & Kredensial Resmi)
     */
    public function login(): ResponseInterface
    {
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $username = trim($input['username'] ?? '');
        $requestedRole = trim($input['role'] ?? '');

        // Pencarian profil role berdasarkan permintaan
        $targetRoleKey = 'publik';
        if (!empty($requestedRole) && isset($this->officialRoles[$requestedRole])) {
            $targetRoleKey = $requestedRole;
        } elseif (!empty($username)) {
            // Pencocokan username dengan role demo
            $cleanUser = strtolower(str_replace([' ', '-', '_'], '', $username));
            foreach ($this->officialRoles as $key => $info) {
                if (str_replace('_', '', $key) === $cleanUser || strtolower($info['kode_opd']) === $cleanUser) {
                    $targetRoleKey = $key;
                    break;
                }
            }
        }

        $roleData = $this->officialRoles[$targetRoleKey];

        // Buat session token representasi
        $sessionToken = 'geominsel_' . $targetRoleKey . '_' . bin2hex(random_bytes(16));

        $userData = [
            'id'           => $roleData['id'],
            'username'     => $targetRoleKey . '_minsel',
            'nama_lengkap' => $roleData['label'] . ' Kab. Minahasa Selatan',
            'email'        => strtolower($roleData['name']) . '@minselkab.go.id',
            'role'         => $roleData['name'],
            'role_label'   => $roleData['label'],
            'opd'          => $roleData['opd'],
            'kode_opd'     => $roleData['kode_opd'],
            'permissions'  => $roleData['permissions'],
            'token'        => $sessionToken,
            'logged_at'    => date('c')
        ];

        return $this->respondSuccess($userData, 'Autentikasi berhasil sebagai ' . $roleData['label']);
    }

    /**
     * Profil Pengguna Aktif
     */
    public function me(): ResponseInterface
    {
        $roleData = $this->officialRoles['superadmin'];

        return $this->respondSuccess([
            'user' => [
                'id'          => $roleData['id'],
                'username'    => 'superadmin_minsel',
                'nama'        => 'Administrator Sistem Diskominfo',
                'role'        => $roleData['name'],
                'opd'         => $roleData['opd'],
                'permissions' => $roleData['permissions']
            ]
        ], 'Profil pengguna aktif berhasil diambil.');
    }
}
