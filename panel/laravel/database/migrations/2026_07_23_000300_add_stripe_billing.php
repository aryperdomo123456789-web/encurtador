<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            if (!Schema::hasColumn('plans', 'stripe_price_id')) {
                $table->string('stripe_price_id', 128)->nullable()->after('name');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'stripe_customer_id')) {
                $table->string('stripe_customer_id', 128)->nullable()->unique()->after('password');
            }
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            if (!Schema::hasColumn('subscriptions', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id', 128)->nullable()->unique()->after('provider');
            }
            if (!Schema::hasColumn('subscriptions', 'stripe_event_id')) {
                $table->string('stripe_event_id', 128)->nullable()->after('stripe_subscription_id');
            }
        });

        // Semeia planos free/premium (idempotente).
        $now = now();
        DB::table('plans')->updateOrInsert(
            ['code' => 'free'],
            [
                'name'                  => 'Free',
                'description'           => 'Plano gratuito com cota mensal e expiração curta.',
                'is_free'               => true,
                'monthly_short_url_limit' => 5,
                'allow_custom_slug'     => false,
                'allow_custom_domain'   => false,
                'allow_custom_expiration' => false,
                'allow_lifetime_links'  => false,
                'is_active'             => true,
                'stripe_price_id'       => null,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('plans')->updateOrInsert(
            ['code' => 'premium'],
            [
                'name'                  => 'Premium',
                'description'           => 'Plano premium com slug customizado e dominio proprio.',
                'is_free'               => false,
                'monthly_short_url_limit' => null,
                'allow_custom_slug'     => true,
                'allow_custom_domain'   => true,
                'allow_custom_expiration' => true,
                'allow_lifetime_links'  => true,
                'is_active'             => true,
                'stripe_price_id'       => env('STRIPE_PREMIUM_PRICE_ID'),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('subscriptions', 'stripe_event_id')) {
                $table->dropColumn('stripe_event_id');
            }
            if (Schema::hasColumn('subscriptions', 'stripe_subscription_id')) {
                $table->dropUnique(['stripe_subscription_id']);
                $table->dropColumn('stripe_subscription_id');
            }
        });

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'stripe_customer_id')) {
                $table->dropUnique(['stripe_customer_id']);
                $table->dropColumn('stripe_customer_id');
            }
        });

        Schema::table('plans', function (Blueprint $table): void {
            if (Schema::hasColumn('plans', 'stripe_price_id')) {
                $table->dropColumn('stripe_price_id');
            }
        });
    }
};
