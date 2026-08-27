<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LinkEventLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'short_link_id',
        'actor_user_id',
        'event_type',
        'severity',
        'message',
        'payload',
        'request_id',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
