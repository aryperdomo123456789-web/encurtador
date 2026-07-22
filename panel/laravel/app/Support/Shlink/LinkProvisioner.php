<?php

declare(strict_types=1);

namespace App\Support\Shlink;

use App\Contracts\FreeLinkQuotaRepository;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use DomainException;
use InvalidArgumentException;

final class LinkProvisioner
{
    public function __construct(
        private readonly ShlinkClient $client,
        private readonly FreeLinkQuotaRepository $quotaRepository,
        private readonly int $freeMonthlyLimit = 5,
        private readonly ?DomainService $domainService = null
    ) {
    }

    /**
     * @param array{
     *     premium?: bool,
     *     customSlug?: string|null,
     *     domain?: string|null,
     *     title?: string|null,
     *     tags?: array<int,string>|null,
     *     crawlable?: bool|null,
     *     forwardQuery?: bool|null,
     *     validSince?: DateTimeInterface|string|null,
     *     validUntil?: DateTimeInterface|string|null,
     *     maxVisits?: int|null,
     *     findIfExists?: bool|null,
     *     pathPrefix?: string|null,
     *     shortCodeLength?: int|null
     * } $options
     * @return array<string,mixed>
     */
    public function provision(int $userId, string $longUrl, array $options = []): array
    {
        $premium = (bool) ($options['premium'] ?? false);
        $customSlug = $this->normalizeNullableString($options['customSlug'] ?? null);
        $domain = $this->normalizeDomain($options['domain'] ?? null);

        if (!$premium && $domain !== null) {
            throw new InvalidArgumentException('Free links must use the default domain.');
        }

        if ($domain !== null && $this->domainService instanceof DomainService) {
            $this->domainService->ensureRegistered($domain);
        }

        $payload = [
            'longUrl' => $longUrl,
        ];

        if ($domain !== null) {
            $payload['domain'] = $domain;
        }

        if (isset($options['title']) && $options['title'] !== null) {
            $payload['title'] = (string) $options['title'];
        }

        if (isset($options['tags']) && is_array($options['tags']) && $options['tags'] !== []) {
            $payload['tags'] = array_values(array_map(static fn ($tag) => trim((string) $tag), $options['tags']));
        }

        if (array_key_exists('crawlable', $options) && $options['crawlable'] !== null) {
            $payload['crawlable'] = (bool) $options['crawlable'];
        }

        if (array_key_exists('forwardQuery', $options) && $options['forwardQuery'] !== null) {
            $payload['forwardQuery'] = (bool) $options['forwardQuery'];
        }

        if (array_key_exists('validSince', $options) && $options['validSince'] !== null) {
            $payload['validSince'] = $this->formatDateTime($options['validSince']);
        }

        if (array_key_exists('maxVisits', $options) && $options['maxVisits'] !== null) {
            $payload['maxVisits'] = (int) $options['maxVisits'];
        }

        if (array_key_exists('findIfExists', $options) && $options['findIfExists'] !== null) {
            $payload['findIfExists'] = (bool) $options['findIfExists'];
        }

        if (array_key_exists('pathPrefix', $options) && $options['pathPrefix'] !== null) {
            $payload['pathPrefix'] = trim((string) $options['pathPrefix']);
        }

        if (array_key_exists('shortCodeLength', $options) && $options['shortCodeLength'] !== null) {
            $payload['shortCodeLength'] = (int) $options['shortCodeLength'];
        }

        if ($premium) {
            if ($customSlug === null) {
                throw new InvalidArgumentException('Premium links require customSlug in this integration layer.');
            }

            $payload['customSlug'] = $customSlug;

            if (array_key_exists('validUntil', $options) && $options['validUntil'] !== null) {
                $payload['validUntil'] = $this->formatDateTime($options['validUntil']);
            }
        } else {
            if ($customSlug !== null) {
                throw new InvalidArgumentException('Free links must not define customSlug.');
            }

            $this->assertMonthlyFreeQuotaAvailable($userId);
            $payload['validUntil'] = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->add(new DateInterval('P7D'))->format(DATE_ATOM);
        }

        $response = $this->client->createShortUrl($payload);

        if (!$premium) {
            $this->quotaRepository->recordFreeLinkCreation($userId, [
                'shortCode' => $response['shortCode'] ?? null,
                'shortUrl' => $response['shortUrl'] ?? null,
                'longUrl' => $longUrl,
                'domain' => $domain,
                'createdAt' => $response['dateCreated'] ?? (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
            ]);
        }

        return $response;
    }

    public function createFreeLink(int $userId, string $longUrl, array $options = []): array
    {
        $options['premium'] = false;

        return $this->provision($userId, $longUrl, $options);
    }

    public function createPremiumLink(int $userId, string $longUrl, string $customSlug, array $options = []): array
    {
        $options['premium'] = true;
        $options['customSlug'] = $customSlug;

        return $this->provision($userId, $longUrl, $options);
    }

    private function assertMonthlyFreeQuotaAvailable(int $userId): void
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $monthStart = $now->modify('first day of this month')->setTime(0, 0, 0);
        $nextMonthStart = $monthStart->modify('first day of next month');
        $createdThisMonth = $this->quotaRepository->countFreeLinksForPeriod($userId, $monthStart, $nextMonthStart);

        if ($createdThisMonth >= $this->freeMonthlyLimit) {
            throw new DomainException(sprintf(
                'Monthly free-link limit reached for user %d. Limit: %d links per month.',
                $userId,
                $this->freeMonthlyLimit
            ));
        }
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeDomain(mixed $value): ?string
    {
        $domain = $this->normalizeNullableString($value);

        if ($domain === null) {
            return null;
        }

        if ($this->domainService instanceof DomainService) {
            return $this->domainService->normalizeForCreation($domain);
        }

        return strtolower($domain);
    }

    /**
     * @param DateTimeInterface|string $value
     */
    private function formatDateTime(DateTimeInterface|string $value): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        $dateTime = new DateTimeImmutable((string) $value);

        return $dateTime->format(DATE_ATOM);
    }
}
