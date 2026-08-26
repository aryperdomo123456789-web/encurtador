<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rich_previews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('slug', 90)->unique();
            $table->text('description')->nullable();
            $table->text('destination_url');
            $table->string('image_path', 255)->nullable();
            $table->text('image_url')->nullable();
            $table->string('cta_label', 50)->default('Abrir link');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('click_count')->default(0);
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rich_previews');
    }
};
