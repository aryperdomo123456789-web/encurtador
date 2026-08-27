<?php

declare(strict_types=1);

namespace App\Support\Shlink;

final class DomainService
{
    /** @var array<int,array<string,mixed>>|null */
    private ?array $cachedDomains = null;

    public function __construct(private readonly ShlinkClient $client) {}

    public function listDomains(bool $refresh = false): array
    {
        if ($refresh || $this->cachedDomains === null) {
            $response = $this->client->listDomains();
            $this->cachedDomains = $response['domains']['data'] ?? [];
        }

        return $this->cachedDomains;
    }

    public function getDefaultDomain(bool $refresh = false): ?string
    {
        foreach ($this->listDomains($refresh) as $domain) {
            if (($domain['isDefault'] ?? false) === true) {
                return $this->normalizeDomainValue($domain['domain'] ?? null);
            }
        }

        return null;
    }

    public function normalizeForCreation(?string $domain, bool $refresh = false): ?string
    {
        $domain = $this->normalizeDomainValue($domain);

        if ($domain === null) {
            return null;
        }

        if ($this->getDefaultDomain($refresh) === $domain) {
            return null;
        }

        return $domain;
    }

    public function ensureRegistered(string $domain, array $payload = []): array
    {
        $normalized = $this->normalizeDomainValue($domain);

        if ($normalized === null) {
            throw new \InvalidArgumentException('Domain can not be empty.');
        }

        foreach ($this->listDomains(true) as $candidate) {
            if ($this->normalizeDomainValue($candidate['domain'] ?? null) === $normalized) {
                return $candidate;
            }
        }

        $response = $this->client->createDomain(array_merge(['domain' => $normalized], $payload));
        $this->cachedDomains = null;

        return $response;
    }

    private function normalizeDomainValue(mixed $domain): ?string
    {
        if ($domain === null) {
            return null;
        }

        $domain = trim((string) $domain);

        return $domain === '' ? null : strtolower($domain);
    }
}
