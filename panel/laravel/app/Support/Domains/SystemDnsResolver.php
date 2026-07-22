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
                if (!empty($record['target'])) {
                    $targets[] = $this->normalize((string) $record['target']);
                }
            }
        }

        $a = @dns_get_record($domain, DNS_A);
        if (is_array($a)) {
            foreach ($a as $record) {
                if (!empty($record['ip'])) {
                    $targets[] = $this->normalize((string) $record['ip']);
                }
            }
        }

        return array_values(array_unique(array_filter($targets)));
    }

    private function normalize(string $value): string
    {
        return strtolower(rtrim(trim($value), '.'));
    }
}
