<?php

namespace Tests\Feature\Billing;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-webhook-secret-melink';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.stripe.webhook_secret', $this->secret);
        Plan::query()->updateOrCreate(['code' => 'free'], [
            'name' => 'Free',
            'description' => 'Free',
            'is_free' => true,
            'monthly_short_url_limit' => 5,
            'allow_custom_slug' => false,
            'allow_custom_domain' => false,
            'allow_custom_expiration' => false,
            'allow_lifetime_links' => false,
            'is_active' => true,
        ]);
        Plan::query()->updateOrCreate(['code' => 'premium'], [
            'name' => 'Premium',
            'description' => 'Premium',
            'is_free' => false,
            'monthly_short_url_limit' => null,
            'allow_custom_slug' => true,
            'allow_custom_domain' => true,
            'allow_custom_expiration' => true,
            'allow_lifetime_links' => true,
            'is_active' => true,
        ]);
    }

    public function test_reprocessamento_do_mesmo_evento_e_idempotente(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['stripe_customer_id' => 'cus_idempotent'])->save();
        $payload = $this->subscriptionPayload('evt_idempotent', 200, 'active', 'cus_idempotent');

        $first = $this->postJson('/billing/webhook', $payload['body'], [
            'Stripe-Signature' => $payload['signature'],
        ]);
        $second = $this->postJson('/billing/webhook', $payload['body'], [
            'Stripe-Signature' => $payload['signature'],
        ]);

        $first->assertOk()->assertJsonPath('status', 'ok');
        $second->assertOk()->assertJson(['status' => 'duplicate', 'id' => 'evt_idempotent']);
        $this->assertDatabaseCount('stripe_event_deliveries', 1);
        $this->assertDatabaseHas('stripe_event_deliveries', [
            'event_id' => 'evt_idempotent',
            'status' => 'processed',
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'active',
            'stripe_event_id' => 'evt_idempotent',
        ]);
    }

    public function test_evento_antigo_nao_sobrescreve_estado_mais_novo(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['stripe_customer_id' => 'cus_ordered'])->save();

        $new = $this->subscriptionPayload('evt_new', 200, 'active', 'cus_ordered');
        $old = $this->subscriptionPayload('evt_old', 100, 'canceled', 'cus_ordered');

        $this->postJson('/billing/webhook', $new['body'], ['Stripe-Signature' => $new['signature']])->assertOk();
        $this->postJson('/billing/webhook', $old['body'], ['Stripe-Signature' => $old['signature']])->assertOk();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'status' => 'active',
            'stripe_event_id' => 'evt_new',
            'stripe_event_created_at' => 200,
        ]);
        $this->assertDatabaseHas('stripe_event_deliveries', [
            'event_id' => 'evt_old',
            'status' => 'processed',
        ]);
    }

    public function test_assinatura_invalida_e_rejeitada(): void
    {
        $body = json_encode([
            'id' => 'evt_invalid',
            'type' => 'customer.subscription.updated',
            'created' => 200,
            'data' => ['object' => []],
        ], JSON_THROW_ON_ERROR);

        $this->postJson('/billing/webhook', json_decode($body, true), [
            'Stripe-Signature' => 't=200,v1=invalid',
        ])->assertStatus(400);
    }

    /** @return array{body: array<string, mixed>, signature: string} */
    private function subscriptionPayload(string $eventId, int $created, string $status, string $customer): array
    {
        $body = [
            'id' => $eventId,
            'object' => 'event',
            'type' => 'customer.subscription.updated',
            'created' => $created,
            'data' => [
                'object' => [
                    'id' => 'sub_'.$customer,
                    'object' => 'subscription',
                    'customer' => $customer,
                    'status' => $status,
                    'current_period_start' => 100,
                    'current_period_end' => 200,
                    'cancel_at_period_end' => false,
                ],
            ],
        ];
        $raw = json_encode($body, JSON_THROW_ON_ERROR);

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$raw, $this->secret);

        return [
            'body' => $body,
            'signature' => 't='.$timestamp.',v1='.$signature,
        ];
    }
}
