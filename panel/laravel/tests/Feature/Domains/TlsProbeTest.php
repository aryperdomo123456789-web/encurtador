<?php

declare(strict_types=1);

namespace Tests\Feature\Domains;

use App\Models\CustomerDomain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class TlsProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_probe_marks_domain_active_on_https_success(): void
    {
        $user = User::factory()->create(['plan' => 'premium']);
        $domain = CustomerDomain::create([
            'user_id'    => $user->id,
            'domain'     => 'links.cliente.com',
            'status'     => 'active',
            'dns_target' => 'me.vr766.com',
            'tls_status' => 'pending',
        ]);

        Http::fake([
            'https://links.cliente.com/*' => Http::response('ok', 200),
        ]);

        $this->actingAs($user)
            ->post(route('domains.tls', $domain))
            ->assertRedirect(route('domains.index'));

        $this->assertSame('active', $domain->fresh()->tls_status);
    }

    public function test_probe_keeps_pending_on_ssl_error(): void
    {
        $user = User::factory()->create(['plan' => 'premium']);
        $domain = CustomerDomain::create([
            'user_id'    => $user->id,
            'domain'     => 'links.cliente.com',
            'status'     => 'active',
            'dns_target' => 'me.vr766.com',
            'tls_status' => 'pending',
        ]);

        Http::fake(function () {
            throw new \RuntimeException('cURL error 60: SSL certificate problem');
        });

        $this->actingAs($user)->post(route('domains.tls', $domain));

        $this->assertSame('pending', $domain->fresh()->tls_status);
        $this->assertStringContainsString('SSL', (string) $domain->fresh()->tls_last_error);
    }

    public function test_tls_endpoint_rejects_domain_not_active(): void
    {
        $user = User::factory()->create(['plan' => 'premium']);
        $domain = CustomerDomain::create([
            'user_id'    => $user->id,
            'domain'     => 'links.cliente.com',
            'status'     => 'pending_dns',
            'dns_target' => 'me.vr766.com',
            'tls_status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('domains.tls', $domain))
            ->assertSessionHasErrors('domain');
    }
}
