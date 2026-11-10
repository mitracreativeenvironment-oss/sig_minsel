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
     * Data Pengguna Resmi Terdaftar (Modul Manajemen Pengguna)
     */
    protected static array $mockUsers = [
        [
            'id' => 1,
            'username' => 'superadmin',
            'nama_lengkap' => 'Superadmin Diskominfo Minsel',
            'email' => 'superadmin@minahasaselatan.go.id',
            'role' => 'superadmin',
            'role_label' => 'Superadmin',
            'opd' => 'Dinas Komunikasi dan Informatika',
            'kode_opd' => 'DISKOMINFO',
            'is_active' => true,
            'created_at' => '2026-01-10 08:00:00'
        ],
        [
            'id' => 2,
            'username' => 'admin_gis',
            'nama_lengkap' => 'Administrator GIS Pemkab Minsel',
            'email' => 'admingis@minahasaselatan.go.id',
            'role' => 'admin_gis',
            'role_label' => 'Admin GIS',
            'opd' => 'Dinas Komunikasi dan Informatika',
            'kode_opd' => 'DISKOMINFO',
            'is_active' => true,
            'created_at' => '2026-01-10 08:05:00'
        ],
        [
            'id' => 3,
            'username' => 'admin_pupr',
            'nama_lengkap' => 'Admin OPD Dinas PUPR Minsel',
            'email' => 'admin.pupr@minahasaselatan.go.id',
            'role' => 'admin_opd',
            'role_label' => 'Admin OPD (PUPR)',
            'opd' => 'Dinas Pekerjaan Umum dan Penataan Ruang',
            'kode_opd' => 'DPUPR',
            'is_active' => true,
            'created_at' => '2026-01-15 09:00:00'
        ],
        [
            'id' => 4,
            'username' => 'operator_distan',
            'nama_lengkap' => 'Operator Data Dinas Pertanian',
            'email' => 'operator.pertanian@minahasaselatan.go.id',
            'role' => 'operator_opd',
            'role_label' => 'Operator OPD (Pertanian)',
            'opd' => 'Dinas Pertanian',
            'kode_opd' => 'DISTAN',
            'is_active' => true,
            'created_at' => '2026-01-20 10:15:00'
        ],
        [
            'id' => 5,
            'username' => 'validator_gis',
            'nama_lengkap' => 'Validator Teknis Geospasial',
            'email' => 'validator.gis@minahasaselatan.go.id',
            'role' => 'validator_gis',
            'role_label' => 'Validator GIS',
            'opd' => 'Tim Teknis GIS Pemkab Minsel',
            'kode_opd' => 'DISKOMINFO',
            'is_active' => true,
            'created_at' => '2026-01-25 11:30:00'
        ],
        [
            'id' => 6,
            'username' => 'approver_bappeda',
            'nama_lengkap' => 'Approver Data Bappelitbangda (Walidata)',
            'email' => 'walidata@minahasaselatan.go.id',
            'role' => 'approver_data',
            'role_label' => 'Approver Data Walidata',
            'opd' => 'Badan Perencanaan, Penelitian dan Pengembangan Daerah',
            'kode_opd' => 'BAPELITBANGDA',
            'is_active' => true,
            'created_at' => '2026-02-01 13:00:00'
        ],
        [
            'id' => 7,
            'username' => 'bupati_minsel',
            'nama_lengkap' => 'Bupati / Pimpinan Daerah Minahasa Selatan',
            'email' => 'bupati@minahasaselatan.go.id',
            'role' => 'pimpinan',
            'role_label' => 'Pimpinan Daerah',
            'opd' => 'Pemerintah Kabupaten Minahasa Selatan',
            'kode_opd' => 'SETDA_PEM',
            'is_active' => true,
            'created_at' => '2026-02-05 14:00:00'
        ],
        [
            'id' => 8,
            'username' => 'publik_minsel',
            'nama_lengkap' => 'Masyarakat Umum / Akademisi Minsel',
            'email' => 'publik@minahasaselatan.go.id',
            'role' => 'publik',
            'role_label' => 'Publik',
            'opd' => 'Masyarakat Umum',
            'kode_opd' => 'PUBLIK',
            'is_active' => true,
            'created_at' => '2026-02-10 15:00:00'
        ]
    ];

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

    protected static function getStorageUsers(): array
    {
        $file = WRITEPATH . 'users_list.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if (is_array($data) && !empty($data)) {
                return $data;
            }
        }
        file_put_contents($file, json_encode(self::$mockUsers, JSON_PRETTY_PRINT));
        return self::$mockUsers;
    }

    protected static function saveStorageUsers(array $users): void
    {
        $file = WRITEPATH . 'users_list.json';
        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
    }

    /**
     * CRUD User: Daftar Pengguna Sistem
     */
    public function getUsers(): ResponseInterface
    {
        $users = self::getStorageUsers();
        return $this->respondSuccess($users, 'Daftar pengguna resmi berhasil diambil.');
    }

    /**
     * CRUD User: Tambah Akun Pengguna Baru
     */
    public function createUser(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $username = strtolower(trim($input['username'] ?? ''));
        $namaLengkap = trim($input['nama_lengkap'] ?? '');
        $email = trim($input['email'] ?? '');
        $role = trim($input['role'] ?? 'operator_opd');
        $opd = trim($input['opd'] ?? 'Dinas Komunikasi dan Informatika');
        $kodeOpd = strtoupper(trim($input['kode_opd'] ?? 'DISKOMINFO'));

        if (empty($username) || empty($namaLengkap)) {
            return $this->respondError('Username dan Nama Lengkap wajib diisi.', 400);
        }

        $users = self::getStorageUsers();

        foreach ($users as $u) {
            if ($u['username'] === $username) {
                return $this->respondError("Username '{$username}' sudah digunakan.", 400);
            }
        }

        $roleLabel = isset($this->officialRoles[$role]) ? $this->officialRoles[$role]['label'] : ucfirst($role);

        $maxId = 0;
        foreach ($users as $u) {
            if ($u['id'] > $maxId) {
                $maxId = $u['id'];
            }
        }
        $newId = $maxId + 1;

        $newUser = [
            'id' => $newId,
            'username' => $username,
            'nama_lengkap' => $namaLengkap,
            'email' => $email ?: ($username . '@minahasaselatan.go.id'),
            'role' => $role,
            'role_label' => $roleLabel,
            'opd' => $opd,
            'kode_opd' => $kodeOpd,
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $users[] = $newUser;
        self::saveStorageUsers($users);

        AuditLogController::record([
            'kategori'      => 'USER',
            'aksi'          => 'CREATE_USER',
            'aktor'         => 'Superadmin Diskominfo',
            'role'          => 'superadmin',
            'opd'           => $opd,
            'objek'         => "{$namaLengkap} ({$username})",
            'keterangan'    => "Pendaftaran akun pengguna baru role {$roleLabel}",
            'versi_terkait' => '-'
        ]);

        return $this->respondSuccess($newUser, "Pengguna '{$namaLengkap}' ({$username}) berhasil didaftarkan.");
    }

    /**
     * CRUD User: Toggle Status Aktif / Nonaktif
     */
    public function toggleUserStatus(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $userId = (int)($input['id'] ?? 0);

        if ($userId === 1) {
            return $this->respondError('Akun Superadmin Utama (#1) tidak boleh dinonaktifkan.', 403);
        }

        $users = self::getStorageUsers();
        foreach ($users as &$u) {
            if ($u['id'] === $userId) {
                $u['is_active'] = !$u['is_active'];
                self::saveStorageUsers($users);
                $statusText = $u['is_active'] ? 'diaktifkan' : 'dinonaktifkan';

                AuditLogController::record([
                    'kategori'      => 'USER',
                    'aksi'          => $u['is_active'] ? 'AKTIFKAN_USER' : 'NONAKTIFKAN_USER',
                    'aktor'         => 'Superadmin Diskominfo',
                    'role'          => 'superadmin',
                    'opd'           => $u['opd'] ?? 'Pemkab Minahasa Selatan',
                    'objek'         => "{$u['nama_lengkap']} ({$u['username']})",
                    'keterangan'    => "Status akun pengguna {$statusText}",
                    'versi_terkait' => '-'
                ]);

                return $this->respondSuccess($u, "Akun {$u['username']} berhasil {$statusText}.");
            }
        }

        return $this->respondError('Pengguna tidak ditemukan.', 404);
    }

    /**
     * CRUD User: Perbarui Data Pengguna (Edit User)
     */
    public function updateUser(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $userId = (int)($input['id'] ?? 0);
        $namaLengkap = trim($input['nama_lengkap'] ?? '');
        $email = trim($input['email'] ?? '');
        $role = trim($input['role'] ?? '');
        $opd = trim($input['opd'] ?? '');
        $kodeOpd = strtoupper(trim($input['kode_opd'] ?? ''));

        if ($userId === 0) {
            return $this->respondError('ID pengguna tidak valid.', 400);
        }

        $users = self::getStorageUsers();
        foreach ($users as &$u) {
            if ($u['id'] === $userId) {
                if (!empty($namaLengkap)) $u['nama_lengkap'] = $namaLengkap;
                if (!empty($email)) $u['email'] = $email;
                if (!empty($role)) {
                    $u['role'] = $role;
                    $u['role_label'] = isset($this->officialRoles[$role]) ? $this->officialRoles[$role]['label'] : ucfirst($role);
                }
                if (!empty($opd)) $u['opd'] = $opd;
                if (!empty($kodeOpd)) $u['kode_opd'] = $kodeOpd;

                self::saveStorageUsers($users);

                AuditLogController::record([
                    'kategori'      => 'USER',
                    'aksi'          => 'UPDATE_USER',
                    'aktor'         => 'Superadmin Diskominfo',
                    'role'          => 'superadmin',
                    'opd'           => $u['opd'] ?? 'Pemkab Minahasa Selatan',
                    'objek'         => "{$u['nama_lengkap']} ({$u['username']})",
                    'keterangan'    => "Pembaruan data profil/role pengguna",
                    'versi_terkait' => '-'
                ]);

                return $this->respondSuccess($u, "Data pengguna '{$u['nama_lengkap']}' berhasil diperbarui.");
            }
        }

        return $this->respondError('Pengguna tidak ditemukan.', 404);
    }

    /**
     * CRUD User: Hapus Akun Pengguna
     */
    public function deleteUser(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $userId = (int)($input['id'] ?? 0);

        if ($userId === 1) {
            return $this->respondError('Akun Superadmin Utama (#1) dilindungi dan tidak dapat dihapus.', 403);
        }

        $users = self::getStorageUsers();
        $targetKey = null;
        foreach ($users as $key => $u) {
            if ($u['id'] === $userId) {
                $targetKey = $key;
                break;
            }
        }

        if ($targetKey === null) {
            return $this->respondError('Pengguna tidak ditemukan.', 404);
        }

        $deletedUser = $users[$targetKey];
        $deletedUsername = $deletedUser['username'];
        unset($users[$targetKey]);
        $users = array_values($users);
        self::saveStorageUsers($users);

        AuditLogController::record([
            'kategori'      => 'USER',
            'aksi'          => 'DELETE_USER',
            'aktor'         => 'Superadmin Diskominfo',
            'role'          => 'superadmin',
            'opd'           => $deletedUser['opd'] ?? 'Pemkab Minahasa Selatan',
            'objek'         => "{$deletedUser['nama_lengkap']} ({$deletedUsername})",
            'keterangan'    => "Penghapusan akun pengguna dari sistem",
            'versi_terkait' => '-'
        ]);

        return $this->respondSuccess(['id' => $userId], "Akun pengguna '{$deletedUsername}' berhasil dihapus.");
    }
}
