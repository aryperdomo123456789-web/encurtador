<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_free',
        'monthly_short_url_limit',
        'allow_custom_slug',
        'allow_custom_domain',
        'allow_custom_expiration',
        'allow_lifetime_links',
        'is_active',
    ];

    protected $casts = [
        'is_free' => 'bool',
        'allow_custom_slug' => 'bool',
        'allow_custom_domain' => 'bool',
        'allow_custom_expiration' => 'bool',
        'allow_lifetime_links' => 'bool',
        'is_active' => 'bool',
    ];
}
