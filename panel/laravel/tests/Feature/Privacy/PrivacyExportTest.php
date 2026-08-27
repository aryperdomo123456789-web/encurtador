<?php

declare(strict_types=1);

namespace Tests\Feature\Privacy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PrivacyExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exporta_apenas_os_dados_do_usuario_autenticado(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.test']);
        $other = User::factory()->create(['email' => 'other@example.test']);
        $planId = DB::table('plans')->insertGetId([
            'code' => 'privacy-test',
            'name' => 'Privacy Test',
            'description' => null,
            'is_free' => true,
            'monthly_short_url_limit' => 5,
            'allow_custom_slug' => false,
            'allow_custom_domain' => false,
            'allow_custom_expiration' => false,
            'allow_lifetime_links' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('short_links')->insert([
            'user_id' => $user->id,
            'workspace_id' => null,
            'customer_domain_id' => null,
            'plan_id' => $planId,
            'domain' => 'me.vr766.com',
            'long_url' => 'https://owner.example.test/offer',
            'custom_slug' => 'owner-offer',
            'generated_slug' => null,
            'is_custom_slug' => true,
            'is_free_link' => true,
            'status' => 'active',
            'created_via' => 'panel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('short_links')->insert([
            'user_id' => $other->id,
            'workspace_id' => null,
            'customer_domain_id' => null,
            'plan_id' => $planId,
            'domain' => 'me.vr766.com',
            'long_url' => 'https://other.example.test/private',
            'custom_slug' => 'other-private',
            'generated_slug' => null,
            'is_custom_slug' => true,
            'is_free_link' => true,
            'status' => 'active',
            'created_via' => 'panel',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($user)->getJson(route('privacy.export'));

        $response->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="melink-data-export.json"')
            ->assertJsonPath('format', 'melink-data-export-v1')
            ->assertJsonPath('account.email', 'owner@example.test');

        $links = $response->json('links');
        $this->assertCount(1, $links);
        $this->assertSame('https://owner.example.test/offer', $links[0]['long_url']);
        $this->assertStringNotContainsString('other@example.test', $response->getContent());
        $this->assertStringNotContainsString('other.example.test', $response->getContent());
        $this->assertStringNotContainsString('token_hash', $response->getContent());
    }
}
