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
        Schema::create('branding_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('logo_path', 255)->nullable();
            $table->string('favicon_path', 255)->nullable();
            $table->string('social_image_path', 255)->nullable();
            $table->timestamps();
        });

        DB::table('branding_settings')->insert([
            'key' => 'default',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('branding_settings');
    }
};
