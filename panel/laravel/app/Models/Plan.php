<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Plan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'marketing_label',
        'is_free',
        'monthly_price_cents',
        'currency',
        'monthly_short_url_limit',
        'monthly_click_limit',
        'custom_domain_limit',
        'allow_custom_slug',
        'allow_custom_domain',
        'allow_custom_expiration',
        'allow_lifetime_links',
        'is_active',
        'sort_order',
        'is_public',
        'is_featured',
        'stripe_product_id',
        'stripe_price_id',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    protected $casts = [
        'is_free' => 'bool',
        'monthly_price_cents' => 'int',
        'monthly_click_limit' => 'int',
        'custom_domain_limit' => 'int',
        'sort_order' => 'int',
        'allow_custom_slug' => 'bool',
        'allow_custom_domain' => 'bool',
        'allow_custom_expiration' => 'bool',
        'allow_lifetime_links' => 'bool',
        'is_active' => 'bool',
        'is_public' => 'bool',
        'is_featured' => 'bool',
    ];
}
