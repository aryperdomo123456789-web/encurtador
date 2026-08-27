<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversion_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->nullable()->constrained('workspaces')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('short_link_id')->nullable()->constrained('short_links')->nullOnDelete();
            $table->string('event_type', 80);
            $table->string('event_id', 120)->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->json('properties')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent_hash', 64)->nullable();
            $table->timestamps();

            $table->unique(['workspace_id', 'event_id']);
            $table->index(['workspace_id', 'event_type', 'occurred_at']);
            $table->index(['short_link_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_events');
    }
};
