<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class LinkEventLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'short_link_id',
        'event_type',
        'severity',
        'message',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];
}
