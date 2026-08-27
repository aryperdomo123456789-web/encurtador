<?php

declare(strict_types=1);

namespace Tests\Feature\Links;

use App\Models\Plan;
use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class QrCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_download_an_svg_qr_code_for_an_active_link(): void
    {
        $user = User::factory()->create();
        $link = $this->linkFor($user);

        $this->actingAs($user)
            ->get(route('links.qr', $link))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'inline; filename="melink-promo-verao.svg"')
            ->assertSee('<svg', false);
    }

    public function test_user_cannot_download_another_users_qr_code(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $link = $this->linkFor($owner);

        $this->actingAs($other)
            ->get(route('links.qr', $link))
            ->assertNotFound();
    }

    private function linkFor(User $user): ShortLink
    {
        $plan = Plan::firstOrCreate(['code' => 'premium'], [
            'code' => 'premium',
            'name' => 'Premium',
            'description' => 'Plano de teste',
            'is_free' => false,
            'monthly_short_url_limit' => null,
            'allow_custom_slug' => true,
            'allow_custom_domain' => true,
            'allow_custom_expiration' => true,
            'allow_lifetime_links' => true,
            'is_active' => true,
        ]);

        return ShortLink::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'shlink_short_url' => 'https://me.vr766.com/promo-verao',
            'shlink_short_code' => 'promo-verao',
            'domain' => 'me.vr766.com',
            'long_url' => 'https://example.com/oferta',
            'custom_slug' => 'promo-verao',
            'generated_slug' => 'promo-verao',
            'is_custom_slug' => true,
            'is_free_link' => false,
            'status' => 'active',
            'created_via' => 'test',
            'shlink_payload' => [],
            'shlink_response' => [],
        ]);
    }
}
