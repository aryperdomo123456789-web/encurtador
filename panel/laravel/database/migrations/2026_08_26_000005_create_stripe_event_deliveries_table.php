<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stripe_event_deliveries')) {
            Schema::create('stripe_event_deliveries', function (Blueprint $table): void {
                $table->id();
                $table->string('event_id', 128)->unique();
                $table->string('event_type', 128)->nullable();
                $table->unsignedBigInteger('provider_created_at')->nullable();
                $table->string('status', 32)->default('received');
                $table->text('last_error')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['status', 'created_at']);
            });
        }

        if (Schema::hasTable('subscriptions') && ! Schema::hasColumn('subscriptions', 'stripe_event_created_at')) {
            Schema::table('subscriptions', function (Blueprint $table): void {
                $table->unsignedBigInteger('stripe_event_created_at')->nullable()->after('stripe_event_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('subscriptions') && Schema::hasColumn('subscriptions', 'stripe_event_created_at')) {
            Schema::table('subscriptions', function (Blueprint $table): void {
                $table->dropColumn('stripe_event_created_at');
            });
        }

        Schema::dropIfExists('stripe_event_deliveries');
    }
};
