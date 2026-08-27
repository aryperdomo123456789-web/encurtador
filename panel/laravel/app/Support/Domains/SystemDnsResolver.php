<?php

declare(strict_types=1);

namespace App\Support\Domains;

final class SystemDnsResolver implements DomainDnsResolver
{
    public function resolveTargets(string $domain): array
    {
        $targets = [];

        $cname = @dns_get_record($domain, DNS_CNAME);
        if (is_array($cname)) {
            foreach ($cname as $record) {
                if (! empty($record['target'])) {
                    $targets[] = $this->normalize((string) $record['target']);
                }
            }
        }

        foreach ([DNS_A, DNS_AAAA] as $recordType) {
            $records = @dns_get_record($domain, $recordType);
            if (! is_array($records)) {
                continue;
            }

            foreach ($records as $record) {
                $address = (string) ($record['ip'] ?? '');
                if ($address !== '' && $this->isPublicAddress($address)) {
                    $targets[] = $this->normalize($address);
                }
            }
        }

        return array_values(array_unique(array_filter($targets)));
    }

    private function normalize(string $value): string
    {
        return strtolower(rtrim(trim($value), '.'));
    }

    private function isPublicAddress(string $address): bool
    {
        return filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
