<?php

declare(strict_types=1);

namespace Tests\Feature\Links;

use App\Models\CustomerDomain;
use App\Models\Plan;
use App\Models\ShortLink;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Shlink\ShlinkClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreatePremiumLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        FakePremiumShlinkPayload::$last = [];
        $this->app->instance(ShlinkClient::class, $this->fakeShlinkClient());
    }

    public function test_premium_user_creates_link_with_custom_slug(): void
    {
        $user = $this->premiumUser();

        $response = $this->actingAs($user)->post(route('links.premium.store'), [
            'long_url' => 'https://example.com/campanha',
            'custom_slug' => 'promo-verao',
        ]);

        $response
            ->assertRedirect(route('links.index'))
            ->assertSessionHas('short_url', 'https://sho.rt/promo-verao');

        $this->assertSame('promo-verao', FakePremiumShlinkPayload::$last['customSlug'] ?? null);
        $this->assertArrayNotHasKey('validUntil', FakePremiumShlinkPayload::$last);
    }

    public function test_premium_user_can_create_campaign_with_domain_tags_and_utm(): void
    {
        $user = $this->premiumUser();
        $domain = CustomerDomain::create([
            'user_id' => $user->id,
            'domain' => 'links.example.com',
            'status' => 'active',
            'dns_target' => 'me.vr766.com',
            'tls_mode' => 'auto',
            'tls_status' => 'active',
        ]);

        $this->actingAs($user)->post(route('links.premium.store'), [
            'long_url' => 'https://example.com/oferta?ref=original',
            'custom_slug' => 'oferta-verao',
            'domain' => $domain->domain,
            'title' => 'Oferta de verão',
            'tags' => 'Instagram, verão, Instagram, produto',
            'utm_source' => 'instagram',
            'utm_medium' => 'paid-social',
            'utm_campaign' => 'verao-2026',
            'utm_content' => 'criativo-a',
            'forward_query' => '1',
        ])->assertRedirect(route('links.index'));

        $this->assertSame('https://example.com/oferta?ref=original&utm_source=instagram&utm_medium=paid-social&utm_campaign=verao-2026&utm_content=criativo-a', FakePremiumShlinkPayload::$last['longUrl'] ?? null);
        $this->assertSame('links.example.com', FakePremiumShlinkPayload::$last['domain'] ?? null);
        $this->assertSame('Oferta de verão', FakePremiumShlinkPayload::$last['title'] ?? null);
        $this->assertSame(['instagram', 'verão', 'produto'], FakePremiumShlinkPayload::$last['tags'] ?? null);
        $this->assertTrue((bool) (FakePremiumShlinkPayload::$last['forwardQuery'] ?? false));
        $this->assertSame($domain->id, ShortLink::query()->where('shlink_short_code', 'oferta-verao')->value('customer_domain_id'));
    }

    public function test_premium_user_can_set_valid_until_within_one_year(): void
    {
        $user = $this->premiumUser();
        $validUntil = now()->addMonths(6)->format('Y-m-d\TH:i');

        $this->actingAs($user)->post(route('links.premium.store'), [
            'long_url' => 'https://example.com/campanha',
            'custom_slug' => 'promo-longa',
            'valid_until' => $validUntil,
        ])->assertRedirect(route('links.index'));

        $this->assertArrayHasKey('validUntil', FakePremiumShlinkPayload::$last);
    }

    public function test_slug_with_invalid_pattern_is_rejected(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->from(route('links.premium'))
            ->post(route('links.premium.store'), [
                'long_url' => 'https://example.com',
                'custom_slug' => '-bad-',
            ])
            ->assertRedirect(route('links.premium'))
            ->assertSessionHasErrors('custom_slug');
    }

    public function test_valid_until_beyond_one_year_is_rejected(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->from(route('links.premium'))
            ->post(route('links.premium.store'), [
                'long_url' => 'https://example.com',
                'custom_slug' => 'valida-ate',
                'valid_until' => now()->addYears(2)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('links.premium'))
            ->assertSessionHasErrors('valid_until');
    }

    public function test_free_user_cannot_access_premium_endpoints(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('links.premium'))->assertForbidden();

        $this->actingAs($user)->post(route('links.premium.store'), [
            'long_url' => 'https://example.com',
            'custom_slug' => 'nao-pode',
        ])->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('links.premium'))->assertRedirect(route('login'));
        $this->post(route('links.premium.store'), [
            'long_url' => 'https://example.com',
            'custom_slug' => 'sem-auth',
        ])->assertRedirect(route('login'));
    }

    public function test_owner_can_access_premium_endpoints_without_subscription(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->get(route('links.premium'))
            ->assertOk()
            ->assertSee('Fluxo premium', false);

        $this->actingAs($owner)
            ->post(route('links.premium.store'), [
                'long_url' => 'https://example.com/campanha',
                'custom_slug' => 'dono-top',
            ])
            ->assertRedirect(route('links.index'));

        $this->assertSame('dono-top', FakePremiumShlinkPayload::$last['customSlug'] ?? null);
    }

    private function premiumUser(): User
    {
        $user = User::factory()->create();

        $plan = Plan::query()->updateOrCreate(['code' => 'pro'], [
            'code' => 'pro',
            'name' => 'Pro',
            'description' => 'Plano premium para testes',
            'is_free' => false,
            'monthly_short_url_limit' => 0,
            'allow_custom_slug' => true,
            'allow_custom_domain' => true,
            'allow_custom_expiration' => true,
            'allow_lifetime_links' => true,
            'is_active' => true,
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'test',
            'provider_customer_id' => 'cus_test',
            'provider_subscription_id' => 'sub_test',
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'cancel_at_period_end' => false,
            'metadata' => [],
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
                FakePremiumShlinkPayload::$last = $body === null ? [] : (array) json_decode($body, true);

                $slug = FakePremiumShlinkPayload::$last['customSlug'] ?? 'auto';

                return [
                    'status' => 200,
                    'headers' => ['content-type' => 'application/json'],
                    'body' => json_encode([
                        'shortCode' => $slug,
                        'shortUrl' => 'https://sho.rt/'.$slug,
                        'longUrl' => FakePremiumShlinkPayload::$last['longUrl'] ?? null,
                        'dateCreated' => '2026-07-22T00:00:00+00:00',
                    ]),
                ];
            }
        );
    }
}

final class FakePremiumShlinkPayload
{
    /** @var array<string,mixed> */
    public static array $last = [];
}
