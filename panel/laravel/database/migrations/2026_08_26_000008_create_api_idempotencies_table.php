<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_idempotencies')) {
            return;
        }

        Schema::create('api_idempotencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('idempotency_key', 80);
            $table->string('method', 10);
            $table->string('route', 190);
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['user_id', 'idempotency_key', 'method', 'route'], 'api_idempotency_unique_request');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotencies');
    }
};
