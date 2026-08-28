<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BillingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_premium_nao_abre_checkout_duplicado(): void
    {
        config()->set('services.stripe.secret', 'sk_test_fake');
        config()->set('services.stripe.premium_price_id', 'price_test_fake');

        $user = User::factory()->create();
        $plan = Plan::query()->updateOrCreate(['code' => 'premium'], [
            'name' => 'Premium',
            'description' => 'Premium',
            'is_free' => false,
            'monthly_short_url_limit' => null,
            'allow_custom_slug' => true,
            'allow_custom_domain' => true,
            'allow_custom_expiration' => true,
            'allow_lifetime_links' => true,
            'is_active' => true,
            'is_public' => true,
            'stripe_price_id' => 'price_test_fake',
        ]);
        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'stripe',
            'status' => 'active',
            'provider_customer_id' => 'cus_existing',
            'provider_subscription_id' => 'sub_existing',
        ]);

        $this->actingAs($user)
            ->post(route('billing.checkout'), ['plan_id' => $plan->id])
            ->assertRedirect(route('billing.index'))
            ->assertSessionHas('status');
    }
}
