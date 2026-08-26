<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;

final class MonthlyQuotaUsage extends Model
{
    use TracksAuditTrail;

    protected $table = 'monthly_quota_usage';

    protected $fillable = [
        'user_id',
        'created_by_user_id',
        'updated_by_user_id',
        'quota_month',
        'free_links_created',
        'free_links_rejected',
        'last_free_link_at',
    ];

    protected $casts = [
        'free_links_created' => 'integer',
        'free_links_rejected' => 'integer',
        'last_free_link_at' => 'datetime',
    ];
}
