<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class PublicRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_slug_is_proxied_to_shlink(): void
    {
        config()->set('shlink.base_url', 'https://api-shlink.test');
        config()->set('shlink.redirect_base_url', 'https://api-shlink.test');

        Http::fake([
            'https://api-shlink.test/abc123' => Http::response('', 302, [
                'Location' => 'https://example.com/destino',
                'X-Request-Id' => 'upstream-id',
                'Connection' => 'keep-alive',
            ]),
        ]);

        $this->get('/abc123')
            ->assertStatus(302)
            ->assertHeader('Location', 'https://example.com/destino')
            ->assertHeader('X-Request-Id')
            ->assertHeaderMissing('Connection');

        Http::assertSent(function ($request): bool {
            return $request->method() === 'GET'
                && (string) $request->url() === 'https://api-shlink.test/abc123'
                && $request->hasHeader('X-Request-Id');
        });
    }

    public function test_upstream_not_found_is_returned_without_recursion(): void
    {
        config()->set('shlink.base_url', 'https://api-shlink.test');
        config()->set('shlink.redirect_base_url', 'https://api-shlink.test');

        Http::fake([
            'https://api-shlink.test/missing' => Http::response('not found', 404, [
                'Content-Type' => 'text/plain',
            ]),
        ]);

        $this->get('/missing')
            ->assertNotFound()
            ->assertSee('not found', false);

        Http::assertSentCount(1);
    }

    public function test_timeout_is_converted_to_controlled_bad_gateway(): void
    {
        config()->set('shlink.base_url', 'https://api-shlink.test');
        config()->set('shlink.redirect_base_url', 'https://api-shlink.test');
        config()->set('shlink.redirect_connect_timeout', 0.2);
        config()->set('shlink.redirect_timeout', 0.5);

        Http::fake(static function (): never {
            throw new RuntimeException('simulated upstream timeout');
        });

        $this->get('/slow-link')
            ->assertStatus(502)
            ->assertHeader('X-Request-Id');
    }

    public function test_scanner_like_paths_are_rejected_without_calling_shlink(): void
    {
        config()->set('shlink.base_url', 'https://api-shlink.test');
        config()->set('shlink.redirect_base_url', 'https://api-shlink.test');
        Http::fake();

        $this->get('/wp-includes/wlwmanifest.xml')
            ->assertNotFound()
            ->assertHeader('Cache-Control', 'max-age=60, public');

        $this->get('/.env')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_paths_longer_than_configured_limit_are_rejected(): void
    {
        config()->set('shlink.redirect_max_path_length', 8);
        Http::fake();

        $this->get('/123456789')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_head_request_keeps_status_and_headers_without_body(): void
    {
        config()->set('shlink.base_url', 'https://api-shlink.test');
        config()->set('shlink.redirect_base_url', 'https://api-shlink.test');

        Http::fake([
            'https://api-shlink.test/head-link' => Http::response('body should not be returned', 302, [
                'Location' => 'https://example.com/head-destination',
            ]),
        ]);

        $this->head('/head-link')
            ->assertStatus(302)
            ->assertHeader('Location', 'https://example.com/head-destination')
            ->assertSee('', false);
    }

    public function test_guest_login_route_is_not_captured_by_public_fallback(): void
    {
        $this->get(route('login'))
            ->assertOk();
    }
}
