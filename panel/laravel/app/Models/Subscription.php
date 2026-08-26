<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Subscription extends Model
{
    use TracksAuditTrail;

    protected $fillable = [
        'user_id',
        'created_by_user_id',
        'updated_by_user_id',
        'plan_id',
        'provider',
        'provider_customer_id',
        'provider_subscription_id',
        'stripe_subscription_id',
        'stripe_event_id',
        'status',
        'current_period_start',
        'current_period_end',
        'cancel_at_period_end',
        'metadata',
    ];

    protected $casts = [
        'cancel_at_period_end' => 'bool',
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
