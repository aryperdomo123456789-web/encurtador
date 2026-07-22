<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            $table->boolean('is_free')->default(false);
            $table->unsignedInteger('monthly_short_url_limit')->nullable();
            $table->boolean('allow_custom_slug')->default(false);
            $table->boolean('allow_custom_domain')->default(false);
            $table->boolean('allow_custom_expiration')->default(false);
            $table->boolean('allow_lifetime_links')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('provider', 40)->default('manual');
            $table->string('provider_customer_id', 120)->nullable();
            $table->string('provider_subscription_id', 120)->nullable();
            $table->string('status', 20)->default('trialing');
            $table->dateTime('current_period_start')->nullable();
            $table->dateTime('current_period_end')->nullable();
            $table->boolean('cancel_at_period_end')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_domains', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('domain', 190)->unique();
            $table->string('status', 20)->default('pending_dns');
            $table->boolean('is_primary')->default(false);
            $table->string('dns_target', 190)->nullable();
            $table->timestamp('dns_verified_at')->nullable();
            $table->timestamp('shlink_domain_registered_at')->nullable();
            $table->string('tls_mode', 20)->default('on_demand');
            $table->string('tls_status', 20)->default('unknown');
            $table->string('tls_last_error', 255)->nullable();
            $table->json('shlink_domain_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('short_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_domain_id')->nullable()->constrained('customer_domains')->nullOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('shlink_short_url', 500)->nullable()->unique();
            $table->string('shlink_short_code', 191)->nullable()->unique();
            $table->string('domain', 190);
            $table->text('long_url');
            $table->string('custom_slug', 190)->nullable();
            $table->string('generated_slug', 190)->nullable();
            $table->boolean('is_custom_slug')->default(false);
            $table->boolean('is_free_link')->default(false);
            $table->dateTime('valid_until')->nullable();
            $table->dateTime('valid_since')->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('created_via', 20)->default('panel');
            $table->json('shlink_payload')->nullable();
            $table->json('shlink_response')->nullable();
            $table->dateTime('last_stats_sync_at')->nullable();
            $table->timestamps();
        });

        Schema::create('monthly_quota_usage', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('quota_month', 7);
            $table->unsignedInteger('free_links_created')->default(0);
            $table->unsignedInteger('free_links_rejected')->default(0);
            $table->dateTime('last_free_link_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'quota_month']);
        });

        Schema::create('link_event_log', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('short_link_id')->constrained('short_links')->cascadeOnDelete();
            $table->string('event_type', 20);
            $table->string('severity', 20)->default('info');
            $table->string('message', 255);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_event_log');
        Schema::dropIfExists('monthly_quota_usage');
        Schema::dropIfExists('short_links');
        Schema::dropIfExists('customer_domains');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
