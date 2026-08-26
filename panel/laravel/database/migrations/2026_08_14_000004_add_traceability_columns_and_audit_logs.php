<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('action', 20);
            $table->json('changes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('request_id', 128)->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_user_id', 'created_at']);
        });

        Schema::table('rich_previews', function (Blueprint $table): void {
            $table->foreignId('created_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('customer_domains', function (Blueprint $table): void {
            $table->foreignId('created_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('short_links', function (Blueprint $table): void {
            $table->foreignId('created_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreignId('created_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('monthly_quota_usage', function (Blueprint $table): void {
            $table->foreignId('created_by_user_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('branding_settings', function (Blueprint $table): void {
            $table->foreignId('created_by_user_id')->nullable()->after('key')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->after('created_by_user_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('link_event_log', function (Blueprint $table): void {
            $table->foreignId('actor_user_id')->nullable()->after('short_link_id')->constrained('users')->nullOnDelete();
            $table->string('request_id', 128)->nullable()->after('payload')->index();
        });

        DB::statement('UPDATE rich_previews SET created_by_user_id = user_id, updated_by_user_id = user_id WHERE created_by_user_id IS NULL');
        DB::statement('UPDATE customer_domains SET created_by_user_id = user_id, updated_by_user_id = user_id WHERE created_by_user_id IS NULL');
        DB::statement('UPDATE short_links SET created_by_user_id = user_id, updated_by_user_id = user_id WHERE created_by_user_id IS NULL');
        DB::statement('UPDATE subscriptions SET created_by_user_id = user_id, updated_by_user_id = user_id WHERE created_by_user_id IS NULL');
        DB::statement('UPDATE monthly_quota_usage SET created_by_user_id = user_id, updated_by_user_id = user_id WHERE created_by_user_id IS NULL');
        DB::statement('UPDATE link_event_log SET actor_user_id = (SELECT user_id FROM short_links WHERE short_links.id = link_event_log.short_link_id) WHERE actor_user_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('link_event_log', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('actor_user_id');
            $table->dropColumn('request_id');
        });

        Schema::table('branding_settings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('updated_by_user_id');
        });

        Schema::table('monthly_quota_usage', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('updated_by_user_id');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('updated_by_user_id');
        });

        Schema::table('short_links', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('updated_by_user_id');
        });

        Schema::table('customer_domains', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('updated_by_user_id');
        });

        Schema::table('rich_previews', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
            $table->dropConstrainedForeignId('updated_by_user_id');
        });

        Schema::dropIfExists('audit_logs');
    }
};
