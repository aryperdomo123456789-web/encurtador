<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MonthlyQuotaUsage;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * A cota mensal free e enforce em tempo real via SQL (LinkProvisioner
 * conta ShortLink.created_at dentro do mes vigente). Este comando
 * garante o marco explicito de virada de mes, poda historico antigo
 * de monthly_quota_usage e emite logs estruturados para auditoria.
 */
final class ResetMonthlyQuota extends Command
{
    protected $signature = 'panel:quota:reset {--keep-months=12 : Meses de historico a preservar em monthly_quota_usage}';

    protected $description = 'Marca virada de mes da cota free e poda historico antigo de uso.';

    public function handle(): int
    {
        $keep = max(1, (int) $this->option('keep-months'));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $currentMonth = $now->format('Y-m');
        $cutoff = $now->modify(sprintf('-%d months', $keep))->format('Y-m');

        $pruned = MonthlyQuotaUsage::query()
            ->where('quota_month', '<', $cutoff)
            ->delete();

        Log::info('panel.quota.reset', [
            'current_month' => $currentMonth,
            'cutoff_month' => $cutoff,
            'pruned_rows' => $pruned,
        ]);

        $this->info(sprintf('Cota free rolou para %s. Podados %d registros anteriores a %s.', $currentMonth, $pruned, $cutoff));

        return self::SUCCESS;
    }
}
