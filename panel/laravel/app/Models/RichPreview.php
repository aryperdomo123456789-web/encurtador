<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TracksAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

final class RichPreview extends Model
{
    use TracksAuditTrail;

    protected $fillable = [
        'user_id',
        'created_by_user_id',
        'updated_by_user_id',
        'title',
        'slug',
        'campaign_name',
        'category_name',
        'description',
        'destination_url',
        'image_path',
        'image_url',
        'cta_label',
        'is_active',
        'click_count',
        'last_clicked_at',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'click_count' => 'integer',
        'last_clicked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function previewUrl(): string
    {
        return $this->absoluteRoute('rich-previews.public');
    }

    public function goUrl(): string
    {
        return $this->absoluteRoute('rich-previews.go');
    }

    public function imageUrl(): string
    {
        if ($this->image_path !== null && $this->image_path !== '' && File::exists(storage_path('app/public/' . $this->image_path))) {
            return $this->absoluteUrl('storage/' . $this->image_path);
        }

        if ($this->image_url !== null && $this->image_url !== '') {
            return $this->image_url;
        }

        return BrandingSetting::current()->socialImageUrl();
    }

    public function socialImageUrl(): string
    {
        if ($this->image_path !== null && $this->image_path !== '') {
            $source = storage_path('app/public/' . $this->image_path);

            if (File::exists($source)) {
                $derivativePath = $this->socialImageDerivativePath($source);
                $derivativeSource = storage_path('app/public/' . $derivativePath);

                if (! File::exists($derivativeSource)) {
                    $this->generateSocialImageDerivative($source, $derivativeSource);
                }

                if (File::exists($derivativeSource)) {
                    return $this->absoluteUrl('storage/' . $derivativePath);
                }

                return $this->absoluteUrl('storage/' . $this->image_path);
            }
        }

        if ($this->image_url !== null && $this->image_url !== '') {
            return $this->image_url;
        }

        return BrandingSetting::current()->socialImageUrl();
    }

    public function socialImageIsOptimized(): bool
    {
        if ($this->image_path === null || $this->image_path === '') {
            return false;
        }

        return File::exists(storage_path('app/public/' . $this->image_path));
    }

    public function displaySlug(): string
    {
        return $this->slug;
    }

    public function excerpt(): string
    {
        $text = trim((string) $this->description);

        return Str::limit($text !== '' ? $text : 'Visualização inteligente para WhatsApp, Telegram e redes.', 140);
    }

    public function campaignLabel(): string
    {
        return trim((string) ($this->campaign_name ?? ''));
    }

    public function categoryLabel(): string
    {
        return trim((string) ($this->category_name ?? ''));
    }

    private function absoluteRoute(string $name): string
    {
        $path = route($name, $this, false);

        return $this->absoluteUrl($path);
    }

    private function absoluteUrl(string $path): string
    {
        $path = ltrim($path, '/');
        $request = request();

        if ($request !== null) {
            return rtrim($request->getSchemeAndHttpHost(), '/') . '/' . $path;
        }

        return url($path);
    }

    private function socialImageDerivativePath(string $source): string
    {
        $base = pathinfo($this->image_path ?? 'rich-preview', PATHINFO_FILENAME);
        $stamp = (string) @filemtime($source);
        $hash = substr(sha1($base . '|' . $stamp . '|' . (string) @filesize($source)), 0, 12);

        return 'rich-previews/social/' . Str::slug($base ?: $this->slug ?: 'preview') . '-' . $hash . '.jpg';
    }

    private function generateSocialImageDerivative(string $source, string $destination): void
    {
        $info = @getimagesize($source);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return;
        }

        $sourceImage = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($source) : false,
            default => false,
        };

        if (! is_resource($sourceImage) && ! $sourceImage instanceof \GdImage) {
            return;
        }

        $canvasWidth = 1200;
        $canvasHeight = 630;
        $background = imagecreatetruecolor($canvasWidth, $canvasHeight);

        if (! is_resource($background) && ! $background instanceof \GdImage) {
            imagedestroy($sourceImage);
            return;
        }

        $white = imagecolorallocate($background, 255, 255, 255);
        imagefilledrectangle($background, 0, 0, $canvasWidth, $canvasHeight, $white);

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);

        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($sourceImage);
            imagedestroy($background);
            return;
        }

        $scale = min($canvasWidth / $sourceWidth, $canvasHeight / $sourceHeight, 1);
        $targetWidth = (int) max(1, floor($sourceWidth * $scale));
        $targetHeight = (int) max(1, floor($sourceHeight * $scale));
        $targetX = (int) floor(($canvasWidth - $targetWidth) / 2);
        $targetY = (int) floor(($canvasHeight - $targetHeight) / 2);

        imagecopyresampled(
            $background,
            $sourceImage,
            $targetX,
            $targetY,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        File::ensureDirectoryExists(dirname($destination));
        imagejpeg($background, $destination, 86);

        imagedestroy($sourceImage);
        imagedestroy($background);
    }
}
