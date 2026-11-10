<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class ApiSecurityTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testRootRedirectsToFrontend()
    {
        $result = $this->get('/');
        $result->assertRedirect('http://localhost:5173');
    }

    public function testSpasialsWithoutTokenIsUnauthorized()
    {
        $result = $this->get('spasials/ping');
        $result->assertStatus(401);
        $result->assertJSONExact([
            'status'  => 401,
            'error'   => 'Unauthorized',
            'message' => 'Akses API ditolak: Header token resmi (X-API-TOKEN atau Authorization Bearer) tidak disertakan.',
            'hint'    => 'Akses backend ini hanya diperkenankan melalui frontend resmi SIG Minahasa Selatan.'
        ]);
    }

    public function testSpasialsHierarchyWithoutTokenIsUnauthorized()
    {
        $result = $this->get('spasials/wilayah/hierarchy');
        $result->assertStatus(401);
    }

    public function testSpasialsWithInvalidTokenIsForbidden()
    {
        $result = $this->withHeaders([
            'X-API-TOKEN' => 'invalid_random_token_123'
        ])->get('spasials/ping');

        $result->assertStatus(403);
    }

    public function testSpasialsWithValidTokenIsSuccessful()
    {
        $result = $this->withHeaders([
            'X-API-TOKEN' => 'sig_minsel_sec_2026_m1n53l_k4b'
        ])->get('spasials/ping');

        $result->assertStatus(200);
        $result->assertJSONFragment([
            'status'  => 200,
            'success' => true,
            'message' => 'API backend aktif dan beroperasi normal.',
        ]);
    }
}
