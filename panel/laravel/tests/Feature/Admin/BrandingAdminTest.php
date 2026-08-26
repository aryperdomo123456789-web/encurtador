<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\BrandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class BrandingAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_owner_can_open_branding_page(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->get(route('admin.branding.edit'))
            ->assertOk()
            ->assertSee('Marca do painel', false);
    }

    public function test_common_user_is_forbidden_from_branding_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.branding.edit'))
            ->assertForbidden();
    }

    public function test_owner_can_upload_branding_assets(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $pngBytes = file_get_contents(public_path('branding/default-social.png'));

        $response = $this->actingAs($owner)->post(route('admin.branding.update'), [
            'logo_image' => UploadedFile::fake()->createWithContent('logo.png', $pngBytes),
            'favicon_image' => UploadedFile::fake()->createWithContent('favicon.png', $pngBytes),
            'social_image' => UploadedFile::fake()->createWithContent('social.png', $pngBytes),
        ]);

        $response->assertRedirect(route('admin.branding.edit'));

        $branding = BrandingSetting::query()->firstOrFail();

        $this->assertNotNull($branding->logo_path);
        $this->assertNotNull($branding->favicon_path);
        $this->assertNotNull($branding->social_image_path);

        $this->assertTrue(file_exists(storage_path('app/public/' . $branding->logo_path)));
        $this->assertTrue(file_exists(storage_path('app/public/' . $branding->favicon_path)));
        $this->assertTrue(file_exists(storage_path('app/public/' . $branding->social_image_path)));
    }
}
