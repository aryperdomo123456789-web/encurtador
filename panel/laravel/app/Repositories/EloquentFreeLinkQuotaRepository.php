<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\FreeLinkQuotaRepository;
use App\Models\MonthlyQuotaUsage;
use App\Models\ShortLink;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

final class EloquentFreeLinkQuotaRepository implements FreeLinkQuotaRepository
{
    public function countFreeLinksForPeriod(int $userId, DateTimeInterface $from, DateTimeInterface $to): int
    {
        return ShortLink::query()
            ->where('user_id', $userId)
            ->where('is_free_link', true)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    public function recordFreeLinkCreation(int $userId, array $record): void
    {
        DB::transaction(function () use ($userId, $record): void {
            ShortLink::query()->create([
                'user_id' => $userId,
                'customer_domain_id' => null,
                'plan_id' => null,
                'shlink_short_url' => $record['shortUrl'] ?? null,
                'shlink_short_code' => $record['shortCode'] ?? null,
                'domain' => $record['domain'] ?? config('shlink.default_domain'),
                'long_url' => $record['longUrl'] ?? '',
                'custom_slug' => null,
                'generated_slug' => $record['shortCode'] ?? null,
                'is_custom_slug' => false,
                'is_free_link' => true,
                'valid_until' => $record['validUntil'] ?? null,
                'valid_since' => $record['createdAt'] ?? null,
                'status' => 'active',
                'created_via' => 'panel',
                'shlink_payload' => $record['payload'] ?? null,
                'shlink_response' => $record,
            ]);

            $month = now('UTC')->format('Y-m');
            MonthlyQuotaUsage::query()->updateOrCreate(
                ['user_id' => $userId, 'quota_month' => $month],
                [
                    'free_links_created' => DB::raw('free_links_created + 1'),
                    'last_free_link_at' => now('UTC'),
                ]
            );
        });
    }
}
