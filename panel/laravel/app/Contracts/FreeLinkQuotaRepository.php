<?php

declare(strict_types=1);

namespace App\Contracts;

use DateTimeInterface;

interface FreeLinkQuotaRepository
{
    public function countFreeLinksForPeriod(int $userId, DateTimeInterface $from, DateTimeInterface $to): int;

    /**
     * @param array<string,mixed> $record
     */
    public function recordFreeLinkCreation(int $userId, array $record): void;
}
