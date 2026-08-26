<?php

declare(strict_types=1);

namespace App\Support\Shlink;

use DateTimeInterface;

final class AnalyticsService
{
    public function __construct(private readonly ShlinkClient $client) {}

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
    public function getTagStats(array $options = []): array
    {
        $query = [];

        if (isset($options['page']) && $options['page'] !== null) {
            $query['page'] = (int) $options['page'];
        }

        if (isset($options['itemsPerPage']) && $options['itemsPerPage'] !== null) {
            $query['itemsPerPage'] = (int) $options['itemsPerPage'];
        }

        return $this->client->request('GET', '/tags/stats', $query);
    }

    private function normalizeVisitQuery(array $options): array
    {
        $query = [];

        foreach (['domain', 'startDate', 'endDate', 'page', 'itemsPerPage'] as $key) {
            if (! array_key_exists($key, $options) || $options[$key] === null) {
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

        if (! empty($options['excludeBots'])) {
            $query['excludeBots'] = true;
        }

        return $query;
    }
}
