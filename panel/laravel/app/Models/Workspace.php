<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Workspace extends Model
{
    protected $fillable = [
        'owner_user_id',
        'name',
        'slug',
        'status',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function links(): HasMany
    {
        return $this->hasMany(ShortLink::class);
    }

    public function customerDomains(): HasMany
    {
        return $this->hasMany(CustomerDomain::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
