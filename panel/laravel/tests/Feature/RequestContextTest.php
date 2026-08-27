<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class RequestContextTest extends TestCase
{
    public function test_preserva_request_id_seguro_no_healthcheck(): void
    {
        $this->withHeader('X-Request-Id', 'synthetic-check-123')
            ->getJson(route('health.live'))
            ->assertOk()
            ->assertHeader('X-Request-Id', 'synthetic-check-123')
            ->assertHeaderMissing('Set-Cookie');
    }

    public function test_substitui_request_id_com_caracteres_invalidos(): void
    {
        $response = $this->withHeader('X-Request-Id', 'invalid request id')
            ->getJson(route('health.live'));

        $response->assertOk();
        $this->assertNotSame('invalid request id', $response->headers->get('X-Request-Id'));
        $this->assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/i',
            (string) $response->headers->get('X-Request-Id')
        );
    }
}
