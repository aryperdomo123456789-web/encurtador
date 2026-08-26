<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PublicRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_slug_is_proxied_to_shlink(): void
    {
        config()->set('shlink.base_url', 'https://api-shlink.test');

        Http::fake([
            'https://api-shlink.test/abc123' => Http::response('', 302, [
                'Location' => 'https://example.com/destino',
            ]),
        ]);

        $this->get('/abc123')
            ->assertStatus(302)
            ->assertHeader('Location', 'https://example.com/destino');

        Http::assertSent(function ($request): bool {
            return $request->method() === 'GET'
                && (string) $request->url() === 'https://api-shlink.test/abc123';
        });
    }

    public function test_guest_login_route_is_not_captured_by_public_fallback(): void
    {
        $this->get(route('login'))
            ->assertOk();
    }
}
