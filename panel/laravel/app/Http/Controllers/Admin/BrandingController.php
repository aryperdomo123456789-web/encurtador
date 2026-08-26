<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BrandingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class BrandingController extends Controller
{
    private function requireOwner(Request $request): void
    {
        abort_unless((bool) optional($request->user())->isOwner(), 403);
    }

    public function edit(Request $request): View
    {
        $this->requireOwner($request);

        return view('admin.branding.edit', [
            'branding' => BrandingSetting::current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->requireOwner($request);

        $data = $request->validate([
            'logo_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'favicon_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'social_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'reset_logo' => ['nullable', 'boolean'],
            'reset_favicon' => ['nullable', 'boolean'],
            'reset_social_image' => ['nullable', 'boolean'],
        ]);

        $branding = BrandingSetting::current();

        $branding->logo_path = $this->resolveSlot(
            currentPath: $branding->logo_path,
            upload: $request->file('logo_image'),
            reset: $request->boolean('reset_logo'),
            slot: 'logo',
        );

        $branding->favicon_path = $this->resolveSlot(
            currentPath: $branding->favicon_path,
            upload: $request->file('favicon_image'),
            reset: $request->boolean('reset_favicon'),
            slot: 'favicon',
        );

        $branding->social_image_path = $this->resolveSlot(
            currentPath: $branding->social_image_path,
            upload: $request->file('social_image'),
            reset: $request->boolean('reset_social_image'),
            slot: 'social',
        );

        $branding->save();

        return redirect()
            ->route('admin.branding.edit')
            ->with('status', 'Marca atualizada com sucesso.');
    }

    private function resolveSlot(?string $currentPath, ?UploadedFile $upload, bool $reset, string $slot): ?string
    {
        if ($upload instanceof UploadedFile) {
            $this->deleteIfCustom($currentPath);
            $extension = strtolower($upload->getClientOriginalExtension() ?: $upload->extension() ?: 'png');
            $name = Str::slug($slot) . '-' . Str::random(16) . '.' . $extension;

            $directory = storage_path('app/public/branding');
            File::ensureDirectoryExists($directory);
            $upload->move($directory, $name);

            return 'branding/' . $name;
        }

        if ($reset) {
            $this->deleteIfCustom($currentPath);

            return null;
        }

        return $currentPath;
    }

    private function deleteIfCustom(?string $path): void
    {
        if ($path !== null && $path !== '') {
            File::delete(storage_path('app/public/' . $path));
        }
    }
}
