<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\FreeLinkQuotaRepository;
use App\Models\MonthlyQuotaUsage;
use App\Models\Plan;
use App\Models\ShortLink;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

    public function reserveFreeLinkCreation(int $userId, int $monthlyLimit): string
    {
        if ($monthlyLimit < 1) {
            throw new DomainException('Free link quota is not configured.');
        }

        $reservationId = (string) Str::uuid();
        $now = now('UTC');
        $month = $now->format('Y-m');

        DB::transaction(function () use ($userId, $monthlyLimit, $reservationId, $now, $month): void {
            MonthlyQuotaUsage::query()->upsert([
                [
                    'user_id' => $userId,
                    'quota_month' => $month,
                    'free_links_created' => 0,
                    'free_links_rejected' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ], ['user_id', 'quota_month'], ['updated_at']);

            /** @var MonthlyQuotaUsage $usage */
            $usage = MonthlyQuotaUsage::query()
                ->where('user_id', $userId)
                ->where('quota_month', $month)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $usage->free_links_created >= $monthlyLimit) {
                $usage->increment('free_links_rejected');
                throw new DomainException(sprintf(
                    'Monthly free-link limit reached for user %d. Limit: %d links per month.',
                    $userId,
                    $monthlyLimit
                ));
            }

            $usage->forceFill([
                'free_links_created' => (int) $usage->free_links_created + 1,
                'last_free_link_at' => $now,
                'updated_at' => $now,
            ])->save();

            DB::table('free_link_reservations')->insert([
                'id' => $reservationId,
                'user_id' => $userId,
                'quota_month' => $month,
                'status' => 'reserved',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        return $reservationId;
    }

    public function releaseFreeLinkCreation(int $userId, string $reservationId): void
    {
        if ($reservationId === '') {
            return;
        }

        DB::transaction(function () use ($userId, $reservationId): void {
            $reservation = DB::table('free_link_reservations')
                ->where('id', $reservationId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($reservation === null || $reservation->status !== 'reserved') {
                return;
            }

            $now = now('UTC');
            DB::table('free_link_reservations')
                ->where('id', $reservationId)
                ->update([
                    'status' => 'released',
                    'released_at' => $now,
                    'updated_at' => $now,
                ]);

            MonthlyQuotaUsage::query()
                ->where('user_id', $userId)
                ->where('quota_month', $reservation->quota_month)
                ->where('free_links_created', '>', 0)
                ->decrement('free_links_created');
        });
    }

    /**
     * @param  array<string,mixed>  $record
     */
    public function recordFreeLinkCreation(int $userId, array $record): void
    {
        $freePlanId = Plan::query()->where('code', 'free')->value('id');
        if ($freePlanId === null) {
            throw new \RuntimeException('Free plan is not configured.');
        }

        $reservationId = (string) ($record['reservationId'] ?? '');
        if ($reservationId === '') {
            throw new \LogicException('Free link reservation is required.');
        }

        DB::transaction(function () use ($userId, $record, $freePlanId, $reservationId): void {
            $reservation = DB::table('free_link_reservations')
                ->where('id', $reservationId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($reservation === null || $reservation->status !== 'reserved') {
                throw new \RuntimeException('Free link reservation is no longer available.');
            }

            $shortLink = ShortLink::query()->create([
                'user_id' => $userId,
                'workspace_id' => $record['workspaceId'] ?? null,
                'customer_domain_id' => null,
                'plan_id' => $freePlanId,
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
                'shlink_response' => $record['response'] ?? $record,
            ]);

            DB::table('free_link_reservations')
                ->where('id', $reservationId)
                ->update([
                    'status' => 'committed',
                    'short_link_id' => $shortLink->id,
                    'committed_at' => now('UTC'),
                    'updated_at' => now('UTC'),
                ]);
        });
    }
}
