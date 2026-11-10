<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Konfigurasi Routing WebGIS Minahasa Selatan (Full API Backend)
 * 
 * @var RouteCollection $routes
 */

// 1. Akses Root Non-API: Langsung Dialihkan (Redirect) ke Frontend
$routes->get('/', 'Home::index');

// 2. Fallback 404 Non-API: Semua URL yang bukan API langsung dialihkan ke Frontend
$routes->set404Override('App\Controllers\Home::redirectFrontend');

// 3. Grup Endpoint REST API (Dilindungi ApiTokenFilter)
$routes->group('api/v1', static function ($routes) {
    // Health check & Token Verification / Handshake
    $routes->get('ping', 'Api\AuthController::ping');
    $routes->get('auth/verify', 'Api\AuthController::verify');

    // Wilayah Spasial (Batas Administrasi Minahasa Selatan)
    $routes->get('wilayah/kecamatan', 'Api\WilayahController::kecamatan');
    $routes->get('wilayah/kecamatan/(:segment)', 'Api\WilayahController::detailKecamatan/$1');

    // Layer Tematik & Objek Spasial (Point, Line, Polygon)
    $routes->get('spasial/kategori', 'Api\SpasialController::kategori');
    $routes->get('spasial/layer/(:segment)', 'Api\SpasialController::getLayer/$1');
    $routes->get('spasial/objek/(:num)', 'Api\SpasialController::getObjek/$1');
});
