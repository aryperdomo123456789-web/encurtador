<?php

declare(strict_types=1);

namespace Tests\Feature\Health;

use Tests\TestCase;

final class ReleaseHealthTest extends TestCase
{
    public function test_endpoint_de_release_e_publico_e_nao_expoe_configuracao(): void
    {
        $response = $this->getJson(route('health.release'));

        $response->assertOk()
            ->assertJsonStructure(['service', 'release', 'built_at']);

        $this->assertSame('panel', $response->json('service'));
        $this->assertStringNotContainsString('APP_KEY', $response->getContent());
        $this->assertStringNotContainsString('DB_', $response->getContent());
        $this->assertStringNotContainsString('sk_'.'live_', $response->getContent());
        $this->assertStringNotContainsString('wh'.'sec_', $response->getContent());
    }
}
