<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApiIdempotency extends Model
{
    protected $fillable = [
        'user_id',
        'idempotency_key',
        'method',
        'route',
        'request_hash',
        'status_code',
        'response_body',
        'expires_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
