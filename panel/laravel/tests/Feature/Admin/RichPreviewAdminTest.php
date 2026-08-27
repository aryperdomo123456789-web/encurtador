<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\RichPreview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class RichPreviewAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_rich_preview_page(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->get(route('admin.rich-previews.index'))
            ->assertOk()
            ->assertSee('Rich Preview', false);
    }

    public function test_common_user_is_forbidden_from_rich_preview_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.rich-previews.index'))
            ->assertForbidden();
    }

    public function test_owner_can_create_rich_preview_with_upload(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $pngBytes = file_get_contents(public_path('branding/default-social.png'));

        $response = $this->actingAs($owner)->post(route('admin.rich-previews.store'), [
            'title' => 'Oferta do dia',
            'description' => 'Uma oferta pronta para WhatsApp e Telegram.',
            'destination_url' => 'https://example.com/oferta',
            'cta_label' => 'Abrir oferta',
            'image_upload' => UploadedFile::fake()->createWithContent('preview.png', $pngBytes),
            'is_active' => 1,
        ]);

        $response->assertRedirect();

        $richPreview = RichPreview::query()->firstOrFail();

        $this->assertSame('Oferta do dia', $richPreview->title);
        $this->assertSame('https://example.com/oferta', $richPreview->destination_url);
        $this->assertNotNull($richPreview->image_path);
        $this->assertTrue(file_exists(storage_path('app/public/'.$richPreview->image_path)));

        $this->actingAs($owner)
            ->get(route('admin.rich-previews.edit', $richPreview))
            ->assertOk()
            ->assertSee('Editar rich preview', false);
    }

    public function test_owner_can_filter_and_duplicate_rich_preview(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $source = RichPreview::query()->create([
            'user_id' => $owner->id,
            'title' => 'Oferta principal',
            'slug' => 'oferta-principal',
            'campaign_name' => 'Lançamento agosto',
            'category_name' => 'Vendas',
            'description' => 'Card com campanha e categoria.',
            'destination_url' => 'https://example.com/oferta',
            'image_url' => 'https://example.com/imagem.png',
            'cta_label' => 'Abrir oferta',
            'is_active' => true,
            'click_count' => 7,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.rich-previews.index', ['campaign' => 'Lançamento', 'category' => 'Vendas']))
            ->assertOk()
            ->assertSee('Lançamento agosto', false)
            ->assertSee('Vendas', false);

        $response = $this->actingAs($owner)->post(route('admin.rich-previews.duplicate', $source));

        $response->assertRedirect();

        $copy = RichPreview::query()
            ->where('title', 'like', 'Oferta principal - cópia%')
            ->firstOrFail();

        $this->assertNotSame($source->slug, $copy->slug);
        $this->assertSame('Lançamento agosto', $copy->campaign_name);
        $this->assertSame('Vendas', $copy->category_name);
        $this->assertSame(0, $copy->click_count);
        $this->assertFalse($copy->is_active);
    }
}
