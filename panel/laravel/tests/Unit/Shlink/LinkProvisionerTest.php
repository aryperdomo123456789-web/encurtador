<?php

declare(strict_types=1);

namespace Tests\Unit\Shlink;

use App\Contracts\FreeLinkQuotaRepository;
use App\Support\Shlink\LinkProvisioner;
use App\Support\Shlink\ShlinkClient;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LinkProvisionerTest extends TestCase
{
    public function test_free_link_returns_short_url_from_client(): void
    {
        [$provisioner] = $this->buildProvisioner();

        $response = $provisioner->createFreeLink(1, 'https://example.com');

        $this->assertSame('https://sho.rt/abc123', $response['shortUrl']);
    }

    public function test_free_link_sets_valid_until_seven_days_ahead(): void
    {
        $captured = [];
        [$provisioner] = $this->buildProvisioner(captured: $captured);

        $provisioner->createFreeLink(1, 'https://example.com');

        $this->assertArrayHasKey('validUntil', $captured['payload']);
        $validUntil = new DateTimeImmutable($captured['payload']['validUntil']);
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $diffDays = ($validUntil->getTimestamp() - $now->getTimestamp()) / 86400;
        $this->assertGreaterThanOrEqual(6.9, $diffDays, 'validUntil must be ~7 days ahead');
        $this->assertLessThanOrEqual(7.1, $diffDays, 'validUntil must be ~7 days ahead');
    }

    public function test_free_link_records_creation_on_repository(): void
    {
        $quota = new class implements FreeLinkQuotaRepository
        {
            /** @var array<int, array<string, mixed>> */
            public array $records = [];

            public function countFreeLinksForPeriod(int $userId, DateTimeInterface $from, DateTimeInterface $to): int
            {
                return 0;
            }

            public function reserveFreeLinkCreation(int $userId, int $monthlyLimit): string
            {
                return 'test-reservation';
            }

            public function releaseFreeLinkCreation(int $userId, string $reservationId): void {}

            public function recordFreeLinkCreation(int $userId, array $record): void
            {
                $this->records[] = ['userId' => $userId] + $record;
            }
        };

        [$provisioner] = $this->buildProvisioner(quota: $quota);
        $provisioner->createFreeLink(7, 'https://example.com');

        $this->assertCount(1, $quota->records);
        $this->assertSame(7, $quota->records[0]['userId']);
        $this->assertSame('abc123', $quota->records[0]['shortCode']);
        $this->assertSame('https://sho.rt/abc123', $quota->records[0]['shortUrl']);
        $this->assertSame('https://example.com', $quota->records[0]['longUrl']);
    }

    public function test_free_link_rejects_when_monthly_quota_reached(): void
    {
        $quota = new class implements FreeLinkQuotaRepository
        {
            public function countFreeLinksForPeriod(int $userId, DateTimeInterface $from, DateTimeInterface $to): int
            {
                return 5;
            }

            public function reserveFreeLinkCreation(int $userId, int $monthlyLimit): string
            {
                throw new DomainException('Monthly free-link limit reached');
            }

            public function releaseFreeLinkCreation(int $userId, string $reservationId): void {}

            public function recordFreeLinkCreation(int $userId, array $record): void {}
        };

        [$provisioner] = $this->buildProvisioner(quota: $quota);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Monthly free-link limit reached');
        $provisioner->createFreeLink(1, 'https://example.com');
    }

    public function test_free_link_rejects_custom_slug(): void
    {
        [$provisioner] = $this->buildProvisioner();

        $this->expectException(InvalidArgumentException::class);
        $provisioner->createFreeLink(1, 'https://example.com', ['customSlug' => 'meu-slug']);
    }

    public function test_free_link_rejects_non_default_domain(): void
    {
        [$provisioner] = $this->buildProvisioner();

        $this->expectException(InvalidArgumentException::class);
        $provisioner->createFreeLink(1, 'https://example.com', ['domain' => 'client.example.com']);
    }

    /**
     * @param  array<string, mixed>  $captured
     * @return array{0: LinkProvisioner}
     */
    private function buildProvisioner(?FreeLinkQuotaRepository $quota = null, array &$captured = []): array
    {
        $quota ??= new class implements FreeLinkQuotaRepository
        {
            public function countFreeLinksForPeriod(int $userId, DateTimeInterface $from, DateTimeInterface $to): int
            {
                return 0;
            }

            public function reserveFreeLinkCreation(int $userId, int $monthlyLimit): string
            {
                return 'test-reservation';
            }

            public function releaseFreeLinkCreation(int $userId, string $reservationId): void {}

            public function recordFreeLinkCreation(int $userId, array $record): void {}
        };

        $client = new ShlinkClient(
            'https://api-shlink.vr766.com',
            'test-key',
            3,
            20,
            function (string $method, string $url, array $headers, ?string $body, int $timeout) use (&$captured): array {
                $captured['method'] = $method;
                $captured['url'] = $url;
                $captured['payload'] = $body === null ? [] : (array) json_decode($body, true);

                return [
                    'status' => 200,
                    'headers' => ['content-type' => 'application/json'],
                    'body' => json_encode([
                        'shortCode' => 'abc123',
                        'shortUrl' => 'https://sho.rt/abc123',
                        'dateCreated' => '2026-07-01T00:00:00+00:00',
                    ]),
                ];
            }
        );

        return [new LinkProvisioner($client, $quota)];
    }
}
