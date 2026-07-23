<?php

declare(strict_types=1);

namespace Tests\Feature\Domains;

use App\Models\CustomerDomain;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class TlsProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_probe_marks_domain_active_on_https_success(): void
    {
        $user = $this->premiumUser();
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
        $user = $this->premiumUser();
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
        $user = $this->premiumUser();
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

    private function premiumUser(): User
    {
        $user = User::factory()->create();

        $plan = Plan::create([
            'code'                    => 'pro-' . uniqid('', true),
            'name'                    => 'Pro',
            'description'             => 'Plano premium para testes',
            'is_free'                 => false,
            'monthly_short_url_limit' => 0,
            'allow_custom_slug'       => true,
            'allow_custom_domain'     => true,
            'allow_custom_expiration' => true,
            'allow_lifetime_links'    => true,
            'is_active'               => true,
        ]);

        Subscription::create([
            'user_id'                  => $user->id,
            'plan_id'                  => $plan->id,
            'provider'                 => 'test',
            'provider_customer_id'     => 'cus_' . uniqid('', true),
            'provider_subscription_id' => 'sub_' . uniqid('', true),
            'status'                   => 'active',
            'current_period_start'     => now(),
            'current_period_end'       => now()->addMonth(),
            'cancel_at_period_end'     => false,
            'metadata'                 => [],
        ]);

        return $user->fresh();
    }
}
