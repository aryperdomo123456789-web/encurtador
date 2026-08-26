<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\ShortLink;
use App\Models\User;
use App\Support\Shlink\ShlinkClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_campaign_context_and_filtered_visits(): void
    {
        $user = User::factory()->create();
        $link = $this->linkFor($user);
        $this->app->instance(ShlinkClient::class, $this->fakeAnalyticsClient());

        $this->actingAs($user)
            ->get(route('analytics.show', [
                'shortCode' => $link->shlink_short_code,
                'startDate' => '2026-08-01',
                'endDate' => '2026-08-26',
            ]))
            ->assertOk()
            ->assertSee('Oferta de verão', false)
            ->assertSee('12', false)
            ->assertSee('Brasil', false)
            ->assertSee('instagram', false)
            ->assertDontSee('Payload bruto', false);
    }

    public function test_unavailable_analytics_renders_a_safe_recovery_message(): void
    {
        $user = User::factory()->create();
        $link = $this->linkFor($user);
        $this->app->instance(ShlinkClient::class, $this->fakeAnalyticsClient(true));

        $this->actingAs($user)
            ->get(route('analytics.show', ['shortCode' => $link->shlink_short_code]))
            ->assertOk()
            ->assertSee('As métricas ainda não estão disponíveis', false)
            ->assertSee('O link continua ativo', false);
    }

    public function test_owner_can_export_filtered_analytics_as_csv(): void
    {
        $user = User::factory()->create();
        $link = $this->linkFor($user);
        $this->app->instance(ShlinkClient::class, $this->fakeAnalyticsClient());

        $response = $this->actingAs($user)->get(route('analytics.export', [
            'shortCode' => $link->shlink_short_code,
            'startDate' => '2026-08-01',
            'endDate' => '2026-08-26',
        ]));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename=melink-promo-verao-analytics.csv');
        $this->assertStringContainsString('data,tipo,pais', $response->streamedContent());
        $this->assertStringContainsString('Brasil', $response->streamedContent());
    }

    public function test_user_cannot_view_another_users_analytics(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $link = $this->linkFor($owner);

        $this->actingAs($other)
            ->get(route('analytics.show', ['shortCode' => $link->shlink_short_code]))
            ->assertNotFound();
    }

    private function fakeAnalyticsClient(bool $throws = false): ShlinkClient
    {
        return new ShlinkClient(
            'https://api-shlink.vr766.com',
            'test-key',
            3,
            20,
            static function (string $method, string $url) use ($throws): array {
                if ($throws) {
                    throw new RuntimeException('upstream unavailable');
                }

                return [
                    'status' => 200,
                    'headers' => ['content-type' => 'application/json'],
                    'body' => json_encode([
                        'visits' => [[
                            'dateVisited' => '2026-08-26T12:00:00+00:00',
                            'visitLocation' => [
                                'countryCode' => 'BR',
                                'countryName' => 'Brasil',
                                'cityName' => 'São Paulo',
                            ],
                            'deviceType' => 'desktop',
                            'referer' => 'https://instagram.com',
                        ]],
                        'pagination' => [
                            'totalItems' => 12,
                            'currentPage' => 1,
                            'itemsPerPage' => 25,
                        ],
                    ]),
                ];
            }
        );
    }

    private function linkFor(User $user): ShortLink
    {
        $plan = Plan::firstOrCreate(['code' => 'premium'], [
            'name' => 'Premium',
            'description' => 'Plano de teste',
            'is_free' => false,
            'monthly_short_url_limit' => null,
            'allow_custom_slug' => true,
            'allow_custom_domain' => true,
            'allow_custom_expiration' => true,
            'allow_lifetime_links' => true,
            'is_active' => true,
        ]);

        return ShortLink::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'shlink_short_url' => 'https://me.vr766.com/promo-verao',
            'shlink_short_code' => 'promo-verao',
            'domain' => 'me.vr766.com',
            'long_url' => 'https://example.com/oferta?utm_source=instagram&utm_campaign=verao-2026',
            'custom_slug' => 'promo-verao',
            'generated_slug' => 'promo-verao',
            'is_custom_slug' => true,
            'is_free_link' => false,
            'status' => 'active',
            'created_via' => 'test',
            'shlink_payload' => [
                'title' => 'Oferta de verão',
                'tags' => ['instagram', 'verão'],
            ],
            'shlink_response' => [],
        ]);
    }
}
