<?php

declare(strict_types=1);

namespace Tests\Feature\Quota;

use App\Models\MonthlyQuotaUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class ResetMonthlyQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_prunes_history_older_than_keep_window(): void
    {
        $user = User::factory()->create();

        MonthlyQuotaUsage::query()->create([
            'user_id' => $user->id,
            'quota_month' => '2020-01',
            'free_links_created' => 3,
            'free_links_rejected' => 0,
        ]);

        MonthlyQuotaUsage::query()->create([
            'user_id' => $user->id,
            'quota_month' => now('UTC')->format('Y-m'),
            'free_links_created' => 1,
            'free_links_rejected' => 0,
        ]);

        $exit = Artisan::call('panel:quota:reset', ['--keep-months' => 12]);

        $this->assertSame(0, $exit);
        $this->assertDatabaseMissing('monthly_quota_usage', ['quota_month' => '2020-01']);
        $this->assertDatabaseHas('monthly_quota_usage', ['quota_month' => now('UTC')->format('Y-m')]);
    }

    public function test_command_is_idempotent_when_no_history_exists(): void
    {
        $exit = Artisan::call('panel:quota:reset');

        $this->assertSame(0, $exit);
        $this->assertSame(0, MonthlyQuotaUsage::query()->count());
    }
}
