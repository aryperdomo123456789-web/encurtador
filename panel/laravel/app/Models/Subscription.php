<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'provider',
        'provider_customer_id',
        'provider_subscription_id',
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
}
