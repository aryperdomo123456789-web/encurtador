<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token_prefix',
        'token_hash',
        'scopes',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashPlainToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public static function prefixForPlainToken(string $plainToken): string
    {
        return substr(self::hashPlainToken($plainToken), 0, 16);
    }

    public function isValid(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function allows(string $scope): bool
    {
        $scopes = array_map('strval', (array) $this->scopes);

        return in_array('*', $scopes, true) || in_array($scope, $scopes, true);
    }
}
