<?php

namespace Tests\Feature\Health;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_liveness_endpoint_responde_ok(): void
    {
        $response = $this->get('/healthz');

        $response->assertOk()
            ->assertJson(['status' => 'ok', 'service' => 'panel']);
    }

    public function test_readiness_ok_quando_db_e_shlink_respondem(): void
    {
        config()->set('services.shlink.base_url', 'https://shlink.test');
        Http::fake([
            'shlink.test/rest/health' => Http::response(['status' => 'pass'], 200),
        ]);

        $response = $this->get('/health/ready');

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.shlink.status', 'ok');
    }

    public function test_readiness_degradado_quando_shlink_falha(): void
    {
        config()->set('services.shlink.base_url', 'https://shlink.test');
        Http::fake([
            'shlink.test/rest/health' => Http::response(['status' => 'fail'], 500),
        ]);

        $response = $this->get('/health/ready');

        $response->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.shlink.status', 'fail');
    }

    public function test_response_traz_x_request_id_no_header(): void
    {
        $response = $this->get('/healthz');

        $this->assertNotEmpty($response->headers->get('X-Request-Id'));
    }

    public function test_respeita_request_id_enviado_pelo_proxy(): void
    {
        $id = 'req-abc-123';
        $response = $this->withHeaders(['X-Request-Id' => $id])->get('/healthz');

        $this->assertSame($id, $response->headers->get('X-Request-Id'));
    }
}
