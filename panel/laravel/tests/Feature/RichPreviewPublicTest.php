<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\RichPreview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RichPreviewPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_preview_page_renders_og_metadata(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $richPreview = RichPreview::query()->create([
            'user_id' => $owner->id,
            'title' => 'Mega oferta',
            'slug' => 'mega-oferta',
            'campaign_name' => 'Campanha de teste',
            'category_name' => 'Vendas',
            'description' => 'Prévia inteligente para campanhas e vendas.',
            'destination_url' => 'https://example.com/oferta',
            'image_url' => 'https://example.com/imagem.png',
            'cta_label' => 'Abrir oferta',
            'is_active' => true,
        ]);

        $this->get(route('rich-previews.public', $richPreview))
            ->assertOk()
            ->assertSee('og:title', false)
            ->assertSee('Mega oferta', false)
            ->assertSee('example.com/imagem.png', false)
            ->assertSee('Abrir oferta', false)
            ->assertSee('Campanha de teste', false)
            ->assertSee('Vendas', false);
    }

    public function test_click_route_increments_counter_and_redirects(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $richPreview = RichPreview::query()->create([
            'user_id' => $owner->id,
            'title' => 'Mega oferta',
            'slug' => 'mega-oferta-clique',
            'campaign_name' => 'Campanha de teste',
            'category_name' => 'Vendas',
            'description' => 'Prévia inteligente para campanhas e vendas.',
            'destination_url' => 'https://example.com/oferta',
            'image_url' => 'https://example.com/imagem.png',
            'cta_label' => 'Abrir oferta',
            'is_active' => true,
        ]);

        $this->get(route('rich-previews.go', $richPreview))
            ->assertRedirect('https://example.com/oferta');

        $richPreview->refresh();

        $this->assertSame(1, $richPreview->click_count);
        $this->assertNotNull($richPreview->last_clicked_at);
    }
}
