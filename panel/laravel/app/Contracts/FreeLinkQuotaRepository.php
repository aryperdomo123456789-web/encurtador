<?php

declare(strict_types=1);

namespace App\Contracts;

use DateTimeInterface;

interface FreeLinkQuotaRepository
{
    public function countFreeLinksForPeriod(int $userId, DateTimeInterface $from, DateTimeInterface $to): int;

    /**
     * Reserva um slot mensal antes de chamar o provedor remoto.
     *
     * @throws \DomainException quando o limite foi atingido.
     */
    public function reserveFreeLinkCreation(int $userId, int $monthlyLimit): string;

    public function releaseFreeLinkCreation(int $userId, string $reservationId): void;

    /**
     * @param  array<string,mixed>  $record
     */
    public function recordFreeLinkCreation(int $userId, array $record): void;
}
