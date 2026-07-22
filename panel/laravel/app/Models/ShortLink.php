<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class ShortLink extends Model
{
    protected $fillable = [
        'user_id',
        'customer_domain_id',
        'plan_id',
        'shlink_short_url',
        'shlink_short_code',
        'domain',
        'long_url',
        'custom_slug',
        'generated_slug',
        'is_custom_slug',
        'is_free_link',
        'valid_until',
        'valid_since',
        'status',
        'created_via',
        'shlink_payload',
        'shlink_response',
        'last_stats_sync_at',
    ];

    protected $casts = [
        'is_custom_slug' => 'bool',
        'is_free_link' => 'bool',
        'valid_until' => 'datetime',
        'valid_since' => 'datetime',
        'last_stats_sync_at' => 'datetime',
        'shlink_payload' => 'array',
        'shlink_response' => 'array',
    ];
}
