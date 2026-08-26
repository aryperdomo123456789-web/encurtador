<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rich_previews', function (Blueprint $table): void {
            $table->string('campaign_name', 120)->nullable()->after('slug');
            $table->string('category_name', 120)->nullable()->after('campaign_name');
        });
    }

    public function down(): void
    {
        Schema::table('rich_previews', function (Blueprint $table): void {
            $table->dropColumn(['campaign_name', 'category_name']);
        });
    }
};
