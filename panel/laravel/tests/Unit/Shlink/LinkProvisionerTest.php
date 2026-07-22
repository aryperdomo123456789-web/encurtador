<?php

declare(strict_types=1);

namespace Tests\Unit\Shlink;

use App\Contracts\FreeLinkQuotaRepository;
use App\Support\Shlink\DomainService;
use App\Support\Shlink\LinkProvisioner;
use App\Support\Shlink\ShlinkClient;
use PHPUnit\Framework\TestCase;

final class LinkProvisionerTest extends TestCase
{
    public function test_free_link_requires_default_domain_and_sets_expiration(): void
    {
        $client = $this->makeClient();
        $quota = new class implements FreeLinkQuotaRepository {
            public function countFreeLinksForPeriod(int $userId, \DateTimeInterface $from, \DateTimeInterface $to): int
            {
                return 0;
            }

            public function recordFreeLinkCreation(int $userId, array $record): void
            {
            }
        };

        $provisioner = new LinkProvisioner($client, $quota);

        $response = $provisioner->createFreeLink(1, 'https://example.com');

        $this->assertSame('https://sho.rt/abc123', $response['shortUrl']);
    }

    private function makeClient(): ShlinkClient
    {
        return new ShlinkClient(
            'https://api-shlink.vr766.com',
            'test-key',
            3,
            20,
            function (string $method, string $url, array $headers, ?string $body, int $timeout): array {
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
    }
}
