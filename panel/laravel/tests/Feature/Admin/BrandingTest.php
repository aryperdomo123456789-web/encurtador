<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\BrandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/public/branding'));
        parent::tearDown();
    }

    public function test_owner_can_open_canonical_brand_route(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->get(route('admin.brand.edit'))
            ->assertOk()
            ->assertSee('Marca &amp; identidade', false)
            ->assertSee('Logo light', false)
            ->assertSee('Logo dark', false)
            ->assertSee('Salvar identidade', false);
    }

    public function test_owner_can_persist_light_dark_favicon_and_social_branding(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->post(route('admin.brand.update'), [
            'logo_light_image' => UploadedFile::fake()->create('logo-light.png', 100, 'image/png'),
            'logo_dark_image' => UploadedFile::fake()->create('logo-dark.png', 100, 'image/png'),
            'favicon_image' => UploadedFile::fake()->create('favicon.png', 100, 'image/png'),
            'social_image' => UploadedFile::fake()->create('social.png', 100, 'image/png'),
        ]);

        $response
            ->assertRedirect(route('admin.brand.edit'))
            ->assertSessionHas('status', 'Marca atualizada com sucesso.');

        $branding = BrandingSetting::current()->fresh();

        $this->assertNotNull($branding);
        $this->assertNotNull($branding->logo_light_path);
        $this->assertNotNull($branding->logo_dark_path);
        $this->assertNotNull($branding->favicon_path);
        $this->assertNotNull($branding->social_image_path);
        $this->assertStringContainsString('/storage/branding/', $branding->logoUrl('light'));
        $this->assertStringContainsString('/storage/branding/', $branding->logoUrl('dark'));
        $this->assertStringContainsString('/storage/branding/', $branding->faviconUrl());
        $this->assertStringContainsString('/storage/branding/', $branding->socialImageUrl());
    }

    public function test_common_user_cannot_manage_branding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.brand.edit'))
            ->assertForbidden();
    }
}
