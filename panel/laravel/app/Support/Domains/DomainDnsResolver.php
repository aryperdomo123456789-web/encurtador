<?php

declare(strict_types=1);

namespace App\Support\Domains;

interface DomainDnsResolver
{
    /**
     * Retorna a lista de alvos (CNAME + A/AAAA reversos) resolvidos para $domain,
     * já em minúsculas e sem ponto final. Vazio quando o domínio não resolve.
     *
     * @return array<int, string>
     */
    public function resolveTargets(string $domain): array;
}
