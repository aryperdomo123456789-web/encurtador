<?php

declare(strict_types=1);

namespace Vr766\Panel\Php;

use DateTimeInterface;

require_once __DIR__ . '/ShlinkClient.php';

final class AnalyticsService
{
    public function __construct(private readonly ShlinkClient $client)
    {
    }

    /**
     * @param array{
     *     domain?: string|null,
     *     startDate?: DateTimeInterface|string|null,
     *     endDate?: DateTimeInterface|string|null,
     *     page?: int|null,
     *     itemsPerPage?: int|null,
     *     excludeBots?: bool|null
     * } $options
     * @return array<string,mixed>
     */
    public function getShortUrlVisits(string $shortCode, array $options = []): array
    {
        return $this->client->getShortUrlVisits($shortCode, $this->normalizeVisitQuery($options));
    }

    /**
     * @param array{
     *     startDate?: DateTimeInterface|string|null,
     *     endDate?: DateTimeInterface|string|null,
     *     page?: int|null,
     *     itemsPerPage?: int|null,
     *     excludeBots?: bool|null
     * } $options
     * @return array<string,mixed>
     */
    public function getDomainVisits(string $domain, array $options = []): array
    {
        return $this->client->getDomainVisits($domain, $this->normalizeVisitQuery($options));
    }

    /**
     * @return array<string,mixed>
     */
    public function getGlobalVisitsStats(): array
    {
        return $this->client->getGlobalVisitsStats();
    }

    /**
     * @param array{page?: int|null, itemsPerPage?: int|null} $options
     * @return array<string,mixed>
     */
    public function getTagStats(array $options = []): array
    {
        $query = [];

        if (isset($options['page']) && $options['page'] !== null) {
            $query['page'] = (int) $options['page'];
        }

        if (isset($options['itemsPerPage']) && $options['itemsPerPage'] !== null) {
            $query['itemsPerPage'] = (int) $options['itemsPerPage'];
        }

        return $this->client->getTagStats($query);
    }

    /**
     * @param array{
     *     startDate?: DateTimeInterface|string|null,
     *     endDate?: DateTimeInterface|string|null,
     *     page?: int|null,
     *     itemsPerPage?: int|null,
     *     excludeBots?: bool|null
     * } $options
     * @return array<string,mixed>
     */
    public function getTagVisits(string $tag, array $options = []): array
    {
        return $this->client->getTagVisits($tag, $this->normalizeVisitQuery($options));
    }

    /**
     * @return int Amount of visits removed.
     */
    public function deleteShortUrlVisits(string $shortCode, ?string $domain = null): int
    {
        $response = $this->client->deleteShortUrlVisits($shortCode, $domain);

        return (int) ($response['deletedVisits'] ?? 0);
    }

    /**
     * @param array<string,mixed> $options
     * @return array<string,mixed>
     */
    private function normalizeVisitQuery(array $options): array
    {
        $query = [];

        foreach (['domain', 'startDate', 'endDate', 'page', 'itemsPerPage'] as $key) {
            if (!array_key_exists($key, $options) || $options[$key] === null) {
                continue;
            }

            $value = $options[$key];

            if ($value instanceof DateTimeInterface) {
                $query[$key] = $value;
                continue;
            }

            if ($key === 'page' || $key === 'itemsPerPage') {
                $query[$key] = (int) $value;
                continue;
            }

            $query[$key] = trim((string) $value);
        }

        if (!empty($options['excludeBots'])) {
            $query['excludeBots'] = true;
        }

        return $query;
    }
}
