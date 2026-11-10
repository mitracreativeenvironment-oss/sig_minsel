<?php

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Controller Verifikasi & Handshake API Token Frontend
 */
class AuthController extends BaseApiController
{
    /**
     * Health check endpoint
     */
    public function ping(): ResponseInterface
    {
        return $this->respondSuccess([
            'system'    => 'SIG Minahasa Selatan REST API',
            'version'   => '0.1.0',
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
            'scope'         => ['wilayah:read', 'spasial:read'],
            'client'        => 'Official Frontend SIG Minsel'
        ], 'Token resmi frontend valid.');
    }
}

