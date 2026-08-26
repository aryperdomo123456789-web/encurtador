<?php

namespace Tests\Feature\Health;

use App\Models\CustomerDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_liveness_endpoint_responde_ok(): void
    {
        $response = $this->get('/healthz');

        $response->assertOk()
            ->assertJson(['status' => 'ok', 'service' => 'panel'])
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_readiness_ok_quando_db_e_shlink_respondem(): void
    {
        config()->set('shlink.base_url', 'https://shlink.test');
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
        config()->set('shlink.base_url', 'https://shlink.test');
        Http::fake([
            'shlink.test/rest/health' => Http::response(['status' => 'fail'], 500),
        ]);

        $response = $this->get('/health/ready');

        $response->assertStatus(503)
            ->assertJsonPath('status', 'degraded')
            ->assertJsonPath('checks.shlink.status', 'fail');

        $this->assertArrayNotHasKey('error', $response->json('checks.shlink'));
    }

    public function test_health_nao_emite_cookie_de_sessao(): void
    {
        $response = $this->get('/healthz');

        $this->assertCount(0, $response->headers->getCookies());
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

    public function test_tls_allow_endpoint_rejects_unknown_domain(): void
    {
        $response = $this->get('/tls/allow?domain=unknown.example');

        $response->assertForbidden();
    }

    public function test_tls_allow_endpoint_accepts_registered_customer_domain(): void
    {
        $user = User::factory()->create();
        CustomerDomain::query()->create([
            'user_id' => $user->id,
            'domain' => 'links.cliente.com',
            'status' => 'active',
            'dns_target' => 'me.vr766.com',
            'dns_verified_at' => now(),
            'tls_mode' => 'on_demand',
            'tls_status' => 'pending',
        ]);

        $response = $this->get('/tls/allow?domain=links.cliente.com');

        $response->assertNoContent();
    }
}
