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
            if (! Schema::hasColumn('plans', 'monthly_price_cents')) {
                $table->unsignedInteger('monthly_price_cents')->default(0)->after('is_free');
            }
            if (! Schema::hasColumn('plans', 'currency')) {
                $table->char('currency', 3)->default('BRL')->after('monthly_price_cents');
            }
            if (! Schema::hasColumn('plans', 'monthly_click_limit')) {
                $table->unsignedInteger('monthly_click_limit')->nullable()->after('monthly_short_url_limit');
            }
            if (! Schema::hasColumn('plans', 'custom_domain_limit')) {
                $table->unsignedInteger('custom_domain_limit')->default(0)->after('monthly_click_limit');
            }
            if (! Schema::hasColumn('plans', 'marketing_label')) {
                $table->string('marketing_label', 120)->nullable()->after('description');
            }
            if (! Schema::hasColumn('plans', 'sort_order')) {
                $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
            }
            if (! Schema::hasColumn('plans', 'is_public')) {
                $table->boolean('is_public')->default(true)->after('sort_order');
            }
            if (! Schema::hasColumn('plans', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_public');
            }
            if (! Schema::hasColumn('plans', 'stripe_product_id')) {
                $table->string('stripe_product_id', 128)->nullable()->after('stripe_price_id');
                $table->index('stripe_product_id');
            }
        });

        $now = now();

        DB::table('plans')->where('code', 'free')->update([
            'monthly_price_cents' => 0,
            'currency' => 'BRL',
            'monthly_short_url_limit' => 5,
            'monthly_click_limit' => 1000,
            'custom_domain_limit' => 0,
            'marketing_label' => 'Ideal para teste rápido',
            'sort_order' => 10,
            'is_public' => true,
            'is_featured' => false,
            'updated_at' => $now,
        ]);

        DB::table('plans')->where('code', 'premium')->update([
            'marketing_label' => 'Plano legado — preservar assinaturas existentes',
            'sort_order' => 90,
            'is_public' => false,
            'is_featured' => false,
            'updated_at' => $now,
        ]);

        DB::table('plans')->updateOrInsert(
            ['code' => 'start'],
            [
                'name' => 'Start',
                'description' => 'Para creators e pequenas lojas.',
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
                'marketing_label' => 'Para creators e pequenas lojas',
                'sort_order' => 20,
                'is_public' => true,
                'is_featured' => false,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('plans')->updateOrInsert(
            ['code' => 'pro'],
            [
                'name' => 'Pro',
                'description' => 'Para gestores de tráfego e agências.',
                'is_free' => false,
                'monthly_price_cents' => 4990,
                'currency' => 'BRL',
                'monthly_short_url_limit' => 100,
                'monthly_click_limit' => 25000,
                'custom_domain_limit' => 3,
                'allow_custom_slug' => true,
                'allow_custom_domain' => true,
                'allow_custom_expiration' => true,
                'allow_lifetime_links' => true,
                'is_active' => true,
                'marketing_label' => 'Para gestores de tráfego e agências',
                'sort_order' => 30,
                'is_public' => true,
                'is_featured' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table): void {
            $columns = [
                'monthly_price_cents',
                'currency',
                'monthly_click_limit',
                'custom_domain_limit',
                'marketing_label',
                'sort_order',
                'is_public',
                'is_featured',
                'stripe_product_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
