<?php

declare(strict_types=1);

namespace Vr766\Panel\Php;

require_once __DIR__ . '/ShlinkClient.php';

final class DomainService
{
    /** @var array<int,array<string,mixed>>|null */
    private ?array $cachedDomains = null;

    public function __construct(private readonly ShlinkClient $client)
    {
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listDomains(bool $refresh = false): array
    {
        if ($refresh || $this->cachedDomains === null) {
            $response = $this->client->listDomains();
            $this->cachedDomains = $response['domains']['data'] ?? [];
        }

        return $this->cachedDomains;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function getDefaultDomainRecord(bool $refresh = false): ?array
    {
        foreach ($this->listDomains($refresh) as $domain) {
            if (($domain['isDefault'] ?? false) === true) {
                return $domain;
            }
        }

        return null;
    }

    public function getDefaultDomain(bool $refresh = false): ?string
    {
        $domain = $this->getDefaultDomainRecord($refresh);

        return is_array($domain) ? $this->normalizeDomainValue($domain['domain'] ?? null) : null;
    }

    public function hasDomain(string $domain, bool $refresh = false): bool
    {
        return $this->findDomain($domain, $refresh) !== null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findDomain(string $domain, bool $refresh = false): ?array
    {
        $needle = $this->normalizeDomainValue($domain);

        foreach ($this->listDomains($refresh) as $candidate) {
            if ($needle !== null && $this->normalizeDomainValue($candidate['domain'] ?? null) === $needle) {
                return $candidate;
            }
        }

        return null;
    }

    public function isDefaultDomain(string $domain, bool $refresh = false): bool
    {
        $needle = $this->normalizeDomainValue($domain);
        $defaultDomain = $this->getDefaultDomain($refresh);

        if ($needle === null || $defaultDomain === null) {
            return false;
        }

        return $needle === $defaultDomain;
    }

    public function normalizeForCreation(?string $domain, bool $refresh = false): ?string
    {
        $domain = $this->normalizeDomainValue($domain);

        if ($domain === null) {
            return null;
        }

        if ($this->isDefaultDomain($domain, $refresh)) {
            return null;
        }

        return $domain;
    }

    /**
     * Register a custom domain in Shlink when it is not already present.
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function ensureRegistered(string $domain, array $payload = []): array
    {
        $normalized = $this->normalizeDomainValue($domain);

        if ($normalized === null) {
            throw new \InvalidArgumentException('Domain can not be empty.');
        }

        $existing = $this->findDomain($normalized, true);
        if (is_array($existing)) {
            return $existing;
        }

        $response = $this->client->createDomain(array_merge(['domain' => $normalized], $payload));
        $this->invalidateCache();

        return $response;
    }

    public function invalidateCache(): void
    {
        $this->cachedDomains = null;
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
