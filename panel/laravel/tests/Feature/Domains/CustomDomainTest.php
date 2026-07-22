<?php

declare(strict_types=1);

namespace Tests\Feature\Domains;

use App\Models\CustomerDomain;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Domains\DomainDnsResolver;
use App\Support\Shlink\ShlinkClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomDomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FakeDomainResolver::$targets = [];
        FakeDomainShlink::$lastPath  = null;
        FakeDomainShlink::$lastBody  = null;

        config()->set('panel.custom_domain_dns_target', 'me.vr766.com');
        config()->set('panel.host', 'panel.test');
        config()->set('shlink.default_domain', 'me.vr766.com');

        $this->app->instance(DomainDnsResolver::class, new FakeDomainResolver());
        $this->app->instance(ShlinkClient::class, $this->fakeShlinkClient());
    }

    public function test_free_user_cannot_access_domain_endpoints(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('domains.index'))->assertForbidden();
        $this->actingAs($user)
            ->post(route('domains.store'), ['domain' => 'links.cliente.com'])
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('domains.index'))->assertRedirect(route('login'));
    }

    public function test_premium_user_can_register_domain_and_receives_dns_instructions(): void
    {
        $user = $this->domainCapableUser();

        $this->actingAs($user)
            ->post(route('domains.store'), ['domain' => 'links.cliente.com'])
            ->assertRedirect(route('domains.index'));

        $this->assertDatabaseHas('customer_domains', [
            'user_id'    => $user->id,
            'domain'     => 'links.cliente.com',
            'status'     => 'pending_dns',
            'dns_target' => 'me.vr766.com',
        ]);
        // Nada foi enviado ao Shlink antes da verificação.
        $this->assertNull(FakeDomainShlink::$lastPath);
    }

    public function test_invalid_domain_is_rejected(): void
    {
        $user = $this->domainCapableUser();

        $this->actingAs($user)
            ->from(route('domains.index'))
            ->post(route('domains.store'), ['domain' => 'nao-eh-fqdn'])
            ->assertRedirect(route('domains.index'))
            ->assertSessionHasErrors('domain');
    }

    public function test_reserved_hosts_cannot_be_registered(): void
    {
        $user = $this->domainCapableUser();

        $this->actingAs($user)
            ->from(route('domains.index'))
            ->post(route('domains.store'), ['domain' => 'me.vr766.com'])
            ->assertRedirect(route('domains.index'))
            ->assertSessionHasErrors('domain');
    }

    public function test_another_user_cannot_claim_existing_domain(): void
    {
        $owner = $this->domainCapableUser();
        $other = $this->domainCapableUser();

        CustomerDomain::create([
            'user_id'    => $owner->id,
            'domain'     => 'links.cliente.com',
            'status'     => 'pending_dns',
            'dns_target' => 'me.vr766.com',
            'is_primary' => false,
        ]);

        $this->actingAs($other)
            ->from(route('domains.index'))
            ->post(route('domains.store'), ['domain' => 'links.cliente.com'])
            ->assertRedirect(route('domains.index'))
            ->assertSessionHasErrors('domain');
    }

    public function test_verify_fails_when_dns_does_not_match(): void
    {
        $user = $this->domainCapableUser();
        $domain = CustomerDomain::create([
            'user_id'    => $user->id,
            'domain'     => 'links.cliente.com',
            'status'     => 'pending_dns',
            'dns_target' => 'me.vr766.com',
            'is_primary' => false,
        ]);

        FakeDomainResolver::$targets = ['outro.host.com'];

        $this->actingAs($user)
            ->from(route('domains.index'))
            ->post(route('domains.verify', $domain))
            ->assertRedirect(route('domains.index'))
            ->assertSessionHasErrors('domain');

        $this->assertNull(FakeDomainShlink::$lastPath);
    }

    public function test_verify_succeeds_and_registers_domain_in_shlink(): void
    {
        $user = $this->domainCapableUser();
        $domain = CustomerDomain::create([
            'user_id'    => $user->id,
            'domain'     => 'links.cliente.com',
            'status'     => 'pending_dns',
            'dns_target' => 'me.vr766.com',
            'is_primary' => false,
        ]);

        FakeDomainResolver::$targets = ['me.vr766.com'];

        $this->actingAs($user)
            ->post(route('domains.verify', $domain))
            ->assertRedirect(route('domains.index'));

        $fresh = $domain->fresh();
        $this->assertSame('active', $fresh->status);
        $this->assertNotNull($fresh->dns_verified_at);
        $this->assertNotNull($fresh->shlink_domain_registered_at);

        $this->assertNotNull(FakeDomainShlink::$lastPath);
        $this->assertSame('links.cliente.com', FakeDomainShlink::$lastBody['domain'] ?? null);
    }

    public function test_owner_can_destroy_and_stranger_cannot(): void
    {
        $owner = $this->domainCapableUser();
        $other = $this->domainCapableUser();
        $domain = CustomerDomain::create([
            'user_id'    => $owner->id,
            'domain'     => 'links.cliente.com',
            'status'     => 'active',
            'dns_target' => 'me.vr766.com',
            'is_primary' => false,
        ]);

        $this->actingAs($other)
            ->delete(route('domains.destroy', $domain))
            ->assertForbidden();

        $this->actingAs($owner)
            ->delete(route('domains.destroy', $domain))
            ->assertRedirect(route('domains.index'));

        $this->assertDatabaseMissing('customer_domains', ['id' => $domain->id]);
    }

    private function domainCapableUser(): User
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

    private function fakeShlinkClient(): ShlinkClient
    {
        return new ShlinkClient(
            'https://api-shlink.vr766.com',
            'test-key',
            3,
            20,
            function (string $method, string $url, array $headers, ?string $body, int $timeout): array {
                if (str_contains($url, '/rest/v3/domains')) {
                    if ($method === 'GET') {
                        return [
                            'status'  => 200,
                            'headers' => ['content-type' => 'application/json'],
                            'body'    => json_encode(['domains' => ['data' => []]]),
                        ];
                    }

                    FakeDomainShlink::$lastPath = $url;
                    FakeDomainShlink::$lastBody = $body === null ? [] : (array) json_decode($body, true);

                    return [
                        'status'  => 201,
                        'headers' => ['content-type' => 'application/json'],
                        'body'    => json_encode([
                            'domain' => FakeDomainShlink::$lastBody['domain'] ?? null,
                            'isDefault' => false,
                        ]),
                    ];
                }

                return [
                    'status'  => 404,
                    'headers' => ['content-type' => 'application/json'],
                    'body'    => json_encode(['error' => 'unexpected route in test']),
                ];
            }
        );
    }
}

final class FakeDomainResolver implements DomainDnsResolver
{
    /** @var array<int,string> */
    public static array $targets = [];

    public function resolveTargets(string $domain): array
    {
        return self::$targets;
    }
}

final class FakeDomainShlink
{
    public static ?string $lastPath = null;
    /** @var array<string,mixed>|null */
    public static ?array $lastBody = null;
}
