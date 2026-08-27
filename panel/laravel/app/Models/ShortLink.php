<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ShortLink extends Model
{
    use TracksAuditTrail;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'created_by_user_id',
        'updated_by_user_id',
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
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'forward_query',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    protected $casts = [
        'is_custom_slug' => 'bool',
        'is_free_link' => 'bool',
        'forward_query' => 'bool',
        'valid_until' => 'datetime',
        'valid_since' => 'datetime',
        'last_stats_sync_at' => 'datetime',
        'shlink_payload' => 'array',
        'shlink_response' => 'array',
    ];
}
