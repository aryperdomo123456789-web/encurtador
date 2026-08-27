<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CustomerDomain;
use App\Support\Domains\TlsProbeService;
use Illuminate\Console\Command;

final class RefreshDomainTlsStatus extends Command
{
    protected $signature = 'panel:tls:refresh {--all : Reprocessa tambem dominios ja ativos}';

    protected $description = 'Sonda HTTPS dos dominios customizados e atualiza tls_status.';

    public function handle(TlsProbeService $probe): int
    {
        $query = CustomerDomain::query()->where('status', 'active');
        if (! $this->option('all')) {
            $query->whereIn('tls_status', ['pending', 'error', null]);
        }

        $count = 0;
        foreach ($query->cursor() as $domain) {
            $result = $probe->probe($domain);
            $this->line(sprintf('[%s] %s -> %s', $domain->id, $domain->domain, $result));
            $count++;
        }

        $this->info(sprintf('Processados: %d', $count));

        return self::SUCCESS;
    }
}
