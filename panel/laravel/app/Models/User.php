<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function customerDomains(): HasMany
    {
        return $this->hasMany(CustomerDomain::class);
    }

    public function shortLinks(): HasMany
    {
        return $this->hasMany(ShortLink::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * True se o usuário possui assinatura ativa/trialing em um plano
     * pago (is_free=false) que libera custom slug. Este é o gate mínimo
     * do fluxo premium; regras adicionais (domínio, expiração custom)
     * ficam para os próximos itens do P1.
     */
    public function isPremium(): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', true)
                    ->where('is_free', false)
                    ->where('allow_custom_slug', true);
            })
            ->exists();
    }

    /**
     * True se o usuário possui assinatura ativa/trialing em um plano
     * pago que libera domínio próprio (allow_custom_domain).
     */
    public function canUseCustomDomain(): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->whereHas('plan', function ($query): void {
                $query->where('is_active', true)
                    ->where('is_free', false)
                    ->where('allow_custom_domain', true);
            })
            ->exists();
    }
}
