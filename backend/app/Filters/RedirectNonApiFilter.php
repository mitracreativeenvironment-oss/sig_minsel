<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Filter Pengalihan Akses Non-Spasials ke Frontend
 * 
 * Backend CI4 murni beroperasi sebagai Layanan Data Spasial.
 * Kata kunci resmi adalah 'spasials'.
 * Jika pengguna/browser mengakses backend secara langsung (root, index.php, dll)
 * tanpa rute 'spasials', maka langsung dialihkan ke URL frontend resmi.
 */
class RedirectNonApiFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $uri = $request->getUri();
        $path = trim($uri->getPath(), '/');

        $prefix = env('app.apiPrefix', config('App')->apiPrefix ?? 'spasials');

        // Kecualikan rute data spasials
        if (str_starts_with($path, $prefix) || $path === $prefix) {
            return;
        }

        // Ambil URL frontend dari konfigurasi atau .env
        $frontendUrl = env('app.frontendURL', config('App')->frontendURL ?? 'http://localhost:5173');

        // Langsung redirect ke frontend
        return redirect()->to($frontendUrl);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Tidak diperlukan aksi setelah request
    }
}
