<?php

declare(strict_types=1);

namespace Tests\Feature\Links;

use App\Contracts\FreeLinkQuotaRepository;
use App\Models\User;
use App\Support\Shlink\ShlinkClient;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateFreeLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(ShlinkClient::class, $this->fakeShlinkClient());
        $this->app->instance(FreeLinkQuotaRepository::class, new InMemoryFreeLinkQuotaRepository);
    }

    public function test_authenticated_user_creates_free_link_via_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('links.store'), [
            'long_url' => 'https://example.com/artigo',
        ]);

        $response
            ->assertRedirect(route('links.index'))
            ->assertSessionHas('status', fn ($v) => is_string($v) && str_contains($v, 'https://sho.rt/abc123'))
            ->assertSessionHas('short_url', 'https://sho.rt/abc123');

        /** @var InMemoryFreeLinkQuotaRepository $quota */
        $quota = $this->app->make(FreeLinkQuotaRepository::class);
        $this->assertCount(1, $quota->records);
        $this->assertSame($user->id, $quota->records[0]['userId']);
    }

    public function test_extra_fields_are_ignored_by_free_flow(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('links.store'), [
            'long_url' => 'https://example.com/artigo',
            'premium' => '1',
            'custom_slug' => 'meu-slug',
            'domain' => 'client.example.com',
            'valid_until' => '2099-01-01',
        ])->assertRedirect(route('links.index'));

        /** @var InMemoryFreeLinkQuotaRepository $quota */
        $quota = $this->app->make(FreeLinkQuotaRepository::class);
        $this->assertCount(1, $quota->records, 'free link deve ser registrado uma vez');
        // O ShlinkClient fake não recebeu customSlug/domain (payload inspecionável abaixo).
        $this->assertArrayNotHasKey('customSlug', FakeShlinkPayload::$last);
        $this->assertArrayNotHasKey('domain', FakeShlinkPayload::$last);
        $this->assertArrayHasKey('validUntil', FakeShlinkPayload::$last);
    }

    public function test_invalid_url_returns_validation_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('links.create'))
            ->post(route('links.store'), ['long_url' => 'nope'])
            ->assertRedirect(route('links.create'))
            ->assertSessionHasErrors('long_url');
    }

    public function test_monthly_quota_exceeded_shows_error(): void
    {
        $user = User::factory()->create();

        $quota = new InMemoryFreeLinkQuotaRepository;
        $quota->forcedCount = 5;
        $this->app->instance(FreeLinkQuotaRepository::class, $quota);

        $this->actingAs($user)
            ->from(route('links.create'))
            ->post(route('links.store'), ['long_url' => 'https://example.com'])
            ->assertRedirect(route('links.create'))
            ->assertSessionHasErrors('long_url');
    }

    public function test_guest_cannot_create_link(): void
    {
        $this->post(route('links.store'), ['long_url' => 'https://example.com'])
            ->assertRedirect(route('login'));
    }

    private function fakeShlinkClient(): ShlinkClient
    {
        FakeShlinkPayload::$last = [];

        return new ShlinkClient(
            'https://api-shlink.vr766.com',
            'test-key',
            3,
            20,
            function (string $method, string $url, array $headers, ?string $body, int $timeout): array {
                FakeShlinkPayload::$last = $body === null ? [] : (array) json_decode($body, true);

                return [
                    'status' => 200,
                    'headers' => ['content-type' => 'application/json'],
                    'body' => json_encode([
                        'shortCode' => 'abc123',
                        'shortUrl' => 'https://sho.rt/abc123',
                        'longUrl' => FakeShlinkPayload::$last['longUrl'] ?? null,
                        'dateCreated' => '2026-07-22T00:00:00+00:00',
                    ]),
                ];
            }
        );
    }
}

final class FakeShlinkPayload
{
    /** @var array<string,mixed> */
    public static array $last = [];
}

final class InMemoryFreeLinkQuotaRepository implements FreeLinkQuotaRepository
{
    public int $forcedCount = 0;

    /** @var array<int, array<string, mixed>> */
    public array $records = [];

    public function countFreeLinksForPeriod(int $userId, DateTimeInterface $from, DateTimeInterface $to): int
    {
        return $this->forcedCount;
    }

    public function reserveFreeLinkCreation(int $userId, int $monthlyLimit): string
    {
        if ($this->forcedCount >= $monthlyLimit) {
            throw new \DomainException('Monthly free-link limit reached');
        }

        return 'test-reservation';
    }

    public function releaseFreeLinkCreation(int $userId, string $reservationId): void {}

    public function recordFreeLinkCreation(int $userId, array $record): void
    {
        $this->records[] = ['userId' => $userId] + $record;
    }
}
