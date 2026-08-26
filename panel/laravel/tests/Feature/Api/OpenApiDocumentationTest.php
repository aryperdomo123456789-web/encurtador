<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\TestCase;

final class OpenApiDocumentationTest extends TestCase
{
    public function test_documentacao_openapi_v1_e_publica_e_descreve_a_api(): void
    {
        $response = $this->getJson(route('api.v1.openapi'));

        $response->assertOk()
            ->assertJsonPath('openapi', '3.1.0')
            ->assertJsonPath('info.title', 'MElink API')
            ->assertJsonStructure([
                'openapi',
                'info' => ['title', 'version'],
                'servers',
                'components' => ['securitySchemes' => ['bearerAuth']],
                'paths' => [
                    '/api/v1/openapi.json' => ['get'],
                    '/api/v1/me' => ['get'],
                    '/api/v1/links' => ['get', 'post'],
                    '/api/v1/links/{link}' => ['get', 'patch', 'delete'],
                    '/api/v1/links/{link}/analytics' => ['get'],
                    '/api/v1/events' => ['post'],
                ],
            ]);

        $document = $response->json();
        $this->assertSame([], $document['paths']['/api/v1/openapi.json']['get']['security']);
        $this->assertStringNotContainsString('sk_live_', $response->getContent());
        $this->assertStringNotContainsString('whsec_', $response->getContent());
        $this->assertStringNotContainsString('APP_KEY', $response->getContent());
    }
}
