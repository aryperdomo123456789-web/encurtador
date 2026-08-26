<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RichPreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class RichPreviewController extends Controller
{
    private function requireOwner(Request $request): void
    {
        abort_unless((bool) optional($request->user())->isOwner(), 403);
    }

    public function index(Request $request): View
    {
        $this->requireOwner($request);

        $campaign = trim((string) $request->string('campaign'));
        $category = trim((string) $request->string('category'));

        $query = RichPreview::query()->with(['user', 'creator', 'updater']);

        if ($campaign !== '') {
            $query->where('campaign_name', 'like', '%' . $campaign . '%');
        }

        if ($category !== '') {
            $query->where('category_name', 'like', '%' . $category . '%');
        }

        return view('admin.rich-previews.index', [
            'richPreviews' => $query
                ->latest('id')
                ->get(),
            'campaign' => $campaign,
            'category' => $category,
            'summary' => [
                'total' => $query->count(),
                'active' => (clone $query)->where('is_active', true)->count(),
                'clicks' => (clone $query)->sum('click_count'),
                'campaigns' => RichPreview::query()->whereNotNull('campaign_name')->distinct()->count('campaign_name'),
                'categories' => RichPreview::query()->whereNotNull('category_name')->distinct()->count('category_name'),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->requireOwner($request);

        return view('admin.rich-previews.create', [
            'richPreview' => new RichPreview([
                'cta_label' => 'Abrir link',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireOwner($request);

        $data = $this->validatePayload($request);
        $slug = $this->resolveSlug($data['slug'] ?? null, $data['title']);

        $richPreview = new RichPreview();
        $richPreview->fill([
            'user_id' => (int) $request->user()->id,
            'title' => $data['title'],
            'slug' => $slug,
            'campaign_name' => $data['campaign_name'] ?? null,
            'category_name' => $data['category_name'] ?? null,
            'description' => $data['description'] ?? null,
            'destination_url' => $data['destination_url'],
            'image_url' => $data['image_url'] ?? null,
            'cta_label' => $data['cta_label'] ?? 'Abrir link',
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->hasFile('image_upload')) {
            $richPreview->image_path = $this->storeImage($request->file('image_upload'), $slug);
        }

        $richPreview->save();

        return redirect()
            ->route('admin.rich-previews.edit', $richPreview)
            ->with('status', 'Rich preview criado com sucesso.');
    }

    public function edit(Request $request, RichPreview $richPreview): View
    {
        $this->requireOwner($request);

        return view('admin.rich-previews.edit', [
            'richPreview' => $richPreview,
        ]);
    }

    public function update(Request $request, RichPreview $richPreview): RedirectResponse
    {
        $this->requireOwner($request);

        $data = $this->validatePayload($request, $richPreview->id);
        $slug = $this->resolveSlug($data['slug'] ?? null, $data['title'], $richPreview->id);

        $richPreview->fill([
            'title' => $data['title'],
            'slug' => $slug,
            'campaign_name' => $data['campaign_name'] ?? null,
            'category_name' => $data['category_name'] ?? null,
            'description' => $data['description'] ?? null,
            'destination_url' => $data['destination_url'],
            'image_url' => $data['image_url'] ?? null,
            'cta_label' => $data['cta_label'] ?? 'Abrir link',
            'is_active' => $request->boolean('is_active', true),
        ]);

        if ($request->hasFile('image_upload')) {
            $this->deleteIfCustom($richPreview->image_path);
            $richPreview->image_path = $this->storeImage($request->file('image_upload'), $slug);
        } elseif ($request->boolean('reset_image')) {
            $this->deleteIfCustom($richPreview->image_path);
            $richPreview->image_path = null;
            $richPreview->image_url = null;
        }

        $richPreview->save();

        return redirect()
            ->route('admin.rich-previews.edit', $richPreview)
            ->with('status', 'Rich preview atualizado com sucesso.');
    }

    public function duplicate(Request $request, RichPreview $richPreview): RedirectResponse
    {
        $this->requireOwner($request);

        $copy = $richPreview->replicate([
            'slug',
            'click_count',
            'last_clicked_at',
            'created_at',
            'updated_at',
        ]);

        $copy->title = $richPreview->title . ' - cópia';
        $copy->slug = $this->resolveSlug($richPreview->slug . '-copia', $richPreview->title . ' cópia');
        $copy->click_count = 0;
        $copy->last_clicked_at = null;
        $copy->is_active = false;

        if ($richPreview->image_path !== null && $richPreview->image_path !== '') {
            $copy->image_path = $this->cloneImagePath($richPreview->image_path, $copy->slug);
        }

        $copy->save();

        return redirect()
            ->route('admin.rich-previews.edit', $copy)
            ->with('status', 'Rich preview duplicado com sucesso.');
    }

    public function destroy(Request $request, RichPreview $richPreview): RedirectResponse
    {
        $this->requireOwner($request);
        $this->deleteIfCustom($richPreview->image_path);
        $richPreview->delete();

        return redirect()
            ->route('admin.rich-previews.index')
            ->with('status', 'Rich preview removido.');
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:90', 'unique:rich_previews,slug,' . $ignoreId],
            'campaign_name' => ['nullable', 'string', 'max:120'],
            'category_name' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'destination_url' => ['required', 'url', 'max:2048'],
            'image_upload' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'cta_label' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'reset_image' => ['nullable', 'boolean'],
        ], [
            'slug.unique' => 'Este slug já está em uso. Escolha outro ou deixe vazio para gerar automaticamente.',
        ]);
    }

    private function resolveSlug(?string $slug, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug !== null && $slug !== '' ? $slug : $title);
        $base = $base !== '' ? $base : 'preview';
        $candidate = $base;
        $suffix = 0;

        while (
            RichPreview::query()
                ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $suffix++;
            $candidate = $base . '-' . Str::lower(Str::random(4)) . ($suffix > 1 ? '-' . $suffix : '');
        }

        return $candidate;
    }

    private function storeImage(UploadedFile $upload, string $slug): string
    {
        $extension = strtolower($upload->getClientOriginalExtension() ?: $upload->extension() ?: 'png');
        $directory = storage_path('app/public/rich-previews');
        File::ensureDirectoryExists($directory);

        $name = Str::slug($slug) . '-' . Str::random(16) . '.' . $extension;
        $upload->move($directory, $name);

        return 'rich-previews/' . $name;
    }

    private function cloneImagePath(string $currentPath, string $slug): string
    {
        $source = storage_path('app/public/' . $currentPath);
        $directory = storage_path('app/public/rich-previews');
        File::ensureDirectoryExists($directory);

        if (! File::exists($source)) {
            return $currentPath;
        }

        $extension = pathinfo($currentPath, PATHINFO_EXTENSION) ?: 'png';
        $name = Str::slug($slug) . '-' . Str::random(16) . '.' . strtolower($extension);

        File::copy($source, $directory . DIRECTORY_SEPARATOR . $name);

        return 'rich-previews/' . $name;
    }

    private function deleteIfCustom(?string $path): void
    {
        if ($path !== null && $path !== '') {
            File::delete(storage_path('app/public/' . $path));
        }
    }
}
