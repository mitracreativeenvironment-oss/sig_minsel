<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Konfigurasi Routing GeoMinsel (Full Backend API)
 * Kata Kunci Endpoint Resmi: 'spasials'
 * 
 * @var RouteCollection $routes
 */

// 1. Tangani Preflight OPTIONS untuk semua endpoint (CORS) langsung dengan respons 204
$routes->options('(:any)', static function () {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $frontendUrl = env('app.frontendURL', config('App')->frontendURL ?? 'http://localhost:5173');
    $allowOrigin = !empty($origin) ? $origin : $frontendUrl;

    return service('response')
        ->setStatusCode(204)
        ->setHeader('Access-Control-Allow-Origin', $allowOrigin)
        ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-TOKEN, X-Requested-With, Accept, Origin')
        ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->setHeader('Access-Control-Allow-Credentials', 'true')
        ->setHeader('Access-Control-Max-Age', '86400');
});

// 2. Akses Root & Non-API: Langsung Dialihkan (Redirect) ke Frontend
$routes->get('/', 'Home::index');

// 3. Fallback 404 Non-API: Semua URL selain 'spasials' langsung dialihkan ke Frontend
$routes->set404Override('App\Controllers\Home::redirectFrontend');

// 4. Grup Endpoint Layanan Data Spasial Resmi (Prefix: 'spasials')
// Seluruh endpoint ini dilindungi oleh ApiTokenFilter
$routes->group('spasials', static function ($routes) {
    // Health check & Token Verification / Handshake
    $routes->get('ping', 'Api\AuthController::ping');
    $routes->get('auth/verify', 'Api\AuthController::verify');
    $routes->get('auth/roles', 'Api\AuthController::roles');
    $routes->post('auth/login', 'Api\AuthController::login');
    $routes->get('auth/me', 'Api\AuthController::me');
    $routes->get('auth/profile', 'Api\AuthController::profile');
    $routes->post('auth/profile', 'Api\AuthController::updateProfile');

    // Hierarki Wilayah Administrasi (Kabupaten -> Kecamatan -> Desa)
    $routes->get('wilayah/kabupaten', 'Api\WilayahController::kabupaten');
    $routes->get('wilayah/kecamatan', 'Api\WilayahController::kecamatan');
    $routes->get('wilayah/kecamatan/(:segment)', 'Api\WilayahController::detailKecamatan/$1');
    $routes->get('wilayah/desa', 'Api\WilayahController::desa');
    $routes->get('wilayah/hierarchy', 'Api\WilayahController::hierarchy');
    $routes->get('wilayah/rekap', 'Api\WilayahController::rekap');

    // Layer Tematik & Objek Spasial (Point, Line, Polygon)
    // Mendukung filter: ?kecamatan_id=... & ?desa_id=... & ?bbox=...
    $routes->get('layer/kategori', 'Api\SpasialController::kategori');
    $routes->get('layer/(:segment)', 'Api\SpasialController::getLayer/$1');
    $routes->get('objek/(:num)', 'Api\SpasialController::getObjek/$1');

    // Alur Persetujuan Terpusat (Workflow 12 Status Fase 2, Upload SHP & Dataset CRUD)
    $routes->get('workflow/states', 'Api\WorkflowController::states');
    $routes->get('workflow/datasets', 'Api\WorkflowController::datasets');
    $routes->post('workflow/datasets/create', 'Api\WorkflowController::createDataset');
    $routes->post('workflow/datasets/upload-shp', 'Api\WorkflowController::uploadShpDataset');
    $routes->post('workflow/datasets/reupload-shp', 'Api\WorkflowController::reuploadShpDataset');
    $routes->get('workflow/datasets/download/(:num)', 'Api\WorkflowController::downloadShpDataset/$1');
    $routes->post('workflow/datasets/update', 'Api\WorkflowController::updateDataset');
    $routes->post('workflow/datasets/delete', 'Api\WorkflowController::deleteDataset');
    $routes->post('workflow/transition', 'Api\WorkflowController::transition');

    // Modul Superadmin & Admin GIS: Data Dasar Geospasial (Upload, Validasi & Hapus SHP)
    $routes->get('admin/data-dasar', 'Api\WorkflowController::dataDasar');
    $routes->post('admin/data-dasar/upload', 'Api\WorkflowController::uploadDataDasar');
    $routes->post('admin/data-dasar/activate', 'Api\WorkflowController::activateDataDasar');
    $routes->post('admin/data-dasar/delete', 'Api\WorkflowController::deleteDataDasar');

    // Modul Manajemen Pengguna & Hak Akses (Superadmin & Admin OPD)
    $routes->get('admin/users', 'Api\AuthController::getUsers');
    $routes->post('admin/users/create', 'Api\AuthController::createUser');
    $routes->post('admin/users/update', 'Api\AuthController::updateUser');
    $routes->post('admin/users/toggle', 'Api\AuthController::toggleUserStatus');
    $routes->post('admin/users/delete', 'Api\AuthController::deleteUser');

    // Modul Master Data OPD (Superadmin, Admin GIS & Admin OPD)
    $routes->get('admin/opd', 'Api\MasterDataController::getOpds');
    $routes->post('admin/opd/create', 'Api\MasterDataController::createOpd');
    $routes->post('admin/opd/update', 'Api\MasterDataController::updateOpd');
    $routes->post('admin/opd/delete', 'Api\MasterDataController::deleteOpd');

    // Modul Master Kategori Spasial Tematik (Superadmin & Admin GIS)
    $routes->get('admin/kategori', 'Api\MasterDataController::getKategori');
    $routes->post('admin/kategori/create', 'Api\MasterDataController::createKategori');
    $routes->post('admin/kategori/update', 'Api\MasterDataController::updateKategori');
    $routes->post('admin/kategori/delete', 'Api\MasterDataController::deleteKategori');

    // Modul Audit Log & Jejak Aktivitas Sistem (Superadmin, Admin GIS, Approver Data)
    $routes->get('admin/logs', 'Api\AuditLogController::getLogs');
    $routes->post('admin/logs/create', 'Api\AuditLogController::createLog');

    // Modul Analitik & Statistik Pengunjung Geoportal Terintegrasi
    $routes->get('analytics/summary', 'Api\VisitorAnalyticsController::getSummary');
    $routes->post('analytics/track', 'Api\VisitorAnalyticsController::track');

    // Modul CRUD Wilayah: Kecamatan, Kelurahan & Desa (Superadmin & Admin)
    $routes->get('admin/wilayah/kecamatan', 'Api\WilayahController::getAdminKecamatan');
    $routes->post('admin/wilayah/kecamatan/create', 'Api\WilayahController::createKecamatan');
    $routes->post('admin/wilayah/kecamatan/update', 'Api\WilayahController::updateKecamatan');
    $routes->post('admin/wilayah/kecamatan/delete', 'Api\WilayahController::deleteKecamatan');
    $routes->get('admin/wilayah/desa', 'Api\WilayahController::getAdminDesa');
    $routes->post('admin/wilayah/desa/create', 'Api\WilayahController::createDesa');
    $routes->post('admin/wilayah/desa/update', 'Api\WilayahController::updateDesa');
    $routes->post('admin/wilayah/desa/delete', 'Api\WilayahController::deleteDesa');

    // Modul CRUD Base Map (Peta Dasar) Superadmin & Geoportal
    $routes->get('basemaps', 'Api\BaseMapController::getPublicBasemaps');
    $routes->get('admin/basemaps', 'Api\BaseMapController::getAdminBasemaps');
    $routes->post('admin/basemaps/create', 'Api\BaseMapController::createBasemap');
    $routes->post('admin/basemaps/update', 'Api\BaseMapController::updateBasemap');
    $routes->post('admin/basemaps/toggle', 'Api\BaseMapController::toggleBasemap');
    $routes->post('admin/basemaps/set-default', 'Api\BaseMapController::setDefault');
    $routes->post('admin/basemaps/delete', 'Api\BaseMapController::deleteBasemap');
});
