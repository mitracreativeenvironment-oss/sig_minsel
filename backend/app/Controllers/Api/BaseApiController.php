<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Base Controller untuk Seluruh Endpoint REST API SIG Minahasa Selatan
 */
abstract class BaseApiController extends ResourceController
{
    protected $format = 'json';

    /**
     * Format response standar GeoJSON FeatureCollection
     *
     * @param array $features Array of GeoJSON Feature
     * @param array $meta Metadata tambahan di level FeatureCollection
     * @return ResponseInterface
     */
    protected function respondGeoJson(array $features, array $meta = []): ResponseInterface
    {
        $payload = array_merge([
            'type'     => 'FeatureCollection',
            'features' => $features,
        ], $meta);

        return $this->response
            ->setStatusCode(200)
            ->setContentType('application/geo+json')
            ->setJSON($payload);
    }

    /**
     * Format response JSON standar
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return ResponseInterface
     */
    protected function respondSuccess($data = null, string $message = 'Sukses', int $code = 200): ResponseInterface
    {
        return $this->respond([
            'status'  => $code,
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Format response JSON error
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return ResponseInterface
     */
    protected function respondFailError(string $message, int $code = 400, $errors = null): ResponseInterface
    {
        $response = [
            'status'  => $code,
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $this->respond($response, $code);
    }

    /**
     * Alias method untuk respondFailError
     */
    protected function respondError(string $message, int $code = 400, $errors = null): ResponseInterface
    {
        return $this->respondFailError($message, $code, $errors);
    }
}

