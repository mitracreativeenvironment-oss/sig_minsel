<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Filter Keamanan Token Resmi API Frontend
 * 
 * Memastikan setiap request ke endpoint /api wajib menyertakan token resmi
 * dari aplikasi frontend (melalui header X-API-TOKEN atau Authorization: Bearer <token>).
 * Request tanpa token resmi akan ditolak dengan respons 401 Unauthorized.
 */
class ApiTokenFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Berikan izin lewat untuk browser CORS preflight (OPTIONS method)
        if ($request->getMethod() === 'options') {
            return;
        }

        $validToken = env('app.apiToken', config('App')->apiToken ?? 'sig_minsel_sec_2026_m1n53l_k4b');

        // 1. Periksa header X-API-TOKEN
        $token = $request->getHeaderLine('X-API-TOKEN');

        // 2. Jika tidak ada, periksa header Authorization: Bearer <token>
        if (empty($token)) {
            $authHeader = $request->getHeaderLine('Authorization');
            if (preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        // 3. Fallback: query parameter ?api_token=... (berguna untuk tile/vector layer jika diperlukan)
        if (empty($token)) {
            $token = $request->getGet('api_token');
        }

        // 4. Validasi Token
        if (empty($token)) {
            return Services::response()
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setContentType('application/json')
                ->setJSON([
                    'status'  => 401,
                    'error'   => 'Unauthorized',
                    'message' => 'Akses API ditolak: Header token resmi (X-API-TOKEN atau Authorization Bearer) tidak disertakan.',
                    'hint'    => 'Akses backend ini hanya diperkenankan melalui frontend resmi SIG Minahasa Selatan.'
                ]);
        }

        // Cek kecocokan token menggunakan hash_equals untuk keamanan timing attack
        if (!hash_equals($validToken, $token)) {
            return Services::response()
                ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                ->setContentType('application/json')
                ->setJSON([
                    'status'  => 403,
                    'error'   => 'Forbidden',
                    'message' => 'Akses API ditolak: Token API resmi dari frontend tidak valid atau kedaluwarsa.',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Pastikan response API selalu bertipe JSON
        if (!$response->hasHeader('Content-Type')) {
            $response->setContentType('application/json');
        }
    }
}
