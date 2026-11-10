<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter Cross-Origin Resource Sharing (CORS)
 * 
 * Mengizinkan frontend (Vue 3) mengakses API backend dengan header keamanan resmi.
 */
class CorsFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Tangani preflight OPTIONS request langsung
        if ($request->getMethod() === 'options') {
            $response = service('response');
            $this->addCorsHeaders($response);
            $response->setStatusCode(204);
            return $response;
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $this->addCorsHeaders($response);
    }

    private function addCorsHeaders(ResponseInterface $response): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        $frontendUrl = env('app.frontendURL', config('App')->frontendURL ?? 'http://localhost:5173');

        // Allow configured frontend origin or wildcard
        $response->setHeader('Access-Control-Allow-Origin', $origin === $frontendUrl ? $frontendUrl : '*');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-TOKEN, X-Requested-With, Accept, Origin');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
        $response->setHeader('Access-Control-Max-Age', '86400');
    }
}

