<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BrandingSetting extends Model
{
    use TracksAuditTrail;

    protected $fillable = [
        'key',
        'created_by_user_id',
        'updated_by_user_id',
        'logo_path',
        'logo_light_path',
        'logo_dark_path',
        'favicon_path',
        'social_image_path',
    ];

    protected static function booted(): void
    {
        static::saved(static function (): void {
            Cache::forget(self::cacheKey());
        });

        static::deleted(static function (): void {
            Cache::forget(self::cacheKey());
        });
    }

    public static function current(): self
    {
        if (! Schema::hasTable('branding_settings')) {
            return new static([
                'key' => 'default',
            ]);
        }

        try {
            return Cache::rememberForever(self::cacheKey(), function (): self {
                return static::query()->firstOrCreate(['key' => 'default']);
            });
        } catch (Throwable) {
            return new static([
                'key' => 'default',
            ]);
        }
    }

    public function logoUrl(string $mode = 'light'): string
    {
        $path = $mode === 'dark'
            ? ($this->logo_dark_path ?: $this->logo_light_path ?: $this->logo_path)
            : ($this->logo_light_path ?: $this->logo_path ?: $this->logo_dark_path);

        return $this->resolveUrl($path, 'branding/default-logo.png');
    }

    public function faviconUrl(): string
    {
        return $this->resolveUrl($this->favicon_path, 'branding/default-favicon.png');
    }

    public function socialImageUrl(): string
    {
        return $this->resolveUrl($this->social_image_path, 'branding/default-social.png');
    }

    public function hasCustomLogo(string $mode = 'light'): bool
    {
        $path = $mode === 'dark'
            ? ($this->logo_dark_path ?: $this->logo_light_path ?: $this->logo_path)
            : ($this->logo_light_path ?: $this->logo_path ?: $this->logo_dark_path);

        return $path !== null && $path !== '';
    }

    public function hasCustomFavicon(): bool
    {
        return $this->favicon_path !== null && $this->favicon_path !== '';
    }

    public function hasCustomSocialImage(): bool
    {
        return $this->social_image_path !== null && $this->social_image_path !== '';
    }

    private function resolveUrl(?string $path, string $fallbackAsset): string
    {
        if ($path !== null && $path !== '' && File::exists(storage_path('app/public/'.$path))) {
            return $this->absoluteUrl('storage/'.$path);
        }

        return $this->absoluteUrl($fallbackAsset);
    }

    private static function cacheKey(): string
    {
        return 'branding.settings.current';
    }

    private function absoluteUrl(string $path): string
    {
        $path = ltrim($path, '/');
        $request = request();

        if ($request !== null) {
            return rtrim($request->getSchemeAndHttpHost(), '/').'/'.$path;
        }

        return url($path);
    }
}
