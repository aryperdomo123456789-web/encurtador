<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workspaces')) {
            Schema::create('workspaces', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('name', 120);
                $table->string('slug', 80)->unique();
                $table->string('status', 20)->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('workspace_members')) {
            Schema::create('workspace_members', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('role', 20)->default('member');
                $table->timestamps();
                $table->unique(['workspace_id', 'user_id']);
                $table->index(['user_id', 'role']);
            });
        }

        if (! Schema::hasColumn('short_links', 'workspace_id')) {
            Schema::table('short_links', function (Blueprint $table): void {
                $table->foreignId('workspace_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
                $table->index(['workspace_id', 'status']);
            });
        }

        if (! Schema::hasColumn('customer_domains', 'workspace_id')) {
            Schema::table('customer_domains', function (Blueprint $table): void {
                $table->foreignId('workspace_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
                $table->index(['workspace_id', 'status']);
            });
        }

        $now = now();
        $users = DB::table('users')->select(['id', 'name'])->get();
        foreach ($users as $user) {
            $workspaceId = DB::table('workspaces')->where('owner_user_id', $user->id)->value('id');
            if ($workspaceId === null) {
                $workspaceId = DB::table('workspaces')->insertGetId([
                    'owner_user_id' => $user->id,
                    'name' => trim((string) ($user->name ?: 'Workspace')).' — MElink',
                    'slug' => 'workspace-'.$user->id.'-'.Str::lower(Str::random(6)),
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('workspace_members')->updateOrInsert(
                ['workspace_id' => $workspaceId, 'user_id' => $user->id],
                ['role' => 'owner', 'created_at' => $now, 'updated_at' => $now]
            );
            DB::table('short_links')->where('user_id', $user->id)->whereNull('workspace_id')->update(['workspace_id' => $workspaceId]);
            DB::table('customer_domains')->where('user_id', $user->id)->whereNull('workspace_id')->update(['workspace_id' => $workspaceId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customer_domains', 'workspace_id')) {
            Schema::table('customer_domains', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('workspace_id');
            });
        }

        if (Schema::hasColumn('short_links', 'workspace_id')) {
            Schema::table('short_links', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('workspace_id');
            });
        }

        Schema::dropIfExists('workspace_members');
        Schema::dropIfExists('workspaces');
    }
};
