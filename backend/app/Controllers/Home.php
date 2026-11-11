<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

/**
 * Controller Non-API: Pengalihan Penuh ke Frontend
 * 
 * Sesuai arsitektur, Backend CI4 murni Full API.
 * Setiap akses langsung melalui web browser ke backend
 * dialihkan (redirect) langsung ke antarmuka aplikasi Frontend resmi.
 */
class Home extends BaseController
{
    /**
     * Redirect root ke URL Frontend
     */
    public function index(): RedirectResponse
    {
        return $this->redirectFrontend();
    }

    /**
     * Redirect fallback / 404 non-API ke Frontend
     */
    public function redirectFrontend(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (strtoupper($this->request->getMethod()) === 'OPTIONS') {
            return $this->response->setStatusCode(204);
        }

        $frontendUrl = env('app.frontendURL', config('App')->frontendURL ?? 'http://localhost:5173');
        return redirect()->to($frontendUrl);
    }
}
