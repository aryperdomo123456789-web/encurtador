<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branding_settings', function (Blueprint $table): void {
            $table->string('logo_light_path', 255)->nullable()->after('logo_path');
            $table->string('logo_dark_path', 255)->nullable()->after('logo_light_path');
        });
    }

    public function down(): void
    {
        Schema::table('branding_settings', function (Blueprint $table): void {
            $table->dropColumn(['logo_light_path', 'logo_dark_path']);
        });
    }
};
