<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlanCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_plan_catalog_with_metrics(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $plan = Plan::query()->create($this->planData());

        $this->actingAs($owner)
            ->get(route('admin.plans.index'))
            ->assertOk()
            ->assertSee('Catálogo de planos', false)
            ->assertSee($plan->name, false)
            ->assertSee('R$ 19,90', false)
            ->assertSee('5.000', false);
    }

    public function test_common_user_is_forbidden_from_plan_catalog(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('admin.plans.index'))
            ->assertForbidden();
    }

    public function test_owner_can_create_plan_with_limits(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->post(route('admin.plans.store'), $this->planData())
            ->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseHas('plans', [
            'code' => 'lab-start',
            'monthly_price_cents' => 1990,
            'monthly_short_url_limit' => 25,
            'monthly_click_limit' => 5000,
            'custom_domain_limit' => 1,
            'currency' => 'BRL',
            'is_public' => true,
        ]);
    }

    public function test_owner_can_archive_paid_plan_without_deleting_it(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $plan = Plan::query()->create($this->planData(['code' => 'growth']));

        $this->actingAs($owner)
            ->delete(route('admin.plans.archive', $plan))
            ->assertRedirect(route('admin.plans.index'));

        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'is_active' => false,
            'is_public' => false,
        ]);
    }

    /** @return array<string,mixed> */
    private function planData(array $overrides = []): array
    {
        return array_merge([
            'code' => 'lab-start',
            'name' => 'Start',
            'description' => 'Para creators e pequenas lojas.',
            'marketing_label' => 'Para creators e pequenas lojas',
            'is_free' => false,
            'monthly_price_cents' => 1990,
            'currency' => 'BRL',
            'monthly_short_url_limit' => 25,
            'monthly_click_limit' => 5000,
            'custom_domain_limit' => 1,
            'allow_custom_slug' => true,
            'allow_custom_domain' => true,
            'allow_custom_expiration' => true,
            'allow_lifetime_links' => false,
            'is_active' => true,
            'sort_order' => 20,
            'is_public' => true,
            'is_featured' => false,
            'stripe_product_id' => null,
            'stripe_price_id' => null,
        ], $overrides);
    }
}
