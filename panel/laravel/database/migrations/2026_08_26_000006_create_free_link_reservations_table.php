<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('free_link_reservations')) {
            return;
        }

        Schema::create('free_link_reservations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('quota_month', 7);
            $table->string('status', 20)->default('reserved');
            $table->foreignId('short_link_id')->nullable()->constrained('short_links')->nullOnDelete();
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'quota_month', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('free_link_reservations');
    }
};
