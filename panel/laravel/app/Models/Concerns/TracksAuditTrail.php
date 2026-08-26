<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait TracksAuditTrail
{
    protected static function bootTracksAuditTrail(): void
    {
        static::creating(function (Model $model): void {
            $userId = self::resolveActorUserId();

            if ($userId !== null && $model->getAttribute('created_by_user_id') === null) {
                $model->setAttribute('created_by_user_id', $userId);
            }

            if ($userId !== null) {
                $model->setAttribute('updated_by_user_id', $userId);
            }
        });

        static::updating(function (Model $model): void {
            $userId = self::resolveActorUserId();

            if ($userId !== null) {
                $model->setAttribute('updated_by_user_id', $userId);
            }
        });

        static::created(function (Model $model): void {
            self::writeAuditLog($model, 'created', [
                'attributes' => self::safeArray($model->getAttributes()),
            ]);
        });

        static::updated(function (Model $model): void {
            self::writeAuditLog($model, 'updated', [
                'changes' => self::safeArray($model->getChanges()),
            ]);
        });

        static::deleted(function (Model $model): void {
            self::writeAuditLog($model, 'deleted', [
                'attributes' => self::safeArray($model->getAttributes()),
            ]);
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    protected static function resolveActorUserId(): ?int
    {
        $request = request();
        $user = $request?->user();

        return $user?->id !== null ? (int) $user->id : null;
    }

    protected static function writeAuditLog(Model $model, string $action, array $changes = []): void
    {
        try {
            AuditLog::query()->create([
                'actor_user_id' => self::resolveActorUserId(),
                'subject_type' => $model::class,
                'subject_id' => $model->getKey(),
                'action' => $action,
                'changes' => $changes,
                'metadata' => [
                    'route' => request()?->path(),
                    'method' => request()?->method(),
                    'request_id' => request()?->attributes->get('request_id'),
                ],
                'request_id' => request()?->attributes->get('request_id'),
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            // Audit logging must never block the main save/delete flow.
        }
    }

    protected static function safeArray(array $values): array
    {
        return array_filter(
            $values,
            static fn ($value): bool => is_scalar($value) || $value === null || is_array($value)
        );
    }
}
