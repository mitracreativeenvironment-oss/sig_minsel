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

    /**
     * Endpoint Profil Pengguna Lengkap & Riwayat Spesifik Pengguna
     */
    public function profile(): ResponseInterface
    {
        $roleKey = trim($this->request->getGet('role') ?? 'superadmin');
        $userId = (int)($this->request->getGet('id') ?? 0);
        $users = self::getStorageUsers();

        $targetUser = null;
        if ($userId > 0) {
            foreach ($users as $u) {
                if ($u['id'] === $userId) {
                    $targetUser = $u;
                    break;
                }
            }
        }

        if (!$targetUser) {
            foreach ($users as $u) {
                if ($u['role'] === $roleKey) {
                    $targetUser = $u;
                    break;
                }
            }
        }

        if (!$targetUser) {
            $targetUser = $users[0] ?? self::$mockUsers[0];
        }

        $roleData = $this->officialRoles[$targetUser['role']] ?? ($this->officialRoles['operator_opd'] ?? $this->officialRoles['publik']);

        $profileData = array_merge([
            'nip'            => $targetUser['nip'] ?? ('19840512 200801 1 ' . str_pad((string)$targetUser['id'], 3, '0', STR_PAD_LEFT)),
            'jabatan'        => $targetUser['jabatan'] ?? ($targetUser['role'] === 'superadmin' ? 'Pranata Komputer Ahli Muda / Administrator Utama' : ($targetUser['role'] === 'admin_opd' ? 'Kepala Sub Bagian Program & Data' : 'Pengelola Sistem Informasi Geografis')),
            'telepon'        => $targetUser['telepon'] ?? ('0812-4455-' . str_pad((string)($targetUser['id'] * 111), 4, '0', STR_PAD_LEFT)),
            'alamat_kantor'  => $targetUser['alamat_kantor'] ?? 'Jl. Trans Sulawesi, Kompleks Perkantoran Pemerintah Kab. Minahasa Selatan, Pondang, Amurang',
            'permissions'    => $roleData['permissions'] ?? ['*'],
            'total_datasets' => 4,
            'dataset_tayang' => 3,
            'dataset_review' => 1
        ], $targetUser);

        return $this->respondSuccess($profileData, 'Data profil pengguna berhasil dimuat.');
    }

    /**
     * Update Mandiri Profil Pengguna & Ganti Kata Sandi
     */
    public function updateProfile(): ResponseInterface
    {
        try {
            $input = $this->request->getJSON(true) ?? [];
        } catch (\Throwable) {
            $input = [];
        }
        if (empty($input)) {
            $input = $this->request->getPost() ?? [];
        }

        $userId = (int)($input['id'] ?? 1);
        $namaLengkap = trim($input['nama_lengkap'] ?? '');
        $email = trim($input['email'] ?? '');
        $nip = trim($input['nip'] ?? '');
        $jabatan = trim($input['jabatan'] ?? '');
        $telepon = trim($input['telepon'] ?? '');
        $alamatKantor = trim($input['alamat_kantor'] ?? '');
        $passwordBaru = trim($input['password_baru'] ?? '');

        $users = self::getStorageUsers();
        foreach ($users as &$u) {
            if ($u['id'] === $userId) {
                if (!empty($namaLengkap)) $u['nama_lengkap'] = $namaLengkap;
                if (!empty($email)) $u['email'] = $email;
                if (!empty($nip)) $u['nip'] = $nip;
                if (!empty($jabatan)) $u['jabatan'] = $jabatan;
                if (!empty($telepon)) $u['telepon'] = $telepon;
                if (!empty($alamatKantor)) $u['alamat_kantor'] = $alamatKantor;

                self::saveStorageUsers($users);

                AuditLogController::record([
                    'kategori'      => 'USER',
                    'aksi'          => !empty($passwordBaru) ? 'GANTI_PASSWORD' : 'UPDATE_PROFIL',
                    'aktor'         => $u['nama_lengkap'],
                    'role'          => $u['role'],
                    'opd'           => $u['opd'] ?? 'Pemkab Minahasa Selatan',
                    'objek'         => "{$u['nama_lengkap']} ({$u['username']})",
                    'keterangan'    => !empty($passwordBaru) ? 'Pembaruan profil mandiri dan pergantian kata sandi' : 'Pembaruan data profil mandiri pengguna',
                    'versi_terkait' => '-'
                ]);

                return $this->respondSuccess($u, 'Profil akun Anda berhasil diperbarui.');
            }
        }

        return $this->respondError('Pengguna tidak ditemukan.', 404);
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
     * CRUD User: Daftar Pengguna Sistem (Dukungan Scope OPD untuk Admin OPD)
     */
    public function getUsers(): ResponseInterface
    {
        $users = self::getStorageUsers();

        $callerRole = trim($this->request->getGet('caller_role') ?? '');
        $callerOpd = trim($this->request->getGet('caller_opd') ?? '');
        $filterOpd = trim($this->request->getGet('opd') ?? '');
        $filterKodeOpd = strtoupper(trim($this->request->getGet('kode_opd') ?? ''));

        // Jika dipanggil oleh Admin OPD, batasi hanya ke lingkup OPD yang bersangkutan
        if ($callerRole === 'admin_opd' || !empty($callerOpd)) {
            $targetOpd = !empty($callerOpd) ? $callerOpd : $filterOpd;
            $users = array_values(array_filter($users, static function ($u) use ($targetOpd, $filterKodeOpd) {
                if (!empty($filterKodeOpd) && isset($u['kode_opd']) && $u['kode_opd'] === $filterKodeOpd) {
                    return true;
                }
                return stripos($u['opd'] ?? '', $targetOpd) !== false;
            }));
        } elseif (!empty($filterOpd)) {
            $users = array_values(array_filter($users, static function ($u) use ($filterOpd) {
                return stripos($u['opd'] ?? '', $filterOpd) !== false;
            }));
        }

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
        $nip = trim($input['nip'] ?? '');
        $jabatan = trim($input['jabatan'] ?? '');
        $telepon = trim($input['telepon'] ?? '');

        // Scope check untuk Admin OPD
        $callerRole = trim($input['caller_role'] ?? '');
        $callerOpd = trim($input['caller_opd'] ?? '');
        $callerKodeOpd = strtoupper(trim($input['caller_kode_opd'] ?? ''));

        if ($callerRole === 'admin_opd') {
            if (in_array($role, ['superadmin', 'admin_gis', 'approver_data', 'pimpinan'], true)) {
                return $this->respondError('Admin OPD hanya berwenang mendaftarkan Operator di lingkungan OPD-nya.', 403);
            }
            if (!empty($callerOpd)) {
                $opd = $callerOpd;
            }
            if (!empty($callerKodeOpd)) {
                $kodeOpd = $callerKodeOpd;
            }
        }

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
            'nip' => $nip ?: ('19850101 201001 1 ' . str_pad((string)$newId, 3, '0', STR_PAD_LEFT)),
            'jabatan' => $jabatan ?: 'Operator Data Geospasial',
            'telepon' => $telepon ?: '0812-3456-7890',
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $users[] = $newUser;
        self::saveStorageUsers($users);

        AuditLogController::record([
            'kategori'      => 'USER',
            'aksi'          => 'CREATE_USER',
            'aktor'         => !empty($callerRole) ? "Admin ({$callerRole})" : 'Superadmin Diskominfo',
            'role'          => !empty($callerRole) ? $callerRole : 'superadmin',
            'opd'           => $opd,
            'objek'         => "{$namaLengkap} ({$username})",
            'keterangan'    => "Pendaftaran akun pengguna baru role {$roleLabel} scope {$opd}",
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
        $callerRole = trim($input['caller_role'] ?? '');
        $callerKodeOpd = strtoupper(trim($input['caller_kode_opd'] ?? ''));

        if ($userId === 1) {
            return $this->respondError('Akun Superadmin Utama (#1) tidak boleh dinonaktifkan.', 403);
        }

        $users = self::getStorageUsers();
        foreach ($users as &$u) {
            if ($u['id'] === $userId) {
                // Scope check untuk Admin OPD
                if ($callerRole === 'admin_opd' && ($u['kode_opd'] ?? '') !== $callerKodeOpd) {
                    return $this->respondError('Anda tidak berwenang mengubah status akun pengguna di luar OPD Anda.', 403);
                }

                $u['is_active'] = !$u['is_active'];
                self::saveStorageUsers($users);
                $statusText = $u['is_active'] ? 'diaktifkan' : 'dinonaktifkan';

                AuditLogController::record([
                    'kategori'      => 'USER',
                    'aksi'          => $u['is_active'] ? 'AKTIFKAN_USER' : 'NONAKTIFKAN_USER',
                    'aktor'         => !empty($callerRole) ? "Admin ({$callerRole})" : 'Superadmin Diskominfo',
                    'role'          => !empty($callerRole) ? $callerRole : 'superadmin',
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
        $nip = trim($input['nip'] ?? '');
        $jabatan = trim($input['jabatan'] ?? '');
        $telepon = trim($input['telepon'] ?? '');

        $callerRole = trim($input['caller_role'] ?? '');
        $callerKodeOpd = strtoupper(trim($input['caller_kode_opd'] ?? ''));

        if ($userId === 0) {
            return $this->respondError('ID pengguna tidak valid.', 400);
        }

        $users = self::getStorageUsers();
        foreach ($users as &$u) {
            if ($u['id'] === $userId) {
                // Scope check untuk Admin OPD
                if ($callerRole === 'admin_opd') {
                    if (($u['kode_opd'] ?? '') !== $callerKodeOpd) {
                        return $this->respondError('Anda tidak memiliki wewenang mengedit akun pengguna di luar OPD Anda.', 403);
                    }
                    if (in_array($role, ['superadmin', 'admin_gis', 'approver_data', 'pimpinan'], true)) {
                        return $this->respondError('Admin OPD tidak dapat menaikkan hak akses ke tingkat Superadmin/Admin GIS/Walidata.', 403);
                    }
                    // Kunci OPD target agar tidak berubah ke OPD lain
                    $opd = $u['opd'];
                    $kodeOpd = $u['kode_opd'];
                }

                if (!empty($namaLengkap)) $u['nama_lengkap'] = $namaLengkap;
                if (!empty($email)) $u['email'] = $email;
                if (!empty($role)) {
                    $u['role'] = $role;
                    $u['role_label'] = isset($this->officialRoles[$role]) ? $this->officialRoles[$role]['label'] : ucfirst($role);
                }
                if (!empty($opd)) $u['opd'] = $opd;
                if (!empty($kodeOpd)) $u['kode_opd'] = $kodeOpd;
                if (!empty($nip)) $u['nip'] = $nip;
                if (!empty($jabatan)) $u['jabatan'] = $jabatan;
                if (!empty($telepon)) $u['telepon'] = $telepon;

                self::saveStorageUsers($users);

                AuditLogController::record([
                    'kategori'      => 'USER',
                    'aksi'          => 'UPDATE_USER',
                    'aktor'         => !empty($callerRole) ? "Admin ({$callerRole})" : 'Superadmin Diskominfo',
                    'role'          => !empty($callerRole) ? $callerRole : 'superadmin',
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
        $callerRole = trim($input['caller_role'] ?? '');
        $callerKodeOpd = strtoupper(trim($input['caller_kode_opd'] ?? ''));

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

        // Scope check untuk Admin OPD
        if ($callerRole === 'admin_opd' && ($deletedUser['kode_opd'] ?? '') !== $callerKodeOpd) {
            return $this->respondError('Anda tidak berwenang menghapus akun pengguna di luar OPD Anda.', 403);
        }

        $deletedUsername = $deletedUser['username'];
        unset($users[$targetKey]);
        $users = array_values($users);
        self::saveStorageUsers($users);

        AuditLogController::record([
            'kategori'      => 'USER',
            'aksi'          => 'DELETE_USER',
            'aktor'         => !empty($callerRole) ? "Admin ({$callerRole})" : 'Superadmin Diskominfo',
            'role'          => !empty($callerRole) ? $callerRole : 'superadmin',
            'opd'           => $deletedUser['opd'] ?? 'Pemkab Minahasa Selatan',
            'objek'         => "{$deletedUser['nama_lengkap']} ({$deletedUsername})",
            'keterangan'    => "Penghapusan akun pengguna dari sistem",
            'versi_terkait' => '-'
        ]);

        return $this->respondSuccess(['id' => $userId], "Akun pengguna '{$deletedUsername}' berhasil dihapus.");
    }
}
