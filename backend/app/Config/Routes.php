<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Konfigurasi Routing GeoMinsel (Full Backend API)
 * Kata Kunci Endpoint Resmi: 'spasials'
 * 
 * @var RouteCollection $routes
 */

// 1. Akses Root & Non-API: Langsung Dialihkan (Redirect) ke Frontend
$routes->get('/', 'Home::index');

// 2. Fallback 404 Non-API: Semua URL selain 'spasials' langsung dialihkan ke Frontend
$routes->set404Override('App\Controllers\Home::redirectFrontend');

// 3. Grup Endpoint Layanan Data Spasial Resmi (Prefix: 'spasials')
// Seluruh endpoint ini dilindungi oleh ApiTokenFilter
$routes->group('spasials', static function ($routes) {
    // Health check & Token Verification / Handshake
    $routes->get('ping', 'Api\AuthController::ping');
    $routes->get('auth/verify', 'Api\AuthController::verify');

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
});
