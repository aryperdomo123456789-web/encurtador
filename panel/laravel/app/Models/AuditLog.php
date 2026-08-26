<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'subject_type',
        'subject_id',
        'action',
        'changes',
        'metadata',
        'request_id',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'subject_id' => 'integer',
        'changes' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
