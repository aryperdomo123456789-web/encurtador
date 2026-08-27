<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CustomerDomain extends Model
{
    use TracksAuditTrail;

    protected $fillable = [
        'user_id',
        'workspace_id',
        'created_by_user_id',
        'updated_by_user_id',
        'domain',
        'status',
        'is_primary',
        'dns_target',
        'dns_verified_at',
        'shlink_domain_registered_at',
        'tls_mode',
        'tls_status',
        'tls_last_error',
        'shlink_domain_payload',
    ];

    protected $casts = [
        'is_primary' => 'bool',
        'dns_verified_at' => 'datetime',
        'shlink_domain_registered_at' => 'datetime',
        'shlink_domain_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
